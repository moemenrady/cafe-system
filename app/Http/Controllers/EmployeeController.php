<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\EmployeePayroll;
use App\Services\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeController extends Controller
{
    protected PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    /**
     * عرض قائمة وسجل الموظفين وإحصائيات الرواتب والمستحقات
     */
    public function index(Request $request)
    {
        // مزامنة رواتب الشهر الحالي لجميع الموظفين النشطين
        $activeEmployees = Employee::where('is_active', true)->get();
        foreach ($activeEmployees as $emp) {
            $this->payrollService->ensurePayrollsGenerated($emp);
        }

        $query = Employee::query()
            ->with(['payrolls' => function ($q) {
                $q->orderByDesc('salary_month');
            }]);

        // 1. بحث بالاسم، الهاتف، المسمى، أو رقم البطاقة
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('job_title', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        // 2. فلتر الحالة (نشط / غير نشط)
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // 3. فلتر المستحقات (من لديه مستحق / مسدد بالكامل)
        if ($request->filled('dues_status')) {
            $duesStatus = $request->input('dues_status');
            if ($duesStatus === 'has_dues') {
                $query->whereHas('payrolls', function ($q) {
                    $q->whereIn('status', ['pending', 'partial']);
                });
            } elseif ($duesStatus === 'all_paid') {
                $query->whereDoesntHave('payrolls', function ($q) {
                    $q->whereIn('status', ['pending', 'partial']);
                });
            }
        }

        // 4. الترتيب
        $sortBy = $request->input('sort_by', 'created_at');
        if ($sortBy === 'name') {
            $query->orderBy('name');
        } elseif ($sortBy === 'salary') {
            $query->orderByDesc('base_salary');
        } else {
            $query->orderByDesc('id');
        }

        $employees = $query->paginate(15)->withQueryString();

        // 5. كروت المؤشرات العليا (KPIs)
        $totalEmployeesCount = Employee::count();
        $activeEmployeesCount = Employee::where('is_active', true)->count();
        $totalMonthlySalaries = (float) Employee::where('is_active', true)->sum('base_salary');

        $currentMonth = Carbon::now()->format('Y-m');
        $totalPaidThisMonth = (float) EmployeePayroll::where('salary_month', $currentMonth)->sum('paid_amount');

        // إجمالي المستحقات التراكمية غير المدفوعة لكافة الموظفين
        $totalPendingDues = (float) EmployeePayroll::whereIn('status', ['pending', 'partial'])
            ->sum(DB::raw('net_salary - paid_amount'));

        // إجمالي البونص والخصومات للشهر الحالي
        $currentMonthBonus = (float) EmployeeAdjustment::where('salary_month', $currentMonth)->where('type', 'bonus')->sum('amount');
        $currentMonthDeductions = (float) EmployeeAdjustment::where('salary_month', $currentMonth)->where('type', 'deduction')->sum('amount');

        return view('employees.index', compact(
            'employees',
            'totalEmployeesCount',
            'activeEmployeesCount',
            'totalMonthlySalaries',
            'totalPaidThisMonth',
            'totalPendingDues',
            'currentMonthBonus',
            'currentMonthDeductions',
            'currentMonth'
        ));
    }

    /**
     * حفظ موظف جديد
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:50',
            'national_id' => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:255',
            'job_title'   => 'required|string|max:100',
            'base_salary' => 'required|numeric|min:0',
            'hire_date'   => 'nullable|date',
            'notes'       => 'nullable|string',
        ]);

        if (empty($validated['hire_date'])) {
            $validated['hire_date'] = Carbon::now()->toDateString();
        }

        $validated['is_active'] = true;

        $employee = Employee::create($validated);

        // توليد رواتب الشهور المستحقة له فورياً
        $this->payrollService->ensurePayrollsGenerated($employee);

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "تم إضافة الموظف \"{$employee->name}\" وتوليد سجل الراتب بنجاح.");
    }

    /**
     * عرض صفحة الموظف وسجل الرواتب والمستحقات
     */
    public function show(Employee $employee)
    {
        // توليد ومزامنة أي شهور مستحقة للموظف
        $this->payrollService->ensurePayrollsGenerated($employee);

        $employee->load([
            'payrolls' => function ($q) {
                $q->orderByDesc('salary_month');
            },
            'payrolls.payer',
            'adjustments' => function ($q) {
                $q->orderByDesc('date')->orderByDesc('id');
            },
            'adjustments.creator',
        ]);

        $totalPaid = $employee->total_paid;
        $totalDues = $employee->total_dues;
        $totalBonus = $employee->total_bonus;
        $totalDeductions = $employee->total_deductions;

        // قائمة الشهور لاختيارها في إضافة بونص أو خصم (12 شهر سابق + الحالي + 3 قادم)
        $availableMonths = [];
        $cursor = Carbon::now()->subMonths(12)->startOfMonth();
        $endCursor = Carbon::now()->addMonths(3)->startOfMonth();
        while ($cursor->lte($endCursor)) {
            $availableMonths[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }
        $availableMonths = array_reverse($availableMonths);

        return view('employees.show', compact(
            'employee',
            'totalPaid',
            'totalDues',
            'totalBonus',
            'totalDeductions',
            'availableMonths'
        ));
    }

    /**
     * تحديث بيانات الموظف
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:50',
            'national_id' => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:255',
            'job_title'   => 'required|string|max:100',
            'base_salary' => 'required|numeric|min:0',
            'hire_date'   => 'nullable|date',
            'is_active'   => 'nullable|boolean',
            'notes'       => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        $oldBaseSalary = (float) $employee->base_salary;
        $employee->update($validated);

        // إذا تغير الراتب الأساسي، نقوم بتحديث سجل الشهر الحالي إذا كان معلقاً
        if ($oldBaseSalary !== (float) $employee->base_salary) {
            $currentMonth = Carbon::now()->format('Y-m');
            $currentPayroll = $employee->payrolls()->where('salary_month', $currentMonth)->first();
            if ($currentPayroll && $currentPayroll->status === 'pending') {
                $currentPayroll->base_salary = $employee->base_salary;
                $currentPayroll->save();
                $this->payrollService->recalculateMonthPayroll($employee, $currentMonth);
            }
        }

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "تم تحديث بيانات الموظف \"{$employee->name}\" بنجاح.");
    }

    /**
     * حذف موظف
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        $name = $employee->name;
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', "تم حذف الموظف \"{$name}\" بنجاح مع الاحتفاظ بسجلات الرواتب المالية.");
    }

    /**
     * إضافة خصم أو بونص للموظف والتأثير الفوري على الشهر المحدد
     */
    public function addAdjustment(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'type'         => 'required|in:bonus,deduction',
            'amount'       => 'required|numeric|min:0.01',
            'salary_month' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'reason'       => 'required|string|max:255',
            'date'         => 'nullable|date',
        ]);

        if (empty($validated['date'])) {
            $validated['date'] = Carbon::now()->toDateString();
        }

        $validated['employee_id'] = $employee->id;
        $validated['created_by']  = auth()->id();

        $adjustment = EmployeeAdjustment::create($validated);

        // إعادة احتساب راتب الشهر المستهدف فورياً
        $payroll = $this->payrollService->recalculateMonthPayroll($employee, $validated['salary_month']);

        $typeLabel = $adjustment->type === 'bonus' ? 'مكافأة / بونص' : 'خصم';
        $formattedAmount = number_format($adjustment->amount, 2);

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "تم إضافة {$typeLabel} بمبلغ {$formattedAmount} ج.م لشهر ({$validated['salary_month']}) وتحديث صافي الراتب المستحق بنجاح.");
    }

    /**
     * حذف حركة خصم أو بونص
     */
    public function deleteAdjustment(EmployeeAdjustment $adjustment): RedirectResponse
    {
        $employee = $adjustment->employee;
        $month = $adjustment->salary_month;
        $typeLabel = $adjustment->type === 'bonus' ? 'المكافأة' : 'الخصم';

        $adjustment->delete();

        // إعادة احتساب صافي الراتب للشهر بعد الحذف
        $this->payrollService->recalculateMonthPayroll($employee, $month);

        return redirect()->route('employees.show', $employee->id)
            ->with('success', "تم حذف {$typeLabel} وإعادة احتساب راتب شهر ({$month}) بنجاح.");
    }

    /**
     * تسجيل صرف راتب شهر محدد
     */
    public function payMonth(Request $request, EmployeePayroll $payroll): RedirectResponse
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|in:cash,visa,instapay,bank_transfer',
            'notes'          => 'nullable|string|max:255',
        ]);

        $employee = $payroll->employee;

        $this->payrollService->paySingleMonth(
            $payroll,
            (float) $validated['amount'],
            $validated['payment_method'],
            $validated['notes'] ?? null,
            auth()->id()
        );

        $monthName = $payroll->month_name_ar;
        return redirect()->route('employees.show', $employee->id)
            ->with('success', "تم تسجيل صرف راتب شهر {$monthName} للموظف \"{$employee->name}\" بنجاح.");
    }

    /**
     * تسديد كافة المستحقات المتأخرة للموظف دفعة واحدة
     */
    public function payAllDues(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,visa,instapay,bank_transfer',
            'notes'          => 'nullable|string|max:255',
        ]);

        $totalPaid = $this->payrollService->payAllDues(
            $employee,
            $validated['payment_method'],
            $validated['notes'] ?? null,
            auth()->id()
        );

        $formatted = number_format($totalPaid, 2);
        return redirect()->route('employees.show', $employee->id)
            ->with('success', "تم تسديد كامل المستحقات المتأخرة للموظف \"{$employee->name}\" بإجمالي {$formatted} ج.م بنجاح.");
    }

    /**
     * تصدير شيت إكسيل لجميع الموظفين (UTF-8 BOM)
     */
    public function exportAll(Request $request): StreamedResponse
    {
        $employees = Employee::with(['payrolls'])->orderBy('name')->get();

        $filename = 'سجل_الموظفين_والرواتب_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($employees) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM لضمان فتح اللغة العربية بسلاسة في Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // الترويسة
            fputcsv($handle, [
                '# كود الموظف',
                'اسم الموظف',
                'رقم الهاتف',
                'رقم البطاقة',
                'المسمى الوظيفي',
                'الراتب الأساسي الشهري',
                'حالة الموظف',
                'تاريخ التعيين',
                'إجمالي المقبوض تاريخياً',
                'إجمالي المستحق حالياً',
                'حالة راتب الشهر الحالي',
                'عنوان السكن',
                'ملاحظات',
            ]);

            $totalSalaries = 0;
            $totalAllPaid = 0;
            $totalAllDues = 0;

            foreach ($employees as $emp) {
                $dues = $emp->total_dues;
                $paid = $emp->total_paid;
                $totalSalaries += (float) $emp->base_salary;
                $totalAllPaid  += $paid;
                $totalAllDues  += $dues;

                $currentStatus = match ($emp->current_month_status) {
                    'paid'    => 'مدفوع بالكامل',
                    'partial' => 'مدفوع جزئياً',
                    default   => 'مستحق لم يدفع',
                };

                fputcsv($handle, [
                    $emp->id,
                    $emp->name,
                    $emp->phone,
                    $emp->national_id ?? '-',
                    $emp->job_title,
                    number_format($emp->base_salary, 2, '.', ''),
                    $emp->is_active ? 'على رأس العمل' : 'متوقف',
                    $emp->hire_date ? $emp->hire_date->format('Y-m-d') : '-',
                    number_format($paid, 2, '.', ''),
                    number_format($dues, 2, '.', ''),
                    $currentStatus,
                    $emp->address ?? '-',
                    $emp->notes ?? '-',
                ]);
            }

            // صف الإجماليات
            fputcsv($handle, []);
            fputcsv($handle, [
                'الإجمالي العام',
                'عدد الموظفين: ' . count($employees),
                '-',
                '-',
                '-',
                number_format($totalSalaries, 2, '.', ''),
                '-',
                '-',
                number_format($totalAllPaid, 2, '.', ''),
                number_format($totalAllDues, 2, '.', ''),
                '-',
                '-',
                '-',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * تصدير شيت إكسيل تفصيلي لموظف واحد
     */
    public function exportSingle(Employee $employee): StreamedResponse
    {
        $this->payrollService->ensurePayrollsGenerated($employee);

        $employee->load([
            'payrolls' => function ($q) {
                $q->orderByDesc('salary_month');
            },
            'adjustments' => function ($q) {
                $q->orderByDesc('date');
            },
        ]);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $employee->name);
        $filename = "كشف_رواتب_الموظف_{$safeName}_" . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($employee) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // ملخص بيانات الموظف
            fputcsv($handle, ['=== كشف حساب ومسير رواتب الموظف ===']);
            fputcsv($handle, ['اسم الموظف', $employee->name]);
            fputcsv($handle, ['رقم الهاتف', $employee->phone]);
            fputcsv($handle, ['رقم البطاقة', $employee->national_id ?? '-']);
            fputcsv($handle, ['المسمى الوظيفي', $employee->job_title]);
            fputcsv($handle, ['الراتب الأساسي', number_format($employee->base_salary, 2, '.', '') . ' ج.م']);
            fputcsv($handle, ['تاريخ التعيين', $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '-']);
            fputcsv($handle, ['إجمالي المقبوض تاريخياً', number_format($employee->total_paid, 2, '.', '') . ' ج.م']);
            fputcsv($handle, ['إجمالي المستحق حالياً', number_format($employee->total_dues, 2, '.', '') . ' ج.م']);
            fputcsv($handle, []);

            // جدول الرواتب الشهرية
            fputcsv($handle, ['--- سجل الرواتب الشهرية والقبض ---']);
            fputcsv($handle, [
                'الشهر',
                'الراتب الأساسي',
                'المكافآت والبونص (+)',
                'الخصومات (-)',
                'الصافي المستحق',
                'المبلغ المدفوع',
                'المتبقي المستحق',
                'حالة الصرف',
                'تاريخ الصرف',
                'طريقة الدفع',
                'ملاحظات',
            ]);

            foreach ($employee->payrolls as $p) {
                $statusAr = match ($p->status) {
                    'paid'    => 'مدفوع بالكامل',
                    'partial' => 'مدفوع جزئياً',
                    default   => 'مستحق معلق',
                };

                fputcsv($handle, [
                    $p->salary_month . ' (' . $p->month_name_ar . ')',
                    number_format($p->base_salary, 2, '.', ''),
                    number_format($p->total_bonus, 2, '.', ''),
                    number_format($p->total_deductions, 2, '.', ''),
                    number_format($p->net_salary, 2, '.', ''),
                    number_format($p->paid_amount, 2, '.', ''),
                    number_format($p->remaining_due, 2, '.', ''),
                    $statusAr,
                    $p->payment_date ? $p->payment_date->format('Y-m-d H:i') : '-',
                    $p->payment_method ?? '-',
                    $p->notes ?? '-',
                ]);
            }

            fputcsv($handle, []);
            // جدول حركات الخصومات والمكافآت
            fputcsv($handle, ['--- سجل تفاصيل الخصومات والمكافآت ---']);
            fputcsv($handle, [
                'التاريخ',
                'الشهر المتأثر',
                'النوع',
                'المبلغ',
                'السبب والبيان',
            ]);

            foreach ($employee->adjustments as $adj) {
                fputcsv($handle, [
                    $adj->date ? $adj->date->format('Y-m-d') : '-',
                    $adj->salary_month,
                    $adj->type === 'bonus' ? 'مكافأة / بونص' : 'خصم / جزاء',
                    number_format($adj->amount, 2, '.', ''),
                    $adj->reason,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
