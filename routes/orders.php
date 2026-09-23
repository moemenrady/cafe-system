<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::middleware(['auth', 'verified'])->prefix('orders')->name('orders.')->group(function () {

    Route::post('/', [OrderController::class, 'store'])->name('store');
    Route::get('/', [OrderController::class, 'index'])->name('index');

    // مسارات الطاولات الخاصة قبل {order} لتجنب تعارض الربط
    Route::get('/history/tables', [OrderController::class, 'tableHistory'])->name('history.tables');
    Route::get('/table/{table}/active', [OrderController::class, 'getActiveTableOrder'])->name('table.active');

    // دورة حياة الطلب المفتوح
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::post('/{order}/add-items', [OrderController::class, 'addItems'])->name('add-items');
    Route::post('/{order}/print-invoice', [OrderController::class, 'printInvoice'])->name('print-invoice');
    Route::post('/{order}/close-table', [OrderController::class, 'closeTable'])->name('close-table');
    Route::post('/{order}/reprint', [OrderController::class, 'reprint'])->name('reprint');

    // إتمام الدفع لطلب Dine-In (للتوافق القديم)
    Route::post('/{order}/checkout', [OrderController::class, 'checkout'])->name('checkout');
});
