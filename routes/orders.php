<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::middleware(['auth', 'verified'])->prefix('orders')->name('orders.')->group(function () {
    Route::post('/', [OrderController::class, 'store'])->name('store');

    Route::get('/', [OrderController::class, 'index'])->name('index');
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::put('/{order}', [OrderController::class, 'update'])->name('update');
    Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
});

Route::get('/tables', [OrderController::class, 'tables'])->name('tables.index');
