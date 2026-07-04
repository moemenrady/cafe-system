<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\StaffWithdrawalController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('categories', CategoryController::class);
    Route::resource('menu', MenuController::class);
    Route::resource('inventory', InventoryController::class);
    Route::post('/inventory/{id}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::resource('expenses', ExpenseController::class);
    Route::resource('withdrawals', StaffWithdrawalController::class);
});