<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Table extends Model
{
    protected $fillable = [
        'name',
        'capacity',
        'area',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'capacity'  => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** الطاولات النشطة فقط – هي اللي بتظهر في الـ POS */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** كل الطلبات المرتبطة بالطاولة دي */
    public function orders()
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    /** الطلبات المفتوحة (الصالة مشغولة) */
    public function activeOrders()
    {
        return $this->hasMany(Order::class, 'table_id')
            ->whereIn('status', ['open', 'waiting_delivery'])
            ->where('payment_status', 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * هل الطاولة مشغولة دلوقتي؟
     * المصدر الوحيد للحقيقة هو نظام الأوردرات الموجود.
     */
    public function isOccupied(): bool
    {
        return $this->activeOrders()->exists();
    }
}
