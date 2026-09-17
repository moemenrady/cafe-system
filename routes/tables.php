<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TableController;

/*
|--------------------------------------------------------------------------
| Tables Routes – إدارة الطاولات
|--------------------------------------------------------------------------
|
| CRUD كامل متاح للأدمن والمشرف.
| الكاشير يشوف الطاولات في الـ POS عبر /pos/tables (JSON endpoint).
|
*/

// CRUD الطاولات – للأدمن والمشرف فقط
Route::middleware(['auth', 'verified', 'role:admin|supervisor'])
    ->group(function () {
        Route::resource('tables', TableController::class);
        Route::post('/tables/{table}/toggle', [TableController::class, 'toggle'])
            ->name('tables.toggle');
    });

// JSON endpoint للـ POS – متاح لكل المستخدمين المسجلين (الكاشير يحتاجها)
Route::middleware(['auth', 'verified'])
    ->get('/pos/tables', [TableController::class, 'posIndex'])
    ->name('pos.tables');
