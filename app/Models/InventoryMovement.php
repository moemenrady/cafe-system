<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    protected $fillable = [
        'inventory_item_id',
        'type',
        'quantity',
        'balance_after',
        'invoice_id',
        'user_id'
    ];

    // علاقة الحركة بالصنف في المخزن
    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    // علاقة الحركة بالفاتورة الصادرة (إن وجدت)
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // علاقة الحركة بالموظف الذي قام بالعملية
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}