<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\StaffWithdrawalController;

Route::middleware(['auth', 'verified'])->group(function () {
    // Read-only menu access for general staff
    Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');
    Route::get('/menu/pdf', [MenuController::class, 'viewPdf'])->name('menu.pdf');
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');

    // حماية إجراءات المصروفات التي تسجل بالشيفت
    Route::middleware('shift.active')->group(function () {
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    });

    Route::resource('expenses', ExpenseController::class)->except(['create', 'store', 'edit', 'update']);

    // Management controls: Admins & Supervisors only
    Route::middleware('role:admin|supervisor')->group(function () {
        Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
        Route::get('/inventory/daily-tracking', [InventoryController::class, 'dailyTracking'])->name('inventory.daily_tracking');
        Route::get('/inventory/export-tracking', [InventoryController::class, 'exportTrackingCsv'])->name('inventory.export_tracking');
        Route::resource('categories', CategoryController::class)->except(['index']);
        Route::resource('menu', MenuController::class)->except(['index']);
        Route::resource('inventory', InventoryController::class);
        Route::post('/inventory/{id}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
        Route::resource('withdrawals', StaffWithdrawalController::class);
        Route::resource('expense-categories', ExpenseCategoryController::class);
    });
});
