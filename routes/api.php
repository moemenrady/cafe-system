<?php

use App\Http\Controllers\Api\PrintAgentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| POS Agent Heartbeat & Device Status Sync
|--------------------------------------------------------------------------
*/
Route::post('/pos/device-heartbeat', function (Request $request) {
    $deviceUuid = $request->input('device_uuid');
    $status = $request->input('status', 'offline');
    $activeRoles = $request->input('active_roles', []); // e.g. ['cashier', 'barista']
    $printers = $request->input('printers', []);

    if ($deviceUuid) {
        // إذا لم يتم إرسال active_roles صراحة، نستخرج الأدوار النشطة من الطابعات المتصلة
        if (empty($activeRoles) && is_array($printers)) {
            foreach ($printers as $role => $printerInfo) {
                if (($printerInfo['status'] ?? '') === 'online') {
                    $activeRoles[] = $role;
                }
            }
        }

        // تخزين حالة الجهاز والأدوار النشطة لمدة 45 ثانية في الكاش
        Cache::put("pos_device_{$deviceUuid}", [
            'status' => $status,
            'active_roles' => $activeRoles,
            'printers' => $printers,
            'last_seen' => now()->toIso8601String(),
        ], now()->addSeconds(45));
    }

    return response()->json([
        'success' => true,
        'message' => 'Heartbeat acknowledged',
        'server_time' => now()->toIso8601String(),
    ]);
});

/*
|--------------------------------------------------------------------------
| POS Printer Status Endpoint (For Front-End Health Check)
|--------------------------------------------------------------------------
*/
Route::get('/pos/printer-status', function (Request $request) {
    $deviceUuid = $request->header('X-Device-UUID') ?? $request->query('device_uuid', 'pos-cashier-01');
    $deviceStatus = Cache::get("pos_device_{$deviceUuid}");

    if (!$deviceStatus) {
        return response()->json([
            'success' => true,
            'connected' => false,
            'status' => 'offline',
            'message' => 'برنامج الطباعة غير متصل بالجهاز حالياً.',
            'device_uuid' => $deviceUuid,
            'printers' => null,
            'active_roles' => [],
            'last_seen' => null,
        ]);
    }

    $activeRoles = $deviceStatus['active_roles'] ?? [];
    if (empty($activeRoles) && !empty($deviceStatus['printers'])) {
        foreach ($deviceStatus['printers'] as $role => $printerInfo) {
            if (($printerInfo['status'] ?? '') === 'online') {
                $activeRoles[] = $role;
            }
        }
    }

    $isReady = ($deviceStatus['status'] ?? '') === 'ready';

    return response()->json([
        'success' => true,
        'connected' => true,
        'status' => $deviceStatus['status'] ?? 'offline',
        'is_ready' => $isReady,
        'device_uuid' => $deviceUuid,
        'printers' => $deviceStatus['printers'] ?? [],
        'active_roles' => $activeRoles,
        'last_seen' => $deviceStatus['last_seen'] ?? null,
    ]);
});

/*
|--------------------------------------------------------------------------
| Over-The-Air Auto Update Endpoint
|--------------------------------------------------------------------------
*/
Route::get('/pos/check-update', function (Request $request) {
    $currentVersion = $request->query('version', '1.0.0');
    
    // يمكنك تعديل رقم النسخة الأحدث ورابط التحميل عند إطلاق إصدار جديد
    $latestVersion = '1.0.0'; 
    $downloadUrl = url('/downloads/pos-agent-latest.exe');

    $updateAvailable = version_compare($currentVersion, $latestVersion, '<');

    return response()->json([
        'latest_version' => $latestVersion,
        'update_available' => $updateAvailable,
        'download_url' => $updateAvailable ? $downloadUrl : null,
        'mandatory' => false,
    ]);
});

/*
|--------------------------------------------------------------------------
| Print Agent Jobs Management
|--------------------------------------------------------------------------
*/
Route::prefix('print-agent')->middleware('print_agent_token')->group(function () {
    Route::get('/jobs', [PrintAgentController::class, 'jobs']);
    Route::post('/jobs/{uuid}/processing', [PrintAgentController::class, 'processing']);
    Route::post('/jobs/{uuid}/complete', [PrintAgentController::class, 'complete']);
    Route::post('/jobs/{uuid}/failed', [PrintAgentController::class, 'failed']);
});