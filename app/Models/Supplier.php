<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'company_name',
        'phone',
        'email',
        'address',
        'opening_balance',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
    ];

    protected $appends = [
        'total_purchases',
        'total_paid',
        'total_due',
    ];

    /**
     * فواتير المشتريات المرتبطة بالمورد
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(PurchaseInvoice::class, 'supplier_id');
    }

    /**
     * إجمالي المشتريات من هذا المورد
     */
    public function getTotalPurchasesAttribute(): float
    {
        return round((float) $this->invoices()->sum('net_amount'), 2);
    }

    /**
     * إجمالي المبالغ المسددة للمورد
     */
    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->invoices()->sum('paid_amount'), 2);
    }

    /**
     * إجمالي المستحق للمورد (الرصيد المتبقي + الرصيد الافتتاحي)
     */
    public function getTotalDueAttribute(): float
    {
        $invoicesDue = (float) $this->invoices()->sum('remaining_amount');
        return round($invoicesDue + (float) $this->opening_balance, 2);
    }

    /**
     * عدد الفواتير المسجلة للمورد
     */
    public function getInvoicesCountAttribute(): int
    {
        return $this->invoices()->count();
    }

    /**
     * تاريخ آخر فاتورة توريد
     */
    public function getLastPurchaseDateAttribute(): ?string
    {
        $lastInvoice = $this->invoices()->orderByDesc('invoice_date')->first();
        return $lastInvoice ? $lastInvoice->invoice_date?->format('Y-m-d') : null;
    }
}
