<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeePayroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * التحقق من توليد جميع سجلات الرواتب المستحقة للموظف من تاريخ تعيينه حتى الشهر الحالي
     */
    public function ensurePayrollsGenerated(Employee $employee): void
    {
        // تحديد شهر البداية (تاريخ التعيين أو تاريخ إنشاء الحساب أو الشهر الحالي)
        $start = $employee->hire_date
            ? Carbon::parse($employee->hire_date)->startOfMonth()
            : ($employee->created_at ? $employee->created_at->copy()->startOfMonth() : Carbon::now()->startOfMonth());

        // نضمن عدم الرجوع أكثر من سنتين للوراء كحد أقصى للحماية والأداء
        $minAllowed = Carbon::now()->subMonths(24)->startOfMonth();
        if ($start->lt($minAllowed)) {
            $start = $minAllowed;
        }

        $end = Carbon::now()->startOfMonth();

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $monthStr = $cursor->format('Y-m');

            $payroll = EmployeePayroll::firstOrCreate(
                [
                    'employee_id'  => $employee->id,
                    'salary_month' => $monthStr,
                ],
                [
                    'base_salary'      => $employee->base_salary,
                    'total_bonus'      => 0.00,
                    'total_deductions' => 0.00,
                    'net_salary'       => $employee->base_salary,
                    'paid_amount'      => 0.00,
                    'status'           => 'pending',
                ]
            );

            // ربط أي خصومات أو مكافآت معلقة بهذا الشهر وإعادة احتسابها
            $this->recalculateMonthPayroll($employee, $monthStr);

            $cursor->addMonth();
        }
    }

    /**
     * إعادة احتساب صافي راتب الشهر للموظف بناءً على الراتب الأساسي والخصومات والمكافآت
     */
    public function recalculateMonthPayroll(Employee $employee, string $salaryMonth): EmployeePayroll
    {
        $payroll = EmployeePayroll::firstOrCreate(
            [
                'employee_id'  => $employee->id,
                'salary_month' => $salaryMonth,
            ],
            [
                'base_salary'      => $employee->base_salary,
                'total_bonus'      => 0.00,
                'total_deductions' => 0.00,
                'net_salary'       => $employee->base_salary,
                'paid_amount'      => 0.00,
                'status'           => 'pending',
            ]
        );

        $totalBonus = (float) $employee->adjustments()
            ->where('salary_month', $salaryMonth)
            ->where('type', 'bonus')
            ->sum('amount');

        $totalDeductions = (float) $employee->adjustments()
            ->where('salary_month', $salaryMonth)
            ->where('type', 'deduction')
            ->sum('amount');

        $payroll->total_bonus = $totalBonus;
        $payroll->total_deductions = $totalDeductions;
        $payroll->net_salary = max(0.00, (float) $payroll->base_salary + $totalBonus - $totalDeductions);

        // تحديث حالة الصرف وفقاً للمبلغ المدفوع
        if ($payroll->paid_amount >= $payroll->net_salary && $payroll->net_salary > 0) {
            $payroll->status = 'paid';
        } elseif ($payroll->paid_amount > 0) {
            $payroll->status = 'partial';
        } else {
            $payroll->status = 'pending';
        }

        $payroll->save();

        // تحديث الحركات لترتبط بمعرف السجل payroll_id
        $employee->adjustments()
            ->where('salary_month', $salaryMonth)
            ->whereNull('payroll_id')
            ->update(['payroll_id' => $payroll->id]);

        return $payroll;
    }

    /**
     * تسجيل صرف راتب شهر محدد للموظف
     */
    public function paySingleMonth(
        EmployeePayroll $payroll,
        float $amount,
        string $paymentMethod = 'cash',
        ?string $notes = null,
        ?int $paidBy = null
    ): EmployeePayroll {
        return DB::transaction(function () use ($payroll, $amount, $paymentMethod, $notes, $paidBy) {
            $payroll->paid_amount = min((float) $payroll->net_salary, (float) $payroll->paid_amount + $amount);

            if ($payroll->paid_amount >= $payroll->net_salary) {
                $payroll->status = 'paid';
            } elseif ($payroll->paid_amount > 0) {
                $payroll->status = 'partial';
            } else {
                $payroll->status = 'pending';
            }

            $payroll->payment_date = now();
            $payroll->payment_method = $paymentMethod;
            $payroll->paid_by = $paidBy;
            if ($notes) {
                $payroll->notes = trim(($payroll->notes ? $payroll->notes . "\n" : '') . $notes);
            }
            $payroll->save();

            return $payroll;
        });
    }

    /**
     * تسديد جميع المستحقات المتأخرة للموظف دفعة واحدة
     */
    public function payAllDues(
        Employee $employee,
        string $paymentMethod = 'cash',
        ?string $notes = null,
        ?int $paidBy = null
    ): float {
        return DB::transaction(function () use ($employee, $paymentMethod, $notes, $paidBy) {
            $this->ensurePayrollsGenerated($employee);

            $pendingPayrolls = $employee->payrolls()
                ->whereIn('status', ['pending', 'partial'])
                ->get();

            $totalPaid = 0.0;
            foreach ($pendingPayrolls as $p) {
                $due = max(0.0, (float) $p->net_salary - (float) $p->paid_amount);
                if ($due > 0) {
                    $p->paid_amount = (float) $p->net_salary;
                    $p->status = 'paid';
                    $p->payment_date = now();
                    $p->payment_method = $paymentMethod;
                    $p->paid_by = $paidBy;
                    if ($notes) {
                        $p->notes = trim(($p->notes ? $p->notes . "\n" : '') . "تسديد شامل: {$notes}");
                    }
                    $p->save();
                    $totalPaid += $due;
                }
            }

            return $totalPaid;
        });
    }
}
