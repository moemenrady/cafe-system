<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SalesInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('shift.active')->group(function () {
        Route::get('/pos', [SaleController::class, 'index'])->name('pos.index');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    });

    Route::get('/invoices/{id}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/customers/ajax-search', [CustomerController::class, 'ajaxSearch'])->name('customers.ajaxSearch');
    Route::get('/customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('/customers/{customer}/export', [CustomerController::class, 'exportSingle'])->name('customers.exportSingle');
    Route::resource('customers', CustomerController::class);
    Route::resource('sales-invoices', SalesInvoiceController::class);
});