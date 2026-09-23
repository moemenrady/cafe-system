<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $shifts = Shift::with('user:id,name,role')
            ->latest('start_time')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => $shifts,
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $shift = $this->shiftService->getActiveShift($request->user());

        return response()->json([
            'success' => true,
            'data'    => $shift,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'opening_float' => 'required|numeric|min:0',
        ]);

        try {
            $shift = $this->shiftService->openShift(
                $request->user(),
                (float) $request->input('opening_float')
            );

            return response()->json([
                'success' => true,
                'message' => 'تم فتح الشِفت بنجاح.',
                'data'    => $shift,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Shift $shift): JsonResponse
    {
        $shift->load(['user:id,name', 'invoices', 'orders']);

        return response()->json([
            'success' => true,
            'data'    => $shift,
        ]);
    }

    public function drop(Request $request, Shift $shift): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $updated = $this->shiftService->recordCashDrop(
                $shift,
                (float) $request->input('amount'),
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل المسحوب النقدي بنجاح.',
                'data'    => $updated,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function close(Request $request, Shift $shift): JsonResponse
    {
        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes'       => 'nullable|string|max:1000',
        ]);

        try {
            $closed = $this->shiftService->closeShift(
                $shift,
                (float) $request->input('actual_cash'),
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق الشِفت وحساب الفروقات بنجاح.',
                'data'    => $closed,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
