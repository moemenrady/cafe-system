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

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
