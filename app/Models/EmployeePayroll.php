<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeePayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_month',
        'base_salary',
        'total_bonus',
        'total_deductions',
        'net_salary',
        'paid_amount',
        'status',
        'payment_date',
        'payment_method',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'base_salary'      => 'decimal:2',
        'total_bonus'      => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary'       => 'decimal:2',
        'paid_amount'      => 'decimal:2',
        'payment_date'     => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(EmployeeAdjustment::class, 'payroll_id');
    }

    /**
     * المبلغ المتبقي المستحق للشهر
     */
    public function getRemainingDueAttribute(): float
    {
        return max(0.0, (float) $this->net_salary - (float) $this->paid_amount);
    }

    /**
     * اسم الشهر بالعربية
     */
    public function getMonthNameArAttribute(): string
    {
        $months = [
            '01' => 'يناير',
            '02' => 'فبراير',
            '03' => 'مارس',
            '04' => 'أبريل',
            '05' => 'مايو',
            '06' => 'يونيو',
            '07' => 'يوليو',
            '08' => 'أغسطس',
            '09' => 'سبتمبر',
            '10' => 'أكتوبر',
            '11' => 'نوفمبر',
            '12' => 'ديسمبر',
        ];

        $parts = explode('-', (string) $this->salary_month);
        if (count($parts) === 2) {
            $year = $parts[0];
            $month = $months[$parts[1]] ?? $parts[1];
            return "{$month} {$year}";
        }

        return $this->salary_month;
    }
}
