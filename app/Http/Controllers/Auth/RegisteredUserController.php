<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use App\Mail\VerifyCodeMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */

    // ...

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'role' => 'nullable|in:admin,supervisor,cashier,barista,client',
        ]);

        // أمان: فقط إذا المستخدم الحالي مُسجل ودوره admin يمكنه تعيين الصلاحيات
        $requestedRole = $request->input('role', 'client');
        $role = 'client';
        if (auth()->check() && auth()->user()->role === 'admin') {
            $role = in_array($requestedRole, ['admin', 'supervisor', 'cashier', 'barista', 'client'], true)
                ? $requestedRole
                : 'client';
        }

        // انشئ المستخدم مفعل تلقائياً كإيميل
        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'role'              => $role,
            'email_verified_at' => now(),
            'is_active'         => true,
            'can_start_shift'   => in_array($role, ['admin', 'supervisor', 'cashier'], true),
        ]);

        // 🔥 لو العملية جاية من لوحة التحكم (admin)
        if (auth()->check() && auth()->user()->role === 'admin') {
            return redirect()
                ->route('settings.index')
                ->with('success', 'تم إنشاء الحساب بنجاح وتفعيله تلقائياً ✅');
        }

        // 🔵 تسجيل دخول تلقائي للحساب المفعل
        Auth::login($user);

        $targetRoute = $user->isManager() ? 'dashboard' : 'pos.index';
        return redirect()->route($targetRoute)->with('success', 'تم إنشاء الحساب وتفعيله بنجاح! مرحباً بك.');
    }

    public function showVerifyForm()
    {
        // صفحة يدخل فيها الايميل و الكود
        $email = session('email') ?? request()->query('email');
        return view('auth.verify-register', compact('email'));
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:6',
        ]);

        $cacheKey = 'email_verif_code_' . $request->email;
        $data = Cache::get($cacheKey);

        if (!$data) {
            return back()->withErrors(['code' => 'انتهت صلاحية كود التحقق أو غير صحيح'])->withInput();
        }

        if ($data['code'] != $request->code) {
            return back()->withErrors(['code' => 'كود التحقق غير صحيح'])->withInput();
        }

        // تحقق من وجود المستخدم وربطه
        $user = \App\Models\User::find($data['user_id']);
        if (!$user || $user->email !== $request->email) {
            return back()->withErrors(['email' => 'حصل خطأ. حاول تسجيل جديد.'])->withInput();
        }

        // فعّل البريد
        $user->email_verified_at = now();
        $user->save();

        // امسح الكود من الكاش
        Cache::forget($cacheKey);

        // سجل الدخول أو وجه المستخدم لصفحة تسجيل الدخول بفلش رسالة
        auth()->login($user);

        return redirect()->route('pages.inventory')->with('success', 'تم تفعيل الحساب بنجاح!');
    }

    public function index()
    {
        $totalUsers = User::count();
        $adminsCount = User::where('role', 'admin')->count();

        // في البداية نعرض الصفحة بدون بيانات (يعرض "جاري التحميل...") ثم JS يجلبهم عبر AJAX
        return view('managment.changes.users.create', compact('totalUsers', 'adminsCount'));
    }
    public function ajaxSearch(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        // لو في استعلام فارغ، نجلب أول 50 مستخدم (أو عدد مناسب)
        $query = User::query()->select(['id', 'name', 'email', 'role']);

        if ($q !== '') {
            $query->where(function ($b) use ($q) {
                $b->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");

                // لو المستخدم كتب رقم نحاول نطابق الـ id بالضبط
                if (is_numeric($q)) {
                    $b->orWhere('id', intval($q));
                }
            });
        }

        // ترتيب وتحديد حد لإعادة الأداء (غير ثابت: اضبط حسب حجم قاعدة البيانات)
        $users = $query->orderBy('id', 'desc')->limit(200)->get();

        // لو تريد إضافة إحصاءات أو total -> return ['data'=>$users,'total'=>$users->count()]
        return response()->json($users);
    }
}
