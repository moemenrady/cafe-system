<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // الشخص اللي عمل الأكشن (تعديل/حذف)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}