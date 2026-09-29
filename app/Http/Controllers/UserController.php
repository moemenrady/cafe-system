<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * GET /settings/users – قائمة المستخدمين (تستخدم عبر AJAX أو التوجيه للإعدادات)
     * مخصصة فقط للأدمن
     */
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $query = User::withCount(['shifts', 'createdOrders'])
            ->when($request->search, function ($q) use ($request) {
                $term = trim($request->search);
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->role && $request->role !== 'all', function ($q) use ($request) {
                $q->where('role', $request->role);
            })
            ->when($request->filled('is_active') && $request->is_active !== 'all', function ($q) use ($request) {
                $q->where('is_active', (bool)$request->is_active);
            })
            ->orderBy('role')
            ->orderBy('name');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'users'   => $query->get(),
            ]);
        }

        return redirect()->route('settings.index');
    }

    /**
     * GET /settings/users/{user} – جلب جميع تفاصيل المستخدم
     */
    public function show(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $user->loadCount(['shifts', 'createdOrders']);

        return response()->json([
            'success' => true,
            'user'    => [
                'id'                 => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'role'               => $user->role,
                'is_active'          => (bool)$user->is_active,
                'can_start_shift'    => (bool)($user->can_start_shift ?? true),
                'email_verified_at'  => $user->email_verified_at ? $user->email_verified_at->format('Y-m-d H:i') : null,
                'created_at'         => $user->created_at ? $user->created_at->format('Y-m-d H:i') : null,
                'updated_at'         => $user->updated_at ? $user->updated_at->format('Y-m-d H:i') : null,
                'shifts_count'       => $user->shifts_count ?? 0,
                'orders_count'       => $user->created_orders_count ?? 0,
                'is_current_user'    => $user->id === Auth::id(),
            ],
        ]);
    }

    /**
     * POST /settings/users – إضافة مستخدم جديد بجميع بياناته (أدمن فقط)
     */
    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'confirmed', 'min:8'],
            'role'            => ['required', Rule::in(['admin', 'supervisor', 'cashier', 'barista', 'client'])],
            'is_active'       => ['nullable', 'boolean'],
            'can_start_shift' => ['nullable', 'boolean'],
        ]);

        $data['password']          = Hash::make($data['password']);
        $data['email_verified_at'] = now();
        $data['is_active']         = $request->boolean('is_active', true);
        
        // إذا لم يُحدد can_start_shift، يتم تفعيله تلقائياً للمدراء والكاشير
        $data['can_start_shift']   = $request->has('can_start_shift') 
            ? $request->boolean('can_start_shift') 
            : in_array($data['role'], ['admin', 'supervisor', 'cashier'], true);

        $user = User::create($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "تم إضافة المستخدم '{$user->name}' بنجاح.",
                'user'    => $user,
            ]);
        }

        return back()->with('success', "تم إضافة المستخدم '{$user->name}' بنجاح.");
    }

    /**
     * PUT /settings/users/{user} – تعديل جميع بيانات المستخدم (أدمن فقط)
     */
    public function update(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'            => ['required', Rule::in(['admin', 'supervisor', 'cashier', 'barista', 'client'])],
            'password'        => ['nullable', 'confirmed', 'min:8'],
            'is_active'       => ['nullable', 'boolean'],
            'can_start_shift' => ['nullable', 'boolean'],
        ]);

        // حماية: لا يمكن للأدمن إزالة صلاحية المدير عن حسابه الحالي أو تعطيل نفسه
        if (Auth::id() === $user->id) {
            if ($data['role'] !== 'admin') {
                return $this->errorResponse($request, 'لا يمكن تجريد حسابك الحالي من صلاحية المدير العام.');
            }
            if ($request->has('is_active') && !$request->boolean('is_active')) {
                return $this->errorResponse($request, 'لا يمكن تعطيل حسابك الحالي.');
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // تحديث الحقول الاختيارية بوضوح
        $data['is_active'] = $request->has('is_active') 
            ? $request->boolean('is_active') 
            : $user->is_active;

        $data['can_start_shift'] = $request->has('can_start_shift') 
            ? $request->boolean('can_start_shift') 
            : $user->can_start_shift;

        $user->update($data);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "تم تحديث بيانات المستخدم '{$user->name}' بنجاح.",
                'user'    => $user,
            ]);
        }

        return back()->with('success', "تم تحديث بيانات المستخدم '{$user->name}' بنجاح.");
    }

    /**
     * DELETE /settings/users/{user} – حذف مستخدم (أدمن فقط)
     */
    public function destroy(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        // حماية: منع حذف الحساب الحالي
        if (Auth::id() === $user->id) {
            return $this->errorResponse($request, 'لا يمكن حذف الحساب المستخدم حالياً لتسجيل الدخول.');
        }

        // حماية: التأكد من وجود مدير آخر على الأقل في النظام
        if ($user->isAdmin() && User::where('role', 'admin')->where('id', '!=', $user->id)->count() === 0) {
            return $this->errorResponse($request, 'لا يمكن حذف هذا المستخدم لأنه المدير الوحيد المتبقي في النظام.');
        }

        $userName = $user->name;
        $user->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "تم حذف المستخدم '{$userName}' بنجاح.",
            ]);
        }

        return back()->with('success', "تم حذف المستخدم '{$userName}' بنجاح.");
    }

    /**
     * POST /settings/users/{user}/toggle-status – تفعيل / تعطيل الحساب بنقرة زر (أدمن فقط)
     */
    public function toggleStatus(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        if (Auth::id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعطيل حسابك الحالي.',
            ], 422);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $stateText = $user->is_active ? 'مفعّل' : 'معطّل';

        return response()->json([
            'success'   => true,
            'is_active' => (bool)$user->is_active,
            'message'   => "تم تغيير حالة المستخدم '{$user->name}' إلى: {$stateText}",
        ]);
    }

    /**
     * POST /settings/users/{user}/toggle-shift – تفعيل / إلغاء صلاحية بدء الشيفت بنقرة زر (أدمن فقط)
     */
    public function toggleShiftPermission(Request $request, User $user)
    {
        $this->ensureAdmin($request);

        $user->can_start_shift = !((bool)($user->can_start_shift ?? true));
        $user->save();

        $stateText = $user->can_start_shift ? 'مسموح له بفتح الشيفت' : 'ممنوع من فتح الشيفت';

        return response()->json([
            'success'         => true,
            'can_start_shift' => (bool)$user->can_start_shift,
            'message'         => "تم تحديث صلاحية الشيفت للمستخدم '{$user->name}': {$stateText}",
        ]);
    }

    /**
     * حفظ ترتيب عناصر القائمة الجانبية لحساب المستخدم الحالي
     */
    public function updateSidebarOrder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'string',
        ]);

        $user = $request->user();
        $user->sidebar_order = $request->order;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ ترتيب القائمة بنجاح.',
            'order'   => $user->sidebar_order,
        ]);
    }

    /**
     * استعادة الترتيب الافتراضي للقائمة الجانبية
     */
    public function resetSidebarOrder(Request $request)
    {
        $user = $request->user();
        $user->sidebar_order = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'تمت استعادة الترتيب الافتراضي بنجاح.',
        ]);
    }

    /**
     * التحقق من أن المستخدم الحالي يحمل صلاحية الأدمن فقط
     */
    protected function ensureAdmin(Request $request): void
    {
        if (!$request->user() || !$request->user()->isAdmin()) {
            abort(403, 'هذه العملية مخصصة لمدير النظام (Admin) فقط.');
        }
    }

    /**
     * مساعد لإرجاع رسالة خطأ موحدة
     */
    protected function errorResponse(Request $request, string $message, int $status = 422)
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], $status);
        }
        return back()->withErrors(['error' => $message]);
    }
}
