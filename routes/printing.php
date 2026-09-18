
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web-Based Printing Management (Browser / POS UI)
|--------------------------------------------------------------------------
| Requires authenticated user session
*/

Route::middleware(['auth', 'verified'])->prefix('admin/printers')->group(function () {
    
    // صفحة عرض حالة الطابعات داخل لوحة التحكم
    // Route::get('/', [PrinterDashboardController::class, 'index'])->name('printers.index');

    // سجل مهام الطباعة السابقة (Audit Log)
    // Route::get('/logs', [PrinterLogController::class, 'index'])->name('printers.logs');

    // زر في الداشبورد لإعادة محاولة طباعة أوردر فشل
    // Route::post('/retry/{jobUuid}', [PrinterJobController::class, 'retry'])->name('printers.retry');
});








// <!-- <?php

// use App\Http\Controllers\Api\PrintAgentController;
// use Illuminate\Support\Facades\Route;


// Route::middleware(['auth', 'verified'])->group(function () {

//     Route::prefix('print-agent')->group(function () {

//         /*
//     |--------------------------------------------------------------------------
//     | Synchronization
//     |--------------------------------------------------------------------------
//     */

//         Route::get('/jobs', [
//             PrintAgentController::class,
//             'jobs',
//         ]);


//         /*
//     |--------------------------------------------------------------------------
//     | Print Job Status
//     |--------------------------------------------------------------------------
//     */

//         Route::post('/jobs/{uuid}/processing', [
//             PrintAgentController::class,
//             'processing',
//         ]);

//         Route::post('/jobs/{uuid}/complete', [
//             PrintAgentController::class,
//             'complete',
//         ]);

//         Route::post('/jobs/{uuid}/failed', [
//             PrintAgentController::class,
//             'failed',
//         ]);
//     });
// }); -->
