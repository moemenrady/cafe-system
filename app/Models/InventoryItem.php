<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'quantity', 'unit', 'reorder_level', 'unit_price'];

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'inventory_item_id');
    }
}
