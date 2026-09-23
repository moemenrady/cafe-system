<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'shift_id',
        'title',
        'amount',
        'notes',
        'created_by',
        'expense_date',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    /**
     * Scope لعزل السجلات: إذا كان المستخدم موظفاً يرى فقط سجلاته، وإذا كان مديراً يرى الكل
     */
    public function scopeAccessibleToUser($query, ?User $user = null)
    {
        $user = $user ?: auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // المدراء والمشرفون يرون جميع المصروفات
        if (in_array($user->role, ['admin', 'supervisor'], true)) {
            return $query;
        }

        // الموظف يرى فقط ما أنشأه بحسابه
        return $query->where('created_by', $user->id);
    }
}
