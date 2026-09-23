<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TableController;

/*
|--------------------------------------------------------------------------
| Tables Routes – إدارة الطاولات
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. راوتس متاحة للكل (كاشير، أدمن، مشرف)
// ==========================================
Route::middleware(['auth', 'verified'])->group(function () {
    
    // ⚠️ مهم جداً: راوت الترابيزات المشغولة لازم يكون فوق show عشان ميجيبش 404
    Route::get('/tables/busy', [TableController::class, 'busyTables'])->name('tables.busy_tables');
    
    // صفحة تفاصيل أي ترابيزة والطلبات المرتبطة بها (متاحة للموظف والإدارة)
    Route::get('/tables/{table}', [TableController::class, 'show'])->name('tables.show');
    
    // JSON endpoint للـ POS
    Route::get('/pos/tables', [TableController::class, 'posIndex'])->name('pos.tables');
});

// ==========================================
// 2. راوتس خاصة بالإدارة (أدمن ومشرف فقط)
// ==========================================
Route::middleware(['auth', 'verified', 'role:admin|supervisor'])->group(function () {
    
    Route::resource('tables', TableController::class)->except(['show']);
    
    Route::post('/tables/{table}/toggle', [TableController::class, 'toggle'])->name('tables.toggle');
});