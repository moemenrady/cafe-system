<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterJob extends Model
{
    protected $fillable = [
        'uuid',
        'device_uuid',
        'printer_identifier',
        'type',
        'order_id',
        'payload',
        'status',
        'copies',
        'printer_name',
    ];

    protected $casts = [

        'payload' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
