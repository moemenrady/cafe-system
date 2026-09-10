<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PrinterJob;
use InvalidArgumentException;

class PrintingService
{
    public function createKitchenTicket(Order $order): void
    {
        $this->createJob($order, 'kitchen', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);
    }

    public function createWaiterTicket(Order $order): void
    {
        $this->createJob($order, 'waiter', [
            'table' => $order->table_number,
        ]);
    }

    public function createCustomerReceipt(Order $order): void
    {
        $this->createJob($order, 'customer', [
            'invoice' => $order->invoice?->invoice_number,
        ]);
    }

    public function createDeliveryTicket(Order $order): void
    {
        $this->createJob($order, 'delivery', [
            'phone' => $order->phone,
            'address' => $order->delivery_address,
            'driver' => $order->delivery_person,
        ]);
    }

    private function createJob(Order $order, string $type, array $payload): void
    {
        PrinterJob::create([
            'order_id' => $order->id,
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }
}
