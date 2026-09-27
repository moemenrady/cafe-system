<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('orders')->name('orders.')->group(function () {

    // ==========================================
    // طلبات تتطلب وردية مفتوحة
    // ==========================================
    Route::middleware('shift.active')->group(function () {
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::post('/{order}/checkout', [OrderController::class, 'checkout'])->name('checkout');
    });

    // ==========================================
    // إدارة الديلفري (متاح لجميع المشتركين في الوردية)
    // ==========================================

    // ⚠️ مهم: delivery/active لازم يتسجل قبل /{order} عشان ميجيبش 404
    Route::get('/delivery/active', [OrderController::class, 'activeDeliveries'])->name('delivery.active');
    Route::post('/{order}/assign-driver', [OrderController::class, 'assignDriver'])->name('assign-driver');
    Route::post('/{order}/cancel', [OrderController::class, 'cancel'])->name('cancel');

    // ==========================================
    // عرض الطلبات
    // ==========================================
    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
});
