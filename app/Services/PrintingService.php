<?php

namespace App\Services;

use App\Events\PrinterJobCreated;
use App\Models\Order;
use App\Models\PrinterJob;
use Illuminate\Support\Facades\Log;

class PrintingService
{
    public function createKitchenTicket(Order $order): ?PrinterJob
    {
        $order->loadMissing(['items.menu', 'table']);

        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->menu?->name ?? 'صنف',
                'quantity' => $item->quantity,
                'notes' => $item->notes,
            ];
        })->values()->toArray();

        $tableName = $order->table?->name ?? $order->table_number ?? null;

        return $this->createJob($order, 'kitchen', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type,
            'table' => $tableName,
            'table_name' => $tableName,
            'notes' => $order->notes,
            'items' => $items,
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
        ]);
    }

    public function createKitchenTicketForItems(Order $order, iterable $items): ?PrinterJob
    {
        $order->loadMissing('table');

        $formattedItems = collect($items)->map(function ($item) {
            $name = is_array($item) ? ($item['name'] ?? null) : ($item->menu?->name ?? null);
            if (!$name && is_object($item) && isset($item->menu_id)) {
                $name = \App\Models\Menu::find($item->menu_id)?->name;
            }
            return [
                'name' => $name ?? 'صنف إضافي',
                'quantity' => is_array($item) ? ($item['quantity'] ?? 1) : $item->quantity,
                'notes' => is_array($item) ? ($item['notes'] ?? null) : $item->notes,
            ];
        })->values()->toArray();

        if (empty($formattedItems)) {
            return null;
        }

        $tableName = $order->table?->name ?? $order->table_number ?? null;

        return $this->createJob($order, 'kitchen', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'type' => $order->type,
            'table' => $tableName,
            'table_name' => $tableName,
            'notes' => 'طلبات إضافية للطاولة',
            'items' => $formattedItems,
            'is_addon' => true,
            'created_at' => now()->format('Y-m-d H:i'),
        ]);
    }

    public function createWaiterTicket(Order $order): ?PrinterJob
    {
        $order->loadMissing(['items.menu', 'table']);

        $tableName = $order->table?->name ?? $order->table_number ?? null;

        return $this->createJob($order, 'waiter', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'table' => $tableName,
            'table_name' => $tableName,
            'type' => $order->type,
        ]);
    }

    public function createCustomerReceipt(Order $order, string $printType = 'customer'): ?PrinterJob
    {
        $order->loadMissing(['items.menu', 'table', 'invoice', 'creator', 'customer']);

        $items = $order->items->map(function ($item) {
            return [
                'name' => $item->menu?->name ?? 'صنف',
                'quantity' => $item->quantity,
                'price' => (float) $item->price,
                'total' => (float) ($item->total ?: ($item->price * $item->quantity)),
                'notes' => $item->notes,
            ];
        })->values()->toArray();

        $subtotal = (float) ($order->subtotal ?? collect($items)->sum('total'));
        $discount = (float) ($order->discount ?? 0);
        $isDineIn = ($order->type === 'dine_in');
        $taxRate = $isDineIn ? 14 : 0;
        $tax = (float) ($order->vat ?? ($isDineIn ? round(max(0, $subtotal - $discount) * 0.14, 2) : 0.0));
        $total = (float) ($order->total ?? round(max(0, $subtotal - $discount) + $tax, 2));

        $tableName = $order->table?->name ?? $order->table_number ?? null;
        $customerName = $order->customer?->name ?? null;
        $customerPhone = $order->customer?->phone ?? $order->phone ?? null;
        $invoiceNumber = $order->invoice?->invoice_number;

        return $this->createJob($order, 'customer', [
            'order_id' => $order->id,
            'invoice_id' => $order->invoice?->id,
            'order_number' => $order->order_number,
            'invoice' => $invoiceNumber,
            'invoice_number' => $invoiceNumber,
            'table_id' => $order->table_id,
            'table' => $tableName,
            'table_name' => $tableName,
            'customer_name' => $customerName,
            'customer_phone' => $customerPhone,
            'type' => $order->type,
            'print_type' => $printType,
            'items' => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'tax' => $tax,
            'tax_amount' => $tax,
            'total' => $total,
            'payment_method' => $order->invoice?->payment_method ?? 'cash',
            'cashier_name' => $order->creator?->name ?? 'الكاشير',
            'created_at' => $order->created_at?->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i'),
        ]);
    }

    public function createDeliveryTicket(Order $order): ?PrinterJob
    {
        $order->loadMissing(['items.menu']);

        return $this->createJob($order, 'delivery', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'phone' => $order->phone,
            'address' => $order->delivery_address,
            'driver' => $order->delivery_person,
            'notes' => $order->notes,
        ]);
    }

    private function createJob(Order $order, string $type, array $payload, ?string $deviceUuid = null): ?PrinterJob
    {
        $printerIdentifier = in_array($type, ['kitchen', 'barista', 'waiter']) ? 'barista' : 'cashier';
        $jobUuid = 'job-' . (string) \Illuminate\Support\Str::uuid();

        $job = PrinterJob::create([
            'uuid' => $jobUuid,
            'device_uuid' => $deviceUuid ?: 'pos-cashier-01',
            'printer_identifier' => $printerIdentifier,
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

        return $job;
    }
}
