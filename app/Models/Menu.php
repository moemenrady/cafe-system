<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    // بما أن اسم الجدول في قاعدة البيانات هو menu_items
    protected $table = 'menu';

    protected $fillable = ['name', 'category_id', 'price', 'image', 'is_available'];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'menu_item_id', 'id');
    }
}
