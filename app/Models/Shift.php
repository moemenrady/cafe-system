<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'opening_float',
        'cash_sales',
        'card_sales',
        'instapay_sales',
        'cash_drops',
        'expenses_total',
        'expected_cash',
        'actual_cash',
        'difference',
        'status',
        'start_time',
        'end_time',
        'closing_notes',
    ];

    protected $casts = [
        'opening_float'  => 'decimal:2',
        'cash_sales'     => 'decimal:2',
        'card_sales'     => 'decimal:2',
        'instapay_sales' => 'decimal:2',
        'cash_drops'     => 'decimal:2',
        'expenses_total' => 'decimal:2',
        'expected_cash'  => 'decimal:2',
        'actual_cash'    => 'decimal:2',
        'difference'     => 'decimal:2',
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function actions()
    {
        return $this->hasMany(ShiftAction::class)->orderBy('created_at', 'desc');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function currentCashSales(): float
    {
        if ($this->isClosed()) {
            return (float) $this->cash_sales;
        }
        return (float) Invoice::where('shift_id', $this->id)
            ->where(function ($q) {
                $q->whereNull('payment_method')
                  ->orWhereRaw('LOWER(payment_method) = ?', ['cash']);
            })->sum('total');
    }

    public function currentCardSales(): float
    {
        if ($this->isClosed()) {
            return (float) $this->card_sales;
        }
        return (float) Invoice::where('shift_id', $this->id)
            ->where(function ($q) {
                $q->whereRaw('LOWER(payment_method) IN (?, ?)', ['card', 'visa']);
            })->sum('total');
    }

    public function currentInstaPaySales(): float
    {
        if ($this->isClosed()) {
            return (float) $this->instapay_sales;
        }
        return (float) Invoice::where('shift_id', $this->id)
            ->whereRaw('LOWER(payment_method) = ?', ['instapay'])
            ->sum('total');
    }

    public function totalSales(): float
    {
        if ($this->isOpen()) {
            return round($this->currentCashSales() + $this->currentCardSales() + $this->currentInstaPaySales(), 2);
        }
        return (float) ($this->cash_sales + $this->card_sales + $this->instapay_sales);
    }

    /**
     * التحقق مما إذا كان الشيفت قد تجاوز الساعة 12 منتصف الليل ويجب إغلاقه تلقائياً
     */
    public function shouldAutoCloseAtMidnight(): bool
    {
        if (!$this->isOpen()) {
            return false;
        }

        $now = now();
        $startTime = $this->start_time;

        if (!$startTime) {
            return false;
        }

        // إذا كان تاريخ اليوم يختلف عن تاريخ بدء الشيفت، أو تجاوز منتصف ليل يوم البدء
        $nextMidnight = $startTime->copy()->endOfDay(); // 23:59:59 of start day
        return $now->greaterThan($nextMidnight);
    }
}
