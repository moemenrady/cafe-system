<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\InvoiceController;

Route::middleware(['auth', 'verified'])->group(function () {
    // صلاحيات المشرفين والأدمن معاً لتعديل الفواتير
    Route::middleware('role:supervisor|admin')->group(function () {
        Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });

    // صلاحيات المشرفين فقط
    Route::middleware('role:supervisor')->group(function () {
        Route::resource('shifts', ShiftController::class);
    });
});