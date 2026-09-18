<?php

namespace App\Http\Controllers;

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

        // فحص جاهزية الطابعة قبل تأكيد الأوردر (يمكن تجاوزه إذا اختار الكاشير المتابعة)
        if ($printerError = $this->verifyPrinterReady($deviceUuid, $force)) {
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
    public function checkout(Order $order, Request $request): JsonResponse
    {
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $deviceUuid = $request->header('X-Device-UUID') ?? $request->input('device_uuid', 'pos-cashier-01');

        if ($printerError = $this->verifyPrinterReady($deviceUuid, $force)) {
            return $printerError;
        }

        try {
            $this->orderService->checkout($order);

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
}
