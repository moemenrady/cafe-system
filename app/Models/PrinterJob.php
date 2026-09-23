<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrinterJob extends Model
{
    protected $fillable = [
        'uuid',
        'device_uuid',
        'type',
        'order_id',
        'payload',
        'status',
        'copies',
        'printer_name',
        'attempts',
        'started_at',
        'printed_at',
        'error_message',
    ];

    protected $casts = [
        'payload'    => 'array',
        'started_at' => 'datetime',
        'printed_at' => 'datetime',
        'attempts'   => 'integer',
        'copies'     => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrinterJob $job) {
            $job->uuid ??= (string) Str::uuid();
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
