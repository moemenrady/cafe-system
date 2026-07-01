<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {});


Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');
