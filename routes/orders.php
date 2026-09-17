<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::middleware(['auth', 'verified'])->prefix('orders')->name('orders.')->group(function () {

    Route::post('/', [OrderController::class, 'store'])->name('store');

    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');

    // إتمام الدفع لطلب Dine-In
    Route::post('/{order}/checkout', [OrderController::class, 'checkout'])->name('checkout');
});
