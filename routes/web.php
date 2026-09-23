<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
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

    // ⚙️ الإعدادات وتخصيص ترتيب القائمة
    Route::get('/settings', function () { return view('settings.index'); })->name('settings.index');
    Route::post('/user/sidebar-order', [UserController::class, 'updateSidebarOrder'])->name('user.sidebar_order.update');
    Route::post('/user/sidebar-order/reset', [UserController::class, 'resetSidebarOrder'])->name('user.sidebar_order.reset');
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