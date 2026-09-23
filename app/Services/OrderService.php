<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderService
{
    public function __construct(
        protected OrderItemService $orderItemService,
        protected OrderCalculationService $calculationService,
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

            $order->refresh();

            $this->handlePostOrderWorkflow($order, $data);

            return $order->fresh(['items.menu', 'table', 'customer', 'invoice']);
        });
    }

    /**
     * إضافة أصناف إضافية لطلب طاولة مفتوح
     */
    public function addItemsToOrder(Order $order, array $items): Order
    {
        return DB::transaction(function () use ($order, $items) {
            if ($order->status === 'completed' || $order->payment_status === 'paid') {
                throw new InvalidArgumentException('لا يمكن إضافة أصناف لطلب مغلق بالفعل.');
            }

            if (empty($items)) {
                throw new InvalidArgumentException('يجب تحديد الأصناف المراد إضافتها.');
            }

            // 1. إنشاء الأصناف الجديدة وحساب المبالغ تلقائياً
            $createdItems = $this->orderItemService->createItems($order, $items);

            // 2. خصم المخزون للأصناف الجديدة
            $this->inventoryService->updateInventory($order);

            // 3. طباعة بون المطبخ للأصناف الإضافية فقط
            $this->printingService->createKitchenTicketForItems($order, $createdItems);

            // 4. تحديث الفاتورة القائمة إن وجدت
            if ($order->invoice()->exists()) {
                $this->invoiceService->createInvoice($order);
            }

            return $order->fresh(['items.menu', 'table', 'customer', 'invoice']);
        });
    }

    /**
     * طباعة فاتورة الحساب للعميل مع بقاء الطاولة مشغولة
     */
    public function printInvoice(Order $order): Invoice
    {
        if ($order->items()->count() === 0) {
            throw new InvalidArgumentException('لا يمكن طباعة فاتورة لطلب فارغ.');
        }

        // 1. التأكد من صحة الحسابات المالية
        $this->calculationService->recalculateOrder($order);

        // 2. إنشاء أو جلب الفاتورة الحالية دون تكرار (Idempotent)
        $invoice = $this->invoiceService->createInvoice($order);

        // 3. إرسال أمر الطباعة
        $this->printingService->createCustomerReceipt($order, 'invoice');

        return $invoice;
    }

    /**
     * إغلاق الطاولة وإتمام التحصيل المالي النهائي
     */
    public function closeTable(Order $order, array $data = []): Order
    {
        return DB::transaction(function () use ($order, $data) {
            if ($order->payment_status === 'paid' && $order->status === 'completed') {
                throw new InvalidArgumentException('الطلب مغلق ومدفوع بالفعل.');
            }

            if ($order->items()->count() === 0) {
                throw new InvalidArgumentException('لا يمكن إغلاق طاولة بدون أي أصناف.');
            }

            // 1. ربط أو تحديث بيانات العميل إن تم إدخالها
            if (!empty($data['customer_phone']) || !empty($data['phone']) || !empty($data['customer_name'])) {
                $customerId = $this->resolveCustomerId([
                    'customer_id'   => $data['customer_id'] ?? $order->customer_id,
                    'customer_name' => $data['customer_name'] ?? $data['name'] ?? null,
                    'phone'         => $data['customer_phone'] ?? $data['phone'] ?? null,
                ]);
                if ($customerId) {
                    $order->customer_id = $customerId;
                }
            }

            // 2. تطبيق الخصم إن وجد
            if (array_key_exists('discount', $data) && $data['discount'] !== null) {
                $order->discount = max(0, (float) $data['discount']);
            }

            // 3. إعادة الحساب المالي
            $this->calculationService->recalculateOrder($order);

            // 4. إنشاء الفاتورة أو تحديثها بالدفع
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $this->invoiceService->createInvoice($order, $paymentMethod);

            // 5. إتمام الطلب وإغلاق الطاولة
            $order->update([
                'payment_status' => 'paid',
                'status'         => 'completed',
                'closed_at'      => now(),
            ]);

            // 6. طباعة الإيصال النهائي
            $this->printingService->createCustomerReceipt($order, 'receipt');

            return $order->fresh(['items.menu', 'table', 'customer', 'invoice']);
        });
    }

    /**
     * الحفاظ على توافق دالة checkout القديمة
     */
    public function checkout(Order $order): void
    {
        $this->closeTable($order, ['payment_method' => 'cash']);
    }

    /**
     * ربط أو إنشاء العميل بالهاتف دون تكرار
     */
    public function resolveCustomerId(array $data): ?int
    {
        if (!empty($data['customer_id'])) {
            return (int) $data['customer_id'];
        }

        $phone = trim($data['customer_phone'] ?? $data['phone'] ?? '');
        $name = trim($data['customer_name'] ?? $data['name'] ?? '');

        if (!empty($phone)) {
            $customer = Customer::where('phone', $phone)->first();
            if ($customer) {
                if (!empty($name) && $customer->name !== $name) {
                    $customer->update(['name' => $name]);
                }
                return $customer->id;
            }

            $customer = Customer::create([
                'name'  => !empty($name) ? $name : 'عميل ' . $phone,
                'phone' => $phone,
            ]);
            return $customer->id;
        }

        if (!empty($name)) {
            $customer = Customer::create([
                'name' => $name,
            ]);
            return $customer->id;
        }

        return null;
    }

    private function createOrderRecord(array $data): Order
    {
        $customerId = $this->resolveCustomerId($data);
        $tableName = null;

        if (!empty($data['table_id'])) {
            $table = Table::find($data['table_id']);
            $tableName = $table?->name;
        } elseif (!empty($data['table_number'])) {
            $tableName = $data['table_number'];
        }

        return Order::create([
            'order_number'    => $this->generateOrderNumber(),
            'customer_id'     => $customerId,
            'table_id'        => $data['table_id'] ?? null,
            'table_number'    => $tableName,
            'delivery_address' => $data['delivery_address'] ?? null,
            'phone'           => $data['phone'] ?? $data['customer_phone'] ?? null,
            'delivery_person' => $data['delivery_person'] ?? null,
            'type'            => $this->resolveOrderType($data['type'] ?? null),
            'status'          => $this->resolveInitialStatus($data['type'] ?? null),
            'payment_status'  => 'pending',
            'discount'        => max(0, (float) ($data['discount'] ?? 0)),
            'notes'           => $data['notes'] ?? null,
            'created_by'      => Auth::check() ? Auth::id() : 1,
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
        $order->update([
            'payment_status' => 'paid',
            'status'         => 'completed',
            'closed_at'      => now(),
        ]);

        $order->refresh();

        $this->invoiceService->createInvoice($order, $data['payment_method'] ?? 'cash');
        $this->printingService->createCustomerReceipt($order, 'receipt');
        $this->printingService->createKitchenTicket($order);
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
