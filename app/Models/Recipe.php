<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = ['menu_item_id', 'inventory_item_id', 'quantity_used'];

    public function menuItem()
    {
        return $this->belongsTo(Menu::class, 'menu_item_id');
    }

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
    public function getIngredientCostAttribute()
    {
        if (!$this->inventoryItem) {
            return 0;
        }

        return $this->quantity_used * $this->inventoryItem->unit_price;
    }
}
