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
            'role' => 'nullable|in:user,admin',
        ]);

        // أمان: فقط إذا المستخدم الحالي مُسجل ودوره admin يمكنه تعيين admin
        $requestedRole = $request->input('role', 'user');
        $role = 'user';
        if (auth()->check() && auth()->user()->role === 'admin' && $requestedRole === 'admin') {
            $role = 'admin';
        }

        // انشئ المستخدم (مؤقتًا غير مفعل)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'email_verified_at' => null,
        ]);

        // genereate 6-digit code
        $code = random_int(100000, 999999);

        // خزّن الكود مؤقتًا في الكاش مرتبط بالإيميل (أو استخدم جدول verification_codes لو تحب)
        $cacheKey = 'email_verif_code_' . $user->email;
        Cache::put($cacheKey, [
            'code' => $code,
            'user_id' => $user->id,
        ], now()->addMinutes(10)); // صالح 10 دقائق

        // 👇 بعد إنشاء المستخدم والكود

        Mail::to($user->email)->send(new VerifyCodeMail($user, $code));

        // 🔥 لو العملية جاية من لوحة التحكم (admin)
        if (auth()->check() && auth()->user()->role === 'admin') {
            return redirect()
                ->route('system-accounts.index')
                ->with('success', 'تم إنشاء الحساب بنجاح وتم إرسال كود التفعيل 📩');
        }

        // 🔵 غير كده (تسجيل عادي)
        return redirect()
            ->route('register.verify.show')
            ->with('email', $user->email);
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
