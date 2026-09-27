<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * POST /orders – إنشاء طلب جديد من شاشة الـ POS
     */
    public function store(OrderRequest $request): JsonResponse
    {
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $deviceUuid = $request->header('X-Device-UUID') ?? $request->input('device_uuid', 'pos-cashier-01');
        $shouldPrint = filter_var($request->input('should_print', true), FILTER_VALIDATE_BOOLEAN);

        // إذا كان الجهاز جهازاً غير مخصص للطباعة (نادل / هاتف / بدون برنامج طباعة مكتبي)
        if ($deviceUuid === 'none' || empty($deviceUuid) || !$shouldPrint) {
            $deviceUuid = null;
        }

        // فحص جاهزية الطابعة فقط إذا كان هذا الجهاز جهاز كاشير رئيسي للطباعة
        if ($deviceUuid && ($printerError = $this->verifyPrinterReady($deviceUuid, $force))) {
            return $printerError;
        }

        $order = $this->orderService->createOrder(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الطلب بنجاح.',
            'data'    => $order,
        ], 201);
    }

    /**
     * POST /orders/{order}/checkout – إتمام الدفع لطلب Dine-In
     */
    public function checkout(Order $order, CheckoutRequest $request): JsonResponse
    {
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $deviceUuid = $request->header('X-Device-UUID') ?? $request->input('device_uuid', 'pos-cashier-01');
        $shouldPrint = filter_var($request->input('should_print', true), FILTER_VALIDATE_BOOLEAN);

        if ($deviceUuid === 'none' || empty($deviceUuid) || !$shouldPrint) {
            $deviceUuid = null;
        }

        if ($deviceUuid && ($printerError = $this->verifyPrinterReady($deviceUuid, $force))) {
            return $printerError;
        }

        try {
            $this->orderService->checkout($order, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم إتمام الدفع بنجاح.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إتمام الدفع.',
            ], 500);
        }
    }

    /**
     * التحقق من اتصال برنامج الطباعة وجاهزية الطابعات المطلوبة
     */
    private function verifyPrinterReady(?string $deviceUuid, bool $force = false, array $requiredRoles = ['cashier']): ?JsonResponse
    {
        if ($force) {
            return null;
        }

        $deviceUuid = $deviceUuid ?: 'pos-cashier-01';
        $deviceStatus = Cache::get("pos_device_{$deviceUuid}");

        // 1. التحقق من اتصال الـ Agent واستلام نبضات حديثة
        if (!$deviceStatus || ($deviceStatus['status'] ?? 'offline') !== 'ready') {
            return response()->json([
                'success' => false,
                'printer_warning' => true,
                'message' => 'تعذر إتمام الطلب: برنامج الطباعة غير متصل بالجهاز حالياً أو متوقف.',
                'device_status' => $deviceStatus['status'] ?? 'offline',
                'printers' => $deviceStatus['printers'] ?? null,
                'device_uuid' => $deviceUuid,
            ], 422);
        }

        // 2. استخراج الأدوار النشطة
        $activeRoles = $deviceStatus['active_roles'] ?? [];
        if (empty($activeRoles) && !empty($deviceStatus['printers'])) {
            foreach ($deviceStatus['printers'] as $role => $info) {
                if (($info['status'] ?? '') === 'online') {
                    $activeRoles[] = $role;
                }
            }
        }

        // 3. التحقق من الطابعات المعينة
        foreach ($requiredRoles as $role) {
            if (!in_array($role, $activeRoles, true)) {
                $roleLabel = match ($role) {
                    'cashier' => 'الكاشير',
                    'barista', 'kitchen' => 'المطبخ / الباريستا',
                    default => $role,
                };

                return response()->json([
                    'success' => false,
                    'printer_warning' => true,
                    'message' => "تعذر إتمام الطلب: طابعة ({$roleLabel}) غير متصلة بالشبكة.",
                    'device_status' => 'printer_offline',
                    'missing_role' => $role,
                    'printers' => $deviceStatus['printers'] ?? null,
                    'device_uuid' => $deviceUuid,
                ], 422);
            }
        }

        return null;
    }

    /**
     * GET /orders – قائمة الطلبات
     */
    public function index(): JsonResponse
    {
        $orders = Order::with(['items.menu', 'table', 'customer', 'creator'])
            ->latest()
            ->take(50)
            ->get();

        return response()->json(['data' => $orders]);
    }

    /**
     * GET /orders/{order} – تفاصيل طلب معين
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['items.menu', 'table', 'customer', 'creator']);

        return response()->json(['data' => $order]);
    }

    /**
     * GET /orders/delivery/active – قائمة طلبات الديلفري المعلقة
     */
    public function activeDeliveries(): JsonResponse
    {
        $orders = Order::with(['items.menu', 'creator'])
            ->where('type', 'delivery')
            ->where('status', 'waiting_delivery')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id'               => $order->id,
                    'order_number'     => $order->order_number,
                    'phone'            => $order->phone,
                    'delivery_address' => $order->delivery_address,
                    'delivery_person'  => $order->delivery_person,
                    'total'            => number_format($order->total, 2),
                    'notes'            => $order->notes,
                    'created_at'       => $order->created_at->format('h:i A'),
                    'waiting_minutes'  => (int) $order->created_at->diffInMinutes(now()),
                    'items'            => $order->items->map(fn($i) => [
                        'name'     => $i->menu->name ?? 'صنف محذوف',
                        'quantity' => $i->quantity,
                    ]),
                ];
            });

        return response()->json([
            'success' => true,
            'count'   => $orders->count(),
            'data'    => $orders,
        ]);
    }

    /**
     * POST /orders/{order}/assign-driver – تعيين مندوب وتسليم الطلب
     */
    public function assignDriver(Order $order, Request $request): JsonResponse
    {
        if ($order->type !== 'delivery' || $order->status !== 'waiting_delivery') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الطلب ليس ديلفري معلق.',
            ], 422);
        }

        $request->validate([
            'delivery_person' => 'nullable|string|max:100',
        ]);

        $order->update([
            'delivery_person' => $request->input('delivery_person', $order->delivery_person),
            'status'          => 'completed',
            'payment_status'  => 'paid',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسليم الطلب وإغلاقه بنجاح.',
        ]);
    }

    /**
     * POST /orders/{order}/cancel – إلغاء طلب ديلفري وإعادة المخزن
     */
    public function cancel(Order $order, Request $request): JsonResponse
    {
        if (! in_array($order->status, ['waiting_delivery', 'open'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إلغاء هذا الطلب في حالته الحالية.',
            ], 422);
        }

        // إذا كانت الفاتورة قد تم إنشاؤها نحتاج نرجع المخزن
        if ($order->invoice) {
            $invoice = $order->invoice->load('items');
            foreach ($invoice->items as $item) {
                $product = \App\Models\Menu::with('recipes.inventoryItem')->find($item->menu_id);
                if ($product) {
                    foreach ($product->recipes as $recipe) {
                        $returnedQty = $recipe->quantity_used * $item->quantity;
                        $recipe->inventoryItem->increment('quantity', $returnedQty);
                        \App\Models\InventoryMovement::create([
                            'inventory_item_id' => $recipe->inventoryItem->id,
                            'type'              => 'sale_cancel',
                            'quantity'          => $returnedQty,
                            'balance_after'     => $recipe->inventoryItem->quantity,
                            'invoice_id'        => $invoice->id,
                            'user_id'           => auth()->id() ?? 1,
                        ]);
                    }
                }
            }
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الطلب بنجاح.',
        ]);
    }
}
