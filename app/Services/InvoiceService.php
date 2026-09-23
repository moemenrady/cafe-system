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
    public function createInvoice(Order $order, array $paymentData = []): Invoice
    {
        return DB::transaction(function () use ($order, $paymentData) {
            $order->loadMissing('items');

            $paymentMethod = $paymentData['payment_method'] ?? 'cash';
            $shiftId = $paymentData['shift_id'] ?? $order->shift_id ?? null;
            $note = $paymentData['note'] ?? $order->notes ?? null;

            $total = (float) ($order->total ?? 0);
            $discount = (float) ($order->discount ?? 0);

            $invoice = Invoice::create([
                'invoice_number' => InvoiceNumberHelper::generate(),
                'total'          => $total,
                'discount'       => $discount,
                'client_id'      => $order->customer_id,
                'profit'         => max(0, $total),
                'payment_method' => $paymentMethod,
                'order_id'       => $order->id,
                'shift_id'       => $shiftId,
                'note'           => $note,
                'created_by'     => Auth::check() ? Auth::id() : ($order->created_by ?: 1),
            ]);

            $invoiceItems = [];
            foreach ($order->items as $item) {
                $invoiceItems[] = [
                    'invoice_id' => $invoice->id,
                    'menu_id'    => $item->menu_id,
                    'quantity'   => $item->quantity,
                    'item_price' => $item->price,
                    'total'      => $item->total,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($invoiceItems)) {
                InvoiceItem::insert($invoiceItems);
            }

            return $invoice;
        });
    }
}
