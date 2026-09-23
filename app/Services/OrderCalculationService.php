<?php

namespace App\Services;

use App\Models\Order;

class OrderCalculationService
{
    public const DINE_IN_VAT_RATE = 0.14; // 14% ضريبة القيمة المضافة للصالة فقط

    /**
     * حساب المبالغ بدقة وفق القواعد المالية الموحدة حسب نوع الطلب
     *
     * @param float $subtotal مجموع قيم الأصناف قبل الخصم والضريبة
     * @param float $discount قيمة الخصم المالي
     * @param string $orderType نوع الطلب: dine_in (14%), takeaway (0%), delivery (0%)
     * @return array{subtotal: float, discount: float, taxable_amount: float, vat: float, vat_rate: float, total: float}
     */
    public function calculate(float $subtotal, float $discount = 0.0, string $orderType = 'dine_in'): array
    {
        $subtotal = round(max(0, $subtotal), 2);
        $discount = round(min($subtotal, max(0, $discount)), 2);
        $taxableAmount = round(max(0, $subtotal - $discount), 2);

        // الصالة 14% - التيك أواي 0% - الديليفري 0%
        $isDineIn = ($orderType === 'dine_in');
        $vatRatePercent = $isDineIn ? 14.0 : 0.0;
        $vatRateDecimal = $isDineIn ? self::DINE_IN_VAT_RATE : 0.0;

        $vat = round($taxableAmount * $vatRateDecimal, 2);
        $total = round($taxableAmount + $vat, 2);

        return [
            'subtotal'       => $subtotal,
            'discount'       => $discount,
            'taxable_amount' => $taxableAmount,
            'vat'            => $vat,
            'vat_rate'       => $vatRatePercent,
            'total'          => $total,
        ];
    }

    /**
     * إعادة حساب أوردر وتحديث حقوله في قاعدة البيانات تلقائياً
     */
    public function recalculateOrder(Order $order, ?float $overrideDiscount = null): Order
    {
        $order->loadMissing('items');

        $subtotal = (float) $order->items->sum(function ($item) {
            return (float) ($item->total ?: ($item->price * $item->quantity));
        });

        $discount = $overrideDiscount !== null
            ? (float) $overrideDiscount
            : (float) ($order->discount ?? 0);

        $orderType = $order->type ?? 'dine_in';
        $calc = $this->calculate($subtotal, $discount, $orderType);

        $order->update([
            'subtotal' => $calc['subtotal'],
            'discount' => $calc['discount'],
            'vat'      => $calc['vat'],
            'total'    => $calc['total'],
        ]);

        return $order;
    }
}
