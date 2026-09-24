<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'email',
        'notes',
    ];

    /**
     * طلبات العميل
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * فواتير مبيعات العميل
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    /**
     * إجمالي إنفاق ومدفوعات العميل
     */
    public function getTotalSpentAttribute(): float
    {
        if (isset($this->attributes['invoices_sum_total'])) {
            return (float) $this->attributes['invoices_sum_total'];
        }

        // الأولوية لفواتير المبيعات الفعلية
        $invoicesSum = (float) $this->invoices()->sum('total');
        if ($invoicesSum > 0) {
            return $invoicesSum;
        }

        // كبديل: الطلبات المدفوعة
        return (float) $this->orders()->where('payment_status', 'paid')->sum('total');
    }

    /**
     * إجمالي عدد الزيارات والطلبات
     */
    public function getVisitsCountAttribute(): int
    {
        if (isset($this->attributes['invoices_count'])) {
            return (int) $this->attributes['invoices_count'];
        }

        $invCount = $this->invoices()->count();
        if ($invCount > 0) {
            return $invCount;
        }

        return $this->orders()->count();
    }

    /**
     * متوسط قيمة الفاتورة للعميل
     */
    public function getAverageOrderValueAttribute(): float
    {
        $visits = $this->visits_count;
        if ($visits <= 0) {
            return 0.0;
        }

        return round($this->total_spent / $visits, 2);
    }

    /**
     * تاريخ وتوقيت آخر زيارة / فاتورة
     */
    public function getLastVisitDateAttribute(): ?Carbon
    {
        if (isset($this->attributes['last_invoice_at']) && $this->attributes['last_invoice_at']) {
            return Carbon::parse($this->attributes['last_invoice_at']);
        }

        $latestInvoice = $this->invoices()->latest('created_at')->first();
        if ($latestInvoice) {
            return $latestInvoice->created_at;
        }

        $latestOrder = $this->orders()->latest('created_at')->first();
        if ($latestOrder) {
            return $latestOrder->created_at;
        }

        return $this->created_at;
    }

    /**
     * تاريخ أول زيارة للعميل
     */
    public function getFirstVisitDateAttribute(): ?Carbon
    {
        $firstInvoice = $this->invoices()->oldest('created_at')->first();
        if ($firstInvoice) {
            return $firstInvoice->created_at;
        }

        $firstOrder = $this->orders()->oldest('created_at')->first();
        if ($firstOrder) {
            return $firstOrder->created_at;
        }

        return $this->created_at;
    }

    /**
     * طريقة الدفع المفضلة لدى العميل
     */
    public function getFavoritePaymentMethodAttribute(): ?string
    {
        $method = $this->invoices()
            ->select('payment_method', DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->orderByDesc('count')
            ->value('payment_method');

        if (!$method) {
            $method = $this->orders()
                ->select('payment_method', DB::raw('count(*) as count'))
                ->groupBy('payment_method')
                ->orderByDesc('count')
                ->value('payment_method');
        }

        return match (strtolower(trim((string) $method))) {
            'instapay' => 'إنستا باي (InstaPay)',
            'card', 'visa' => 'فيزا / كارت',
            'cash' => 'كاش نقدي',
            default => $method ?: 'كاش نقدي',
        };
    }

    /**
     * شارة تصنيف العميل التلقائية (VIP / دائم / جديد)
     */
    public function getVipBadgeAttribute(): array
    {
        $spent = $this->total_spent;
        $visits = $this->visits_count;

        if ($spent >= 2000 || $visits >= 15) {
            return [
                'type'  => 'vip',
                'label' => 'عميل VIP متميز ⭐',
                'class' => 'bg-amber-100 text-amber-800 border-amber-300',
                'icon'  => 'fa-solid fa-crown',
            ];
        }

        if ($spent >= 500 || $visits >= 5) {
            return [
                'type'  => 'regular',
                'label' => 'عميل دائم 🌟',
                'class' => 'bg-blue-100 text-blue-800 border-blue-300',
                'icon'  => 'fa-solid fa-star',
            ];
        }

        return [
            'type'  => 'new',
            'label' => 'عميل جديد 🌱',
            'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'icon'  => 'fa-solid fa-seedling',
        ];
    }
}
