<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {

    // 🌟 صفحة شيفت الموظف الحالية والشخصية
    Route::get('/shifts/my-shift', [ShiftController::class, 'myShift'])->name('shifts.my_shift');

    // 🌟 تفاصيل أكشن محدد للمودال (Read-Only)
    Route::get('/shifts/actions/{action}', [ShiftController::class, 'actionDetails'])->name('shifts.actions.details');

    // عمليات فتح وإغلاق ومسحوبات الشيفت للموظف
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::post('/shifts/{shift}/drop', [ShiftController::class, 'drop'])->name('shifts.drop');
    Route::post('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('shifts.close');
    Route::get('/shifts/current', [ShiftController::class, 'current'])->name('shifts.current');

    // 🔒 لوحة تحكم المشرف والمدير للشيفتات
    Route::middleware('role:supervisor|admin')->group(function () {
        Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('shifts.show');
        Route::post('/shifts/toggle-block/{user}', [ShiftController::class, 'toggleBlock'])->name('shifts.toggle_block');

        // صلاحيات تعديل وإلغاء الفواتير
        Route::put('/invoices/{id}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    });
});