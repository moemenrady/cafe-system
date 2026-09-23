<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift_id',
        'user_id',
        'action_type',
        'action_title',
        'model_type',
        'model_id',
        'amount',
        'payment_method',
        'details',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'details' => 'array',
    ];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function model()
    {
        return $this->morphTo();
    }
}
