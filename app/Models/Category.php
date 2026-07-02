<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // السماح بإدخال اسم القسم مباشرة من الفورم
    protected $fillable = ['name'];

    /**
     * علاقة القسم بمنتجات المينيو
     */
    public function menuItems()
    {
        return $this->hasMany(Menu::class);
    }
}
