<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'total',
        'discount',
        'client_id',
        'profit',
        'payment_method',
        'status',
        'order_id',
        'shift_id',
        'created_by',
        'note',
        'refunded_at',
        'refund_reason',
        'refunded_by',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount' => 'decimal:2',
        'profit' => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function refunder()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function client()
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function transactions()
    {
        return $this->hasMany(InvoiceTransaction::class);
    }
}