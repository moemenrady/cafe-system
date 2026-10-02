<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // ==========================================
    // POS – يتطلب وردية مفتوحة
    // ==========================================
    Route::middleware('shift.active')->group(function () {
        Route::get('/pos', [SaleController::class, 'index'])->name('pos.index');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    });

    // ==========================================
    // فواتير – قراءة (متاح للجميع)
    // ==========================================
    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');

    // سجل الفواتير + عرض التفاصيل (read-only للجميع)
    Route::get('/sales-invoices', [SalesInvoiceController::class, 'index'])->name('sales-invoices.index');
    Route::get('/sales-invoices/{id}', [SalesInvoiceController::class, 'show'])->name('sales-invoices.show');

    // ==========================================
    // تعديل واسترجاع الفواتير (تخضع لضوابط الصلاحيات في Controller)
    // ==========================================
    Route::post('/sales-invoices/{id}/refund', [SalesInvoiceController::class, 'refund'])->name('sales-invoices.refund');
    Route::get('/sales-invoices/{id}/edit', [SalesInvoiceController::class, 'edit'])->name('sales-invoices.edit');
    Route::put('/sales-invoices/{id}', [SalesInvoiceController::class, 'update'])->name('sales-invoices.update');

    // ==========================================
    // عملاء
    // ==========================================
    Route::get('/customers/ajax-search', [CustomerController::class, 'ajaxSearch'])->name('customers.ajaxSearch');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/customers/{customer}/export', [CustomerController::class, 'exportSingle'])->name('customers.exportSingle');
    Route::resource('customers', CustomerController::class);
});