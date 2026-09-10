<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [

        'order_number',
        'customer_id',
        'table_number',
        'delivery_address',
        'phone',
        'delivery_person',

        'type',
        'status',
        'payment_status',

        'subtotal',
        'discount',
        'service_charge',
        'vat',
        'total',

        'notes',

        'created_by',
    ];

    protected $casts = [

        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'vat' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}
