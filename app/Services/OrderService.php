<?php

namespace App\Services;

use App\Models\Order;
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

            $this->orderItemService->createItems(
                $order,
                $data['items'] ?? [],
                (float) ($data['discount'] ?? 0),
                (float) ($data['service_charge'] ?? 0),
                (float) ($data['vat_rate'] ?? 0)
            );

            $this->inventoryService->updateInventory($order);

            $this->handlePostOrderWorkflow($order);

            return $order->fresh(['items']);
        });
    }

    public function checkout(Order $order, array $paymentData = []): void
    {
        DB::transaction(function () use ($order, $paymentData) {
            /** @var Order $lockedOrder */
            $lockedOrder = Order::where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedOrder->payment_status === 'paid') {
                throw new InvalidArgumentException('Order already paid.');
            }

            if ($lockedOrder->status === 'cancelled') {
                throw new InvalidArgumentException('Cannot checkout a cancelled order.');
            }

            if (in_array($lockedOrder->type, ['dine_in', 'delivery'], true)) {
                $this->invoiceService->createInvoice($lockedOrder, $paymentData);
            }

            $lockedOrder->update([
                'payment_status' => 'paid',
                'status'         => 'completed',
            ]);

            $this->printingService->createCustomerReceipt($lockedOrder);
        });
    }

    private function createOrderRecord(array $data): Order
    {
        return Order::create([
            'order_number'    => DocumentSequenceService::getNextOrderNumber(),
            'customer_id'     => $data['customer_id'] ?? null,
            'table_id'        => $data['table_id'] ?? null,
            'table_number'    => $data['table_id'] ?? $data['table_number'] ?? null,
            'shift_id'        => $data['shift_id'] ?? null,
            'delivery_address' => $data['delivery_address'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'delivery_person' => $data['delivery_person'] ?? null,
            'type'            => $this->resolveOrderType($data['type'] ?? null),
            'status'          => $this->resolveInitialStatus($data['type'] ?? null),
            'payment_status'  => 'pending',
            'discount'        => (float) ($data['discount'] ?? 0),
            'notes'           => $data['notes'] ?? null,
            'created_by'      => Auth::check() ? Auth::id() : 1,
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
}
