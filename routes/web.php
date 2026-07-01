<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome', compact("", "", ""));
});
Route::get('/demo', [UserController::class, "index"]);




require __DIR__ . '/auth.php';
require __DIR__ . '/dashboard.php';
