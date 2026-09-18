<?php

namespace App\Services;

use App\Events\PrinterJobCreated;
use App\Models\Order;
use App\Models\PrinterJob;
use Illuminate\Support\Facades\Log;

class PrintingService
{
    public function createKitchenTicket(Order $order): void
    {
        $order->loadMissing(['items.menu', 'table']);

        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->menu?->name ?? 'صنف',
                'quantity' => $item->quantity,
                'notes' => $item->notes,
            ];
        })->values()->toArray();

        $this->createJob($order, 'kitchen', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type,
            'table' => $order->table_number ?? $order->table?->number,
            'notes' => $order->notes,
            'items' => $items,
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
        ]);
    }

    public function createWaiterTicket(Order $order): void
    {
        $order->loadMissing(['items.menu', 'table']);

        $this->createJob($order, 'waiter', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'table' => $order->table_number ?? $order->table?->number,
            'type' => $order->type,
        ]);
    }

    public function createCustomerReceipt(Order $order): void
    {
        $order->loadMissing(['items.menu', 'table', 'invoice', 'creator']);

        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->menu?->name ?? 'صنف',
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'total' => (float) ($item->price * $item->quantity),
                'notes' => $item->notes,
            ];
        })->values()->toArray();

        $subtotal = collect($items)->sum('total');
        $discount = (float) ($order->discount ?? 0);
        $total = max(0, $subtotal - $discount);

        $this->createJob($order, 'customer', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'invoice' => $order->invoice?->invoice_number,
            'table' => $order->table_number ?? $order->table?->number,
            'type' => $order->type,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'cashier_name' => $order->creator?->name ?? 'الكاشير',
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
        ]);
    }

    public function createDeliveryTicket(Order $order): void
    {
        $order->loadMissing(['items.menu']);

        $this->createJob($order, 'delivery', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'phone' => $order->phone,
            'address' => $order->delivery_address,
            'driver' => $order->delivery_person,
            'notes' => $order->notes,
        ]);
    }

    private function createJob(Order $order, string $type, array $payload): void
    {
        $job = PrinterJob::create([
            'order_id' => $order->id,
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
        ]);

        try {
            PrinterJobCreated::dispatch($job);
        } catch (\Throwable $e) {
            Log::warning('Failed to dispatch PrinterJobCreated event: ' . $e->getMessage());
        }
    }
}
