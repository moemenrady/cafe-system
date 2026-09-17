<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PrintJob extends Model
{
    protected $fillable = [
        'type',
        'payload',
        'status',
        'started_at',
        'printed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'started_at' => 'datetime',
        'printed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (PrintJob $job) {
            $job->uuid ??= (string) Str::uuid();
        });
    }
}