<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_id',
        'salary_month',
        'type',
        'amount',
        'reason',
        'date',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date'   => 'date',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(EmployeePayroll::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeBadgeAttribute(): array
    {
        if ($this->type === 'bonus') {
            return [
                'label' => 'مكافأة / بونص',
                'class' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20',
                'icon'  => 'fa-solid fa-gift',
                'sign'  => '+',
            ];
        }

        return [
            'label' => 'خصم / جزاء',
            'class' => 'bg-rose-500/10 text-rose-400 border border-rose-500/20',
            'icon'  => 'fa-solid fa-circle-minus',
            'sign'  => '-',
        ];
    }
}
