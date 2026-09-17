<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;

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
    public function checkout(Order $order): JsonResponse
    {
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
