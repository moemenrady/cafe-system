<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    // صلاحيات المشرفين والأدمن لتعديل الفواتير وإلغائها
    Route::middleware('role:supervisor|admin')->group(function () {
        Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    });

    // إدارة الشِفت للكاشير والموظفين
    Route::get('/shifts/current', [ShiftController::class, 'current'])->name('shifts.current');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::post('/shifts/{shift}/drop', [ShiftController::class, 'drop'])->name('shifts.drop');
    Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
});