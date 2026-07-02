<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = []; // للسماح بالإدخال الجماعي

    // العميل صاحب الفاتورة (لو موجود)
    public function client()
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    // الموظف اللي عمل الفاتورة
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // تفاصيل المنتجات اللي جوة الفاتورة
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    // السجل الرقابي للحركات اللي تمت على الفاتورة
    public function transactions()
    {
        return $this->hasMany(InvoiceTransaction::class);
    }
}