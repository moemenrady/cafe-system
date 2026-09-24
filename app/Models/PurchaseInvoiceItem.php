<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'inventory_item_id',
        'item_name',
        'unit',
        'quantity',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saving(function ($item) {
            if (is_null($item->subtotal) || $item->subtotal == 0) {
                $item->subtotal = round(((float) ($item->quantity ?? 0)) * ((float) ($item->unit_price ?? 0)), 2);
            }
        });
    }

    /**
     * الفاتورة الأم التابع لها الصنف
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    /**
     * المادة الخام المرتبطة في المخزن
     */
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
