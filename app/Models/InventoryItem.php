<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    // السماح بإدخال هذه الحقول مباشرة من الفورم
    protected $fillable = ['name', 'quantity', 'unit', 'reorder_level'];
}
