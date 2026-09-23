<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\StaffWithdrawalController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Read-only menu access for general staff
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    // Management controls: Admins & Supervisors only
    Route::middleware('role:admin|supervisor')->group(function () {
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::resource('categories', CategoryController::class)->except(['index']);
        Route::resource('menu', MenuController::class)->except(['index']);
        Route::resource('inventory', InventoryController::class);
        Route::post('/inventory/{id}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::resource('expenses', ExpenseController::class);
        Route::resource('withdrawals', StaffWithdrawalController::class);
    });
});
