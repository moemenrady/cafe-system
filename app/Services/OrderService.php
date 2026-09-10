<?php

namespace App\Services;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        protected OrderItemService $orderItemService,
        protected InventoryService $inventoryService,
        protected PrintingService $printingService,
        protected InvoiceService $invoiceService
    ) {}

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = $this->createOrderRecord($data);

            $this->orderItemService->createItems($order, $data['items'] ?? []);
            $this->inventoryService->updateInventory($order);

            $this->handlePostOrderWorkflow($order);

            return $order->fresh(['items']);
        });
    }

    public function checkout(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if ($order->payment_status === 'paid') {
                throw new InvalidArgumentException('Order already paid.');
            }

            if (in_array($order->type, ['dine_in', 'delivery'], true)) {
                $this->invoiceService->createInvoice($order);
            }

            $order->update([
                'payment_status' => 'paid',
                'status' => 'completed',
            ]);

            $this->printingService->createCustomerReceipt($order);
        });
    }

    private function createOrderRecord(array $data): Order
    {
        return Order::create([
            'order_number' => $this->generateOrderNumber(),
            'customer_id' => $data['customer_id'] ?? null,
            'table_number' => $data['table_number'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'delivery_person' => $data['delivery_person'] ?? null,
            'type' => $this->resolveOrderType($data['type'] ?? null),
            'status' => $this->resolveInitialStatus($data['type'] ?? null),
            'payment_status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_by' => Auth::check() ? Auth::id() : 1,
        ]);
    }

    private function handlePostOrderWorkflow(Order $order): void
    {
        match ($order->type) {
            'dine_in' => $this->handleDineInWorkflow($order),
            'takeaway' => $this->handleTakeawayWorkflow($order),
            'delivery' => $this->handleDeliveryWorkflow($order),
            default => throw new InvalidArgumentException('Unknown order type'),
        };
    }

    private function handleDineInWorkflow(Order $order): void
    {
        $this->printingService->createKitchenTicket($order);
        $this->printingService->createWaiterTicket($order);
    }

    private function handleTakeawayWorkflow(Order $order): void
    {
        $this->invoiceService->createInvoice($order);
        $this->printingService->createCustomerReceipt($order);
        $this->printingService->createKitchenTicket($order);

        $order->update([
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
    }

    private function handleDeliveryWorkflow(Order $order): void
    {
        $this->printingService->createKitchenTicket($order);
        $this->printingService->createDeliveryTicket($order);
    }

    private function resolveOrderType(?string $type): string
    {
        return match ($type) {
            'dine_in', 'takeaway', 'delivery' => $type,
            default => throw new InvalidArgumentException('Unsupported order type.'),
        };
    }

    private function resolveInitialStatus(?string $type): string
    {
        return match ($type) {
            'delivery' => 'waiting_delivery',
            default => 'open',
        };
    }

    private function generateOrderNumber(): string
    {
        $today = Carbon::today();
        $lastOrder = Order::whereDate('created_at', $today)->latest('id')->first();
        $nextNumber = 1;

        if ($lastOrder) {
            $lastSequence = (int) substr($lastOrder->order_number, -4);
            $nextNumber = $lastSequence + 1;
        }

        return sprintf('ORD-%s-%04d', $today->format('Ymd'), $nextNumber);
    }
}
