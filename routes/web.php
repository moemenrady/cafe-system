<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Public & Guest Routes (الروابط العامة والزوار)
|--------------------------------------------------------------------------
*/
Route::get('/welcome', function () { return view('welcome'); });

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Authenticated Shared Routes (الروابط العامة بعد تسجيل الدخول)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/keep-alive', function () {
        return response()->json(['status' => 'ok', 'timestamp' => now()->timestamp]);
    })->name('keep_alive');

    // ⚙️ الإعدادات – الصفحة الرئيسية (تمرر بيانات المستخدمين للأدمن والمشرف)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');

    // 📊 التحليلات الشاملة والذكاء التشغيلي (Analytics)
    Route::middleware('role:admin|supervisor')->prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/comprehensive', [\App\Http\Controllers\AnalyticsController::class, 'comprehensive'])->name('comprehensive');
    });

    // 📖 دليل الاستخدام التفاعلي (User Guide - متاح لجميع مستخدمي النظام)
    Route::get('/user-guide', [\App\Http\Controllers\UserGuideController::class, 'index'])->name('user_guide.index');

    // ⚙️ تخصيص ترتيب القائمة الجانبية والتاب الأساسية (متاح للجميع)
    Route::post('/user/sidebar-order', [UserController::class, 'updateSidebarOrder'])->name('user.sidebar_order.update');
    Route::post('/user/sidebar-order/reset', [UserController::class, 'resetSidebarOrder'])->name('user.sidebar_order.reset');

    // ==========================================
    // 👥 إدارة المستخدمين (CRUD كامل) – أدمن فقط
    // ==========================================
    Route::middleware('role:admin')->prefix('settings')->name('users.')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('index');
        Route::post('/users', [UserController::class, 'store'])->name('store');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('show');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/users/{user}/toggle-shift', [UserController::class, 'toggleShiftPermission'])->name('toggle-shift');
    });
});

/*
|--------------------------------------------------------------------------
| Load Sub-Routing Files (استدعاء ملفات الروابط الفرعية المقسمة)
|--------------------------------------------------------------------------
*/
require __DIR__.'/sales.php';
require __DIR__.'/inventory.php';
require __DIR__.'/admin.php';
require __DIR__.'/orders.php';
require __DIR__.'/shifts.php';
require __DIR__.'/printing.php';
require __DIR__.'/tables.php';