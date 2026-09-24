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

            $this->handlePostOrderWorkflow($order, $data);

            $freshOrder = $order->fresh(['items.menu', 'table', 'customer']);
            app(ShiftActionService::class)->logOrder($freshOrder, 'order_created');

            return $freshOrder;
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

            // معالجة بيانات العميل الاختيارية لربط الزيارة والفاتورة
            $customerId = $paymentData['customer_id'] ?? null;
            $customerPhone = trim((string) ($paymentData['customer_phone'] ?? $paymentData['phone'] ?? ''));
            $customerName = trim((string) ($paymentData['customer_name'] ?? ''));

            if (!$customerId && !empty($customerPhone)) {
                $customer = \App\Models\Customer::where('phone', $customerPhone)->first();
                if (!$customer) {
                    $customer = \App\Models\Customer::create([
                        'name'  => $customerName ?: ('عميل ' . substr($customerPhone, -4)),
                        'phone' => $customerPhone,
                    ]);
                } elseif (!empty($customerName) && (empty($customer->name) || str_starts_with($customer->name, 'عميل '))) {
                    $customer->update(['name' => $customerName]);
                }
                $customerId = $customer->id;
            } elseif (!$customerId && !empty($customerName)) {
                $customer = \App\Models\Customer::create([
                    'name' => $customerName,
                ]);
                $customerId = $customer->id;
            }

            if ($customerId) {
                $lockedOrder->customer_id = $customerId;
                if (!empty($customerPhone)) {
                    $lockedOrder->phone = $customerPhone;
                }
                $lockedOrder->save();
            }

            if (in_array($lockedOrder->type, ['dine_in', 'delivery'], true)) {
                $this->invoiceService->createInvoice($lockedOrder, $paymentData);
            }

            $lockedOrder->update([
                'payment_status' => 'paid',
                'payment_method' => $paymentData['payment_method'] ?? $lockedOrder->payment_method ?? 'cash',
                'status'         => 'completed',
            ]);

            $this->printingService->createCustomerReceipt($lockedOrder);
        });
    }

    private function createOrderRecord(array $data): Order
    {
        $userId = Auth::check() ? Auth::id() : 1;
        $shiftId = $data['shift_id'] ?? \App\Models\Shift::where('user_id', $userId)->where('status', 'open')->latest('id')->value('id');

        return Order::create([
            'order_number'    => DocumentSequenceService::getNextOrderNumber(),
            'customer_id'     => $data['customer_id'] ?? null,
            'table_id'        => $data['table_id'] ?? null,
            'table_number'    => $data['table_id'] ?? $data['table_number'] ?? null,
            'shift_id'        => $shiftId,
            'delivery_address' => $data['delivery_address'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'delivery_person' => $data['delivery_person'] ?? null,
            'type'            => $this->resolveOrderType($data['type'] ?? null),
            'status'          => $this->resolveInitialStatus($data['type'] ?? null),
            'payment_status'  => 'pending',
            'payment_method'  => $data['payment_method'] ?? 'cash',
            'discount'        => (float) ($data['discount'] ?? 0),
            'notes'           => $data['notes'] ?? null,
            'created_by'      => $userId,
        ]);
    }

    private function handlePostOrderWorkflow(Order $order, array $data = []): void
    {
        match ($order->type) {
            'dine_in' => $this->handleDineInWorkflow($order),
            'takeaway' => $this->handleTakeawayWorkflow($order, $data),
            'delivery' => $this->handleDeliveryWorkflow($order),
            default => throw new InvalidArgumentException('Unknown order type'),
        };
    }

    private function handleDineInWorkflow(Order $order): void
    {
        $this->printingService->createKitchenTicket($order);
        $this->printingService->createWaiterTicket($order);
    }

    private function handleTakeawayWorkflow(Order $order, array $data = []): void
    {
        $this->invoiceService->createInvoice($order, [
            'payment_method' => $data['payment_method'] ?? $order->payment_method ?? 'cash',
            'note'           => $data['notes'] ?? $order->notes ?? null,
            'shift_id'       => $order->shift_id,
        ]);
        $this->printingService->createCustomerReceipt($order);
        $this->printingService->createKitchenTicket($order);

        $order->update([
            'payment_status' => 'paid',
            'payment_method' => $data['payment_method'] ?? $order->payment_method ?? 'cash',
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
