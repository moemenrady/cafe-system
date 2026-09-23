<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Menu extends Model
{
    use HasFactory;

    // اسم الجدول في قاعدة البيانات
    protected $table = 'menu';

    protected $fillable = [
        'name',
        'category_id',
        'price',
        'image',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'price'        => 'decimal:2',
    ];

    protected $appends = [
        'image_url',
    ];

    /**
     * رابط الصورة الجاهز للعرض في المتصفح والـ POS
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'menu_item_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getRecipeCostAttribute()
    {
        return $this->recipes->sum(function ($recipe) {
            return $recipe->quantity_used * ($recipe->inventoryItem->unit_price ?? 0);
        });
    }
}
