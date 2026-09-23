<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('orders')->name('orders.')->group(function () {
    Route::middleware('shift.active')->group(function () {
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::post('/{order}/checkout', [OrderController::class, 'checkout'])->name('checkout');
    });

    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
});
