<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\ShiftAction;
use App\Models\User;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService
    ) {}

    /**
     * صفحة شيفت الموظف الشخصية (My Shift)
     */
    public function myShift(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $activeShift = $this->shiftService->getActiveShift($user);

        if ($activeShift) {
            $summary = $this->shiftService->getShiftLiveSummary($activeShift);

            $actionsQuery = $activeShift->actions()->latest('id');

            // تصفية سريعة للأكشنز في صفحة الموظف
            if ($request->filled('type') && $request->type !== 'all') {
                $actionsQuery->where('action_type', $request->type);
            }

            $actions = $actionsQuery->paginate(30)->withQueryString();
            $recentShifts = Shift::where('user_id', $user->id)
                ->where('status', 'closed')
                ->latest('end_time')
                ->take(5)
                ->get();

            return view('shifts.my_shift', compact('user', 'activeShift', 'summary', 'actions', 'recentShifts'));
        }

        // في حال عدم وجود شيفت مفتوح
        $recentShifts = Shift::where('user_id', $user->id)
            ->where('status', 'closed')
            ->latest('end_time')
            ->take(5)
            ->get();

        return view('shifts.my_shift', compact('user', 'activeShift', 'recentShifts'));
    }

    /**
     * صفحة إدارة الشيفتات للمدير والمشرفين (Shifts Index)
     */
    public function index(Request $request): View|RedirectResponse|JsonResponse
    {
        $user = $request->user();

        // إذا لم يكن المستخدم مديراً أو مشرفاً يتم توجيهه لصفحة شيفته
        if (!$user->isManager()) {
            return redirect()->route('shifts.my_shift');
        }

        $query = Shift::with('user:id,name,role');

        // 1. فلترة الحالة
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // 2. فلترة الموظف
        if ($request->filled('employee_id') && $request->employee_id !== 'all') {
            $query->where('user_id', $request->employee_id);
        }

        // 3. فلترة التاريخ
        if ($request->filled('date_filter')) {
            match ($request->date_filter) {
                'today' => $query->whereDate('start_time', Carbon::today()),
                'yesterday' => $query->whereDate('start_time', Carbon::yesterday()),
                'this_week' => $query->whereBetween('start_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
                'this_month' => $query->whereMonth('start_time', Carbon::now()->month)->whereYear('start_time', Carbon::now()->year),
                default => null,
            };
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('start_time', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('start_time', '<=', $request->date_to);
            }
        }

        // إحصائيات عامة
        $stats = [
            'open_count'         => Shift::where('status', 'open')->count(),
            'closed_today_count' => Shift::where('status', 'closed')->whereDate('end_time', Carbon::today())->count(),
            'today_sales'        => (float) Shift::whereDate('start_time', Carbon::today())->sum(DB::raw('cash_sales + card_sales + instapay_sales')),
            'differences_sum'    => (float) Shift::whereDate('start_time', Carbon::today())->whereNotNull('difference')->sum('difference'),
        ];

        // قائمة الموظفين للفلترة وإدارة صلاحيات بدء الشيفت
        $employees = User::whereIn('role', ['cashier', 'barista', 'client', 'supervisor'])
            ->with(['shifts' => function ($q) {
                $q->where('status', 'open')->latest('id');
            }])
            ->orderBy('name')
            ->get();

        $shifts = $query->latest('start_time')->paginate(15)->appends($request->query());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $shifts,
                'stats'   => $stats,
            ]);
        }

        return view('shifts.index', compact('shifts', 'stats', 'employees'));
    }

    /**
     * صفحة تفاصيل الشيفت للمدير (Shift Details)
     */
    public function show(Shift $shift, Request $request): View|JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if (!$user->isManager() && $shift->user_id !== $user->id) {
            abort(403, 'غير مصرح لك بعرض تفاصيل هذا الشيفت.');
        }

        $shift->load(['user:id,name,role']);

        $actionsQuery = $shift->actions()->latest('id');
        if ($request->filled('type') && $request->type !== 'all') {
            $actionsQuery->where('action_type', $request->type);
        }
        $actions = $actionsQuery->paginate(30)->withQueryString();

        $summary = $this->shiftService->getShiftLiveSummary($shift);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $shift,
                'summary' => $summary,
            ]);
        }

        return view('shifts.show', compact('shift', 'summary', 'actions'));
    }

    /**
     * بدء شيفت جديد للموظف
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // فحص المنع أولاً
        if (!$user->canStartShift()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'تم منعك من بدء الشيفت',
                ], 403);
            }
            return back()->with('error', 'تم منعك من بدء الشيفت');
        }

        $request->validate([
            'opening_float' => 'nullable|numeric|min:0',
        ]);

        try {
            $openingFloat = (float) $request->input('opening_float', 0);
            $shift = $this->shiftService->openShift($user, $openingFloat);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم فتح الشِفت بنجاح.',
                    'data'    => $shift,
                ], 201);
            }

            return redirect()->route('shifts.my_shift')->with('success', 'تم بدء الشيفت بنجاح.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * إغلاق الشيفت وتسوية الحسابات (من الموظف أو المدير)
     */
    public function close(Request $request, Shift $shift): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // التحقق من صلاحية الإغلاق: إما صاحب الشيفت أو مدير/مشرف
        if ($shift->user_id !== $user->id && !$user->isManager()) {
            abort(403, 'غير مصرح لك بإغلاق هذا الشيفت.');
        }

        $request->validate([
            'actual_cash' => 'required|numeric|min:0',
            'notes'       => 'nullable|string|max:1000',
        ]);

        try {
            $isManager = $user->isManager() && $shift->user_id !== $user->id;

            if ($isManager) {
                $closed = $this->shiftService->closeByManager(
                    $shift,
                    $user,
                    (float) $request->input('actual_cash'),
                    $request->input('notes')
                );
            } else {
                $closed = $this->shiftService->closeShift(
                    $shift,
                    (float) $request->input('actual_cash'),
                    $request->input('notes')
                );
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إغلاق الشِفت وحساب الفروقات بنجاح.',
                    'data'    => $closed,
                ]);
            }

            $redirectRoute = $isManager ? route('shifts.show', $shift->id) : route('shifts.my_shift');
            return redirect($redirectRoute)->with('success', 'تم إغلاق الشيفت وحساب الفروقات بنجاح.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * تسجيل مسحوب نقدي
     */
    public function drop(Request $request, Shift $shift): JsonResponse|RedirectResponse
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

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تسجيل المسحوب النقدي بنجاح.',
                    'data'    => $updated,
                ]);
            }

            return back()->with('success', 'تم تسجيل المسحوب النقدي بنجاح.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * المدير يمنع أو يسمح للموظف ببدء شيفت جديد
     */
    public function toggleBlock(Request $request, User $user): JsonResponse|RedirectResponse
    {
        if (!$request->user()->isManager()) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء.');
        }

        $user->can_start_shift = !$user->can_start_shift;
        $user->save();

        $message = $user->can_start_shift
            ? "تم السماح للموظف [{$user->name}] ببدء الشيفتات."
            : "تم منع الموظف [{$user->name}] من بدء أي شيفت جديد.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success'         => true,
                'can_start_shift' => $user->can_start_shift,
                'message'         => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * جلب تفاصيل أكشن محدد لعرضها في المودال التفصيلي (Read-Only)
     */
    public function actionDetails(ShiftAction $action): JsonResponse
    {
        $action->load('user:id,name,role');

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $action->id,
                'action_type'    => $action->action_type,
                'action_title'   => $action->action_title,
                'model_type'     => $action->model_type,
                'model_id'       => $action->model_id,
                'amount'         => (float) $action->amount,
                'payment_method' => $action->payment_method,
                'details'        => $action->details,
                'created_at'     => $action->created_at->format('Y-m-d h:i:s A'),
                'created_at_human' => $action->created_at->diffForHumans(),
                'user_name'      => $action->user->name ?? 'غير معروف',
            ],
        ]);
    }

    /**
     * الشيفت النشط للمستخدم الحالي (JSON API)
     */
    public function current(Request $request): JsonResponse
    {
        $shift = $this->shiftService->getActiveShift($request->user());

        return response()->json([
            'success' => true,
            'data'    => $shift,
            'summary' => $shift ? $this->shiftService->getShiftLiveSummary($shift) : null,
        ]);
    }
}
