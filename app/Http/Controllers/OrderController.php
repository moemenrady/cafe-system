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

    public function store(OrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder(
            $request->validated()
        );
        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order,
        ], 201);
    }
    public function checkout(Order $order)
    {
        $this->orderService->checkout($order);

        return response()->json([
            'message' => 'Payment completed successfully.'
        ]);
    }
}
