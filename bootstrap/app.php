<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // تم إضافة .php هنا لإصلاح المسار
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // تسجيل الـ Middleware الخاص بالصلاحيات
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'print_agent_token' => \App\Http\Middleware\VerifyPrintAgentToken::class,
            'shift.active' => \App\Http\Middleware\RequireActiveShift::class,
        ]);

        // توجيه الزوار وغير المسجلين لصفحة تسجيل الدخول
        $middleware->redirectGuestsTo(fn () => route('login'));

        // توجيه المسجلين مسبقاً (عند محاولة زيارة صفحة اللوجين) حسب الصلاحية
        $middleware->redirectUsersTo(function () {
            $user = \Illuminate\Support\Facades\Auth::user();
            if ($user && $user->isManager()) {
                return route('dashboard');
            }
            return route('pos.index');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // معالجة خطأ 419 Page Expired تلقائياً وتجديد الجلسة دون إظهار صفحة خطأ
        $exceptions->renderable(function (\Throwable $e, $request) {
            if ($e instanceof \Illuminate\Session\TokenMismatchException || ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException && $e->getStatusCode() === 419)) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'انتهت الجلسة المؤقتة، يرجى إعادة المحاولة.',
                        'status'  => 419,
                    ], 419);
                }

                $user = $request->user() ?? \Illuminate\Support\Facades\Auth::user();
                if ($user) {
                    $target = $user->isManager() ? route('dashboard') : route('pos.index');
                    return redirect()->to($target)->with('warning', 'تم تحديث الجلسة وتجديد الاتصال بنجاح.');
                }

                return redirect()->route('login')->with('warning', 'انتهت الجلسة، يرجى إعادة تسجيل الدخول.');
            }
        });
    })->create();
