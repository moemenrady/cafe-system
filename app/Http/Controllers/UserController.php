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
     * GET /settings/users – قائمة المستخدمين (مع فلتر بحث)
     * تستخدم في صفحة الإعدادات
     */
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.users.index', compact('users'));
    }

    /**
     * POST /settings/users – إضافة مستخدم جديد
     * يدعم كل الأدوار: admin, supervisor, cashier, barista
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', 'min:8'],
            'role'      => ['required', Rule::in(['admin', 'supervisor', 'cashier', 'barista'])],
        ]);

        $data['password']       = Hash::make($data['password']);
        $data['is_active']      = true;
        $data['can_start_shift'] = in_array($data['role'], ['admin', 'supervisor', 'cashier'], true);

        User::create($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم إضافة المستخدم بنجاح.']);
        }

        return back()->with('success', 'تم إضافة المستخدم بنجاح.');
    }

    /**
     * PUT /settings/users/{user} – تعديل بيانات مستخدم
     * يدعم كل الأدوار + تغيير كلمة السر (اختياري)
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'     => ['required', Rule::in(['admin', 'supervisor', 'cashier', 'barista'])],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // تحديث can_start_shift بناءً على الدور
        $data['can_start_shift'] = in_array($data['role'], ['admin', 'supervisor', 'cashier'], true);

        $user->update($data);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم تحديث بيانات المستخدم بنجاح.']);
        }

        return back()->with('success', 'تم تحديث بيانات المستخدم بنجاح.');
    }

    /**
     * DELETE /settings/users/{user} – حذف مستخدم
     */
    public function destroy(User $user)
    {
        if (Auth::id() == $user->id) {
            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف حسابك الحالي.'], 422);
            }
            return back()->withErrors(['error' => 'لا يمكن حذف المستخدم الحالي لأنه الحساب المستخدم لتسجيل الدخول.']);
        }

        $user->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف المستخدم بنجاح.']);
        }

        return back()->with('success', 'تم حذف المستخدم بنجاح.');
    }

    /**
     * POST /settings/users/{user}/toggle-status – تفعيل/تعطيل حساب مستخدم
     */
    public function toggleStatus(User $user)
    {
        if (Auth::id() == $user->id) {
            return response()->json(['success' => false, 'message' => 'لا يمكن تعطيل حسابك الحالي.'], 422);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $state = $user->is_active ? 'مفعّل' : 'معطّل';
        return response()->json([
            'success'   => true,
            'is_active' => $user->is_active,
            'message'   => "تم تغيير حالة المستخدم إلى: {$state}",
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
}
