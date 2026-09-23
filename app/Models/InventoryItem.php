<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'category',
        'quantity',
        'unit',
        'reorder_level',
        'unit_price',
    ];

    protected $casts = [
        'unit_price'    => 'decimal:2',
    ];

    protected $appends = [
        'total_value',
        'status',
        'item_code',
    ];

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'inventory_item_id');
    }

    /**
     * القيمة الإجمالية (الرصيد × تكلفة الوحدة)
     */
    public function getTotalValueAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->unit_price, 2);
    }

    /**
     * كود الصنف (إما الكود المسجل أو كود تسلسلي ITM-001)
     */
    public function getItemCodeAttribute(): string
    {
        if (!empty($this->code)) {
            return $this->code;
        }

        return 'ITM-' . str_pad((string) ($this->id ?? 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * حالة المخزون: متوفر / منخفض / نفد
     */
    public function getStatusAttribute(): string
    {
        if ((float) $this->quantity <= 0) {
            return 'نفد';
        }

        if ((float) $this->quantity <= (float) $this->reorder_level) {
            return 'منخفض';
        }

        return 'متوفر';
    }

    /**
     * لون الحالة للعرض في الجداول والتقارير
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'نفد'     => 'red',
            'منخفض'   => 'yellow',
            default  => 'green',
        };
    }
}
