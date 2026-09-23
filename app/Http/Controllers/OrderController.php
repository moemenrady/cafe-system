<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\Table;
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
     * GET /orders/table/{table}/active – جلب الطلب المفتوح الحالي لطاولة
     */
    public function getActiveTableOrder(Table $table): JsonResponse
    {
        $order = $table->currentOrder;

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد طلب مفتوح لهذه الطاولة حالياً.',
            ], 404);
        }

        $order->load(['items.menu', 'table', 'customer', 'creator', 'invoice']);

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /**
     * POST /orders/{order}/add-items – إضافة أصناف إضافية لطلب الطاولة المفتوح
     */
    public function addItems(Order $order, Request $request): JsonResponse
    {
        $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.menu_id'  => 'required|exists:menu,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes'    => 'nullable|string|max:255',
        ]);

        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $deviceUuid = $request->header('X-Device-UUID') ?? $request->input('device_uuid', 'pos-cashier-01');

        if ($printerError = $this->verifyPrinterReady($deviceUuid, $force, ['cashier'])) {
            return $printerError;
        }

        try {
            $updatedOrder = $this->orderService->addItemsToOrder($order, $request->input('items'));

            return response()->json([
                'success' => true,
                'message' => 'تمت إضافة الأصناف بنجاح وتحديث إجماليات الطلب.',
                'data'    => $updatedOrder,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إضافة الأصناف إلى الطلب.',
            ], 500);
        }
    }

    /**
     * POST /orders/{order}/print-invoice – طباعة فاتورة الحساب للعميل دون إغلاق الطاولة
     */
    public function printInvoice(Order $order, Request $request): JsonResponse
    {
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $deviceUuid = $request->header('X-Device-UUID') ?? $request->input('device_uuid', 'pos-cashier-01');

        if ($printerError = $this->verifyPrinterReady($deviceUuid, $force, ['cashier'])) {
            return $printerError;
        }

        try {
            $invoice = $this->orderService->printInvoice($order);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال أمر طباعة الفاتورة للطابعة بنجاح.',
                'invoice' => $invoice,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر إصدار أمر طباعة الفاتورة.',
            ], 500);
        }
    }

    /**
     * POST /orders/{order}/close-table – إغلاق الطاولة وتحصيل الحساب النهائي
     */
    public function closeTable(Order $order, Request $request): JsonResponse
    {
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $deviceUuid = $request->header('X-Device-UUID') ?? $request->input('device_uuid', 'pos-cashier-01');

        if ($printerError = $this->verifyPrinterReady($deviceUuid, $force, ['cashier'])) {
            return $printerError;
        }

        try {
            $completedOrder = $this->orderService->closeTable($order, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق الطاولة وإتمام الحساب بنجاح.',
                'data'    => $completedOrder,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إغلاق الطاولة.',
            ], 500);
        }
    }

    /**
     * POST /orders/{order}/reprint – إعادة طباعة إيصال الفاتورة لطلب قديم أو مغلق
     */
    public function reprint(Order $order, Request $request): JsonResponse
    {
        $force = filter_var($request->input('force', false), FILTER_VALIDATE_BOOLEAN);
        $deviceUuid = $request->header('X-Device-UUID') ?? $request->input('device_uuid', 'pos-cashier-01');

        if ($printerError = $this->verifyPrinterReady($deviceUuid, $force, ['cashier'])) {
            return $printerError;
        }

        try {
            $this->orderService->printInvoice($order);

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال أمر إعادة الطباعة إلى الطابعة بنجاح.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'تعذر إعادة طباعة الإيصال.',
            ], 500);
        }
    }

    /**
     * POST /orders/{order}/checkout – للتوافق مع الطلبات القديمة
     */
    public function checkout(Order $order, Request $request): JsonResponse
    {
        return $this->closeTable($order, $request);
    }

    /**
     * GET /orders/history/tables – سجل الطاولات المغلقة (View Only)
     */
    public function tableHistory(Request $request)
    {
        $query = Order::where('type', 'dine_in')
            ->where('status', 'completed')
            ->with(['table', 'customer', 'creator', 'invoice', 'items.menu']);

        // فلترة بالتاريخ
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // فلترة بالطاولة
        if ($request->filled('table_id')) {
            $query->where('table_id', $request->table_id);
        }

        // فلترة بالبحث (رقم الأوردر أو رقم/اسم العميل)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest('closed_at')->paginate(20)->withQueryString();
        $tables = Table::orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $orders,
            ]);
        }

        return view('tables.history', compact('orders', 'tables'));
    }

    /**
     * GET /orders/{order} – تفاصيل طلب معين (Read-only)
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['items.menu', 'table', 'customer', 'creator', 'invoice']);

        return response()->json(['data' => $order]);
    }

    /**
     * GET /orders – قائمة الطلبات
     */
    public function index(): JsonResponse
    {
        $orders = Order::with(['items.menu', 'table', 'customer', 'creator', 'invoice'])
            ->latest()
            ->take(50)
            ->get();

        return response()->json(['data' => $orders]);
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
}
