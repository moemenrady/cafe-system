<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'national_id',
        'address',
        'job_title',
        'base_salary',
        'hire_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'base_salary' => 'decimal:2',
        'hire_date'   => 'date',
        'is_active'   => 'boolean',
    ];

    /**
     * حساب المستخدم المرتبط بالموظف (إن وجد)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * سجل الرواتب الشهرية والقبض
     */
    public function payrolls(): HasMany
    {
        return $this->hasMany(EmployeePayroll::class);
    }

    /**
     * سجل الخصومات والمكافآت
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(EmployeeAdjustment::class);
    }

    /**
     * إجمالي المبالغ المصروفة والمدفوعة للموظف تاريخياً
     */
    public function getTotalPaidAttribute(): float
    {
        if ($this->relationLoaded('payrolls')) {
            return (float) $this->payrolls->sum('paid_amount');
        }

        return (float) $this->payrolls()->reorder()->sum('paid_amount');
    }

    /**
     * إجمالي المستحقات غير المدفوعة للموظف حالياً
     */
    public function getTotalDuesAttribute(): float
    {
        if ($this->relationLoaded('payrolls')) {
            return (float) $this->payrolls
                ->whereIn('status', ['pending', 'partial'])
                ->sum(fn ($p) => max(0.0, (float) $p->net_salary - (float) $p->paid_amount));
        }

        return (float) $this->payrolls()
            ->reorder()
            ->whereIn('status', ['pending', 'partial'])
            ->sum(DB::raw('net_salary - paid_amount'));
    }

    /**
     * إجمالي المكافآت التراكمية
     */
    public function getTotalBonusAttribute(): float
    {
        if ($this->relationLoaded('adjustments')) {
            return (float) $this->adjustments->where('type', 'bonus')->sum('amount');
        }

        return (float) $this->adjustments()->where('type', 'bonus')->sum('amount');
    }

    /**
     * إجمالي الخصومات التراكمية
     */
    public function getTotalDeductionsAttribute(): float
    {
        if ($this->relationLoaded('adjustments')) {
            return (float) $this->adjustments->where('type', 'deduction')->sum('amount');
        }

        return (float) $this->adjustments()->where('type', 'deduction')->sum('amount');
    }

    /**
     * سجل راتب الشهر الحالي
     */
    public function getCurrentMonthPayrollAttribute(): ?EmployeePayroll
    {
        $currentMonth = Carbon::now()->format('Y-m');
        return $this->payrolls()->where('salary_month', $currentMonth)->first();
    }

    /**
     * حالة راتب الشهر الحالي (مدفوع / مستحق / جزئي / غير مسجل)
     */
    public function getCurrentMonthStatusAttribute(): string
    {
        $current = $this->current_month_payroll;
        return $current ? $current->status : 'pending';
    }
}
