<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $guarded = [];

    // الفاتورة الأم
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    // المنتج المرتبط من المنيو
    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }
}