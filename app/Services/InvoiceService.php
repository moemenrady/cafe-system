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
    public function createInvoice(Order $order): Invoice
    {
        return DB::transaction(function () use ($order) {
            $invoice = Invoice::create([
                'invoice_number' => InvoiceNumberHelper::generate(),
                'total' => (float) ($order->total ?? 0),
                'discount' => (float) ($order->discount ?? 0),
                'client_id' => $order->customer_id,
                'profit' => max(0, (float) ($order->total ?? 0) - (float) ($order->discount ?? 0)),
                'payment_method' => 'cash',
                'order_id' => $order->id,
                'created_by' => Auth::check() ? Auth::id() : 1,
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'menu_id' => $item->menu_id,
                    'quantity' => $item->quantity,
                    'item_price' => $item->price,
                    'total' => $item->total,
                ]);
            }

            return $invoice;
        });
    }
}
