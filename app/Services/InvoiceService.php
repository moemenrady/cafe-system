<?php

namespace App\Services;

use App\Helpers\InvoiceNumberHelper;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * إنشاء أو جلب الفاتورة الحالية للأوردر مع ضمان عدم التكرار (Idempotency)
     */
    public function createInvoice(Order $order, string $paymentMethod = 'cash'): Invoice
    {
        return DB::transaction(function () use ($order, $paymentMethod) {
            $order->loadMissing('items');

            // 1. فحص وجود فاتورة سابقة لنفس الطلب
            $existingInvoice = Invoice::where('order_id', $order->id)->first();

            $subtotal = (float) ($order->subtotal ?? 0);
            $discount = (float) ($order->discount ?? 0);
            $vat      = (float) ($order->vat ?? 0);
            $total    = (float) ($order->total ?? 0);

            if ($existingInvoice) {
                // تحديث الفاتورة القائمة إذا تغيرت الأصناف أو المبالغ دون تغيير رقم الفاتورة
                $existingInvoice->update([
                    'subtotal'       => $subtotal,
                    'discount'       => $discount,
                    'vat'            => $vat,
                    'total'          => $total,
                    'client_id'      => $order->customer_id ?: $existingInvoice->client_id,
                    'payment_method' => $paymentMethod ?: $existingInvoice->payment_method,
                    'profit'         => max(0, $total - $discount),
                ]);

                // تحديث بنود الفاتورة لتطابق أصناف الطلب
                $existingInvoice->items()->delete();
                foreach ($order->items as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $existingInvoice->id,
                        'menu_id'    => $item->menu_id,
                        'quantity'   => $item->quantity,
                        'item_price' => $item->price,
                        'total'      => $item->total,
                    ]);
                }

                return $existingInvoice->fresh(['items', 'client']);
            }

            // 2. إنشاء فاتورة جديدة إذا لم تكن موجودة
            $invoice = Invoice::create([
                'invoice_number' => InvoiceNumberHelper::generate(),
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'vat'            => $vat,
                'total'          => $total,
                'client_id'      => $order->customer_id,
                'profit'         => max(0, $total - $discount),
                'payment_method' => $paymentMethod ?: 'cash',
                'order_id'       => $order->id,
                'created_by'     => Auth::check() ? Auth::id() : ($order->created_by ?: 1),
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'menu_id'    => $item->menu_id,
                    'quantity'   => $item->quantity,
                    'item_price' => $item->price,
                    'total'      => $item->total,
                ]);
            }

            return $invoice->fresh(['items', 'client']);
        });
    }
}
