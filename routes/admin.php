<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\PurchaseInvoiceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\PasswordController;

Route::middleware(['auth', 'verified'])->group(function () {

    // 🔒 1. مسارات إدارة الموظفين والرواتب (للمدراء والمشرفين)
    Route::middleware('role:admin|supervisor')->group(function () {
        Route::get('/employees/export', [EmployeeController::class, 'exportAll'])->name('employees.export');
        Route::get('/employees/{employee}/export', [EmployeeController::class, 'exportSingle'])->name('employees.exportSingle');
        Route::post('/employees/{employee}/adjustments', [EmployeeController::class, 'addAdjustment'])->name('employees.addAdjustment');
        Route::delete('/employees/adjustments/{adjustment}', [EmployeeController::class, 'deleteAdjustment'])->name('employees.deleteAdjustment');
        Route::post('/employees/payrolls/{payroll}/pay', [EmployeeController::class, 'payMonth'])->name('employees.payMonth');
        Route::post('/employees/{employee}/pay-all', [EmployeeController::class, 'payAllDues'])->name('employees.payAllDues');
        Route::resource('employees', EmployeeController::class);
    });

    // 🔒 2. صلاحيات المدراء أولاً (Admins Only)
    // تم تقديمها للأعلى ليتم قراءة مسار /recipes/create قبل المسار العام المفتوح
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/movements', [InvoiceController::class, 'movements'])->name('admin.movements');

        // 🛠️ مسارات التحكم بالوصفات (خاص بالأدمن)
        Route::get('/recipes/create', [RecipeController::class, 'create'])->name('recipes.create');
        Route::post('/recipes', [RecipeController::class, 'store'])->name('recipes.store');
        Route::get('/recipes/{recipe}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
        Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy'])->name('recipes.destroy');
        Route::put('/recipes/update/{recipe}', [RecipeController::class, 'update'])->name('recipes.update');
        // مسارات فواتير الشراء وإدارتها
        Route::get('/inventory/ajax-search', [PurchaseInvoiceController::class, 'searchInventoryItems'])->name('inventory.ajaxSearch');
        Route::resource('purchase-invoices', PurchaseInvoiceController::class);
        Route::get('/management', function () {
            return view('management.index');
        })->name('management.index');

        // إدارة الحسابات والأمان (Admin & Secure Auth)
        Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
        Route::post('register', [RegisteredUserController::class, 'store']);
        Route::get('/users', [RegisteredUserController::class, 'index'])->name('users.create');
        Route::get('/users/ajax-search', [RegisteredUserController::class, 'ajaxSearch'])->name('users.ajaxSearch');
        Route::get('register/verify', [RegisteredUserController::class, 'showVerifyForm'])->name('register.verify.show');
        Route::post('register/verify', [RegisteredUserController::class, 'verifyCode'])->name('register.verify.post');
        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    });

    // 📜 2. صلاحيات رؤية وعرض الوصفات (متاحة للأدمن، المشرف، والباريستا)
    // تم تأخيرها للأسفل حتى لا تعترض رابط الإنشاء (create)
    Route::middleware('role:admin|supervisor|barista')->group(function () {
        Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
        Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->name('recipes.show');
    });
});
