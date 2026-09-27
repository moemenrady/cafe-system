<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TableController;

/*
|--------------------------------------------------------------------------
| Tables Routes – إدارة الطاولات
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. راوتس خاصة بالإدارة (أدمن ومشرف فقط)
// ==========================================
// ⚠️ مهم: مسارات الإدارة لازم تتسجل الأول عشان /tables/create ميتداخلش مع /tables/{table}
Route::middleware(['auth', 'verified', 'role:admin|supervisor'])->group(function () {

    // تسجيل صريح لكل مسار بترتيب الأولوية الصحيح
    Route::get('/tables/create', [TableController::class, 'create'])->name('tables.create');
    Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
    Route::get('/tables/{table}/edit', [TableController::class, 'edit'])
        ->whereNumber('table')
        ->name('tables.edit');
    Route::put('/tables/{table}', [TableController::class, 'update'])
        ->whereNumber('table')
        ->name('tables.update');
    Route::patch('/tables/{table}', [TableController::class, 'update'])
        ->whereNumber('table');
    Route::delete('/tables/{table}', [TableController::class, 'destroy'])
        ->whereNumber('table')
        ->name('tables.destroy');
    Route::get('/tables', [TableController::class, 'index'])->name('tables.index');

    Route::post('/tables/{table}/toggle', [TableController::class, 'toggle'])
        ->whereNumber('table')
        ->name('tables.toggle');
});

// ==========================================
// 2. راوتس متاحة للكل (كاشير، أدمن، مشرف)
// ==========================================
Route::middleware(['auth', 'verified'])->group(function () {

    // ⚠️ مهم: راوت الترابيزات المشغولة لازم يكون قبل show عشان ميجيبش 404
    Route::get('/tables/busy', [TableController::class, 'busyTables'])->name('tables.busy_tables');

    // صفحة تفاصيل أي ترابيزة – قيّد لأرقام فقط لمنع التقاط busy أو create
    Route::get('/tables/{table}', [TableController::class, 'show'])
        ->whereNumber('table')
        ->name('tables.show');

    // JSON endpoint للـ POS
    Route::get('/pos/tables', [TableController::class, 'posIndex'])->name('pos.tables');
});