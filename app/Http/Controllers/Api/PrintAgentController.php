<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrinterJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrintAgentController extends Controller
{
    /**
     * Get pending print jobs for this device.
     *
     * Used when the agent starts or reconnects.
     */
    public function jobs(Request $request): JsonResponse
    {
        $deviceUuid = $request->header('X-Device-UUID');

        if (!$deviceUuid) {
            return response()->json([
                'success' => false,
                'message' => 'X-Device-UUID header is required.',
            ], 422);
        }

        $jobs = PrinterJob::query()
            ->where('device_uuid', $deviceUuid)
            ->whereIn('status', [
                'pending',
                'failed',
            ])
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }


    /**
     * Mark print job as processing.
     */
    public function processing(
        Request $request,
        string $uuid
    ): JsonResponse {

        $deviceUuid = $request->header('X-Device-UUID');

        if (!$deviceUuid) {
            return response()->json([
                'success' => false,
                'message' => 'X-Device-UUID header is required.',
            ], 422);
        }

        $job = PrinterJob::query()
            ->where('uuid', $uuid)
            ->where('device_uuid', $deviceUuid)
            ->firstOrFail();

        $job->update([
            'status' => 'processing',
            'attempts' => $job->attempts + 1,
            'started_at' => now(),
            'error_message' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $job->fresh(),
        ]);
    }


    /**
     * Mark print job as successfully printed.
     */
    public function complete(
        Request $request,
        string $uuid
    ): JsonResponse {

        $deviceUuid = $request->header('X-Device-UUID');

        if (!$deviceUuid) {
            return response()->json([
                'success' => false,
                'message' => 'X-Device-UUID header is required.',
            ], 422);
        }

        $job = PrinterJob::query()
            ->where('uuid', $uuid)
            ->where('device_uuid', $deviceUuid)
            ->firstOrFail();

        $job->update([
            'status' => 'printed',
            'printed_at' => now(),
            'error_message' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $job->fresh(),
        ]);
    }


    /**
     * Mark print job as failed.
     */
    public function failed(
        Request $request,
        string $uuid
    ): JsonResponse {

        $deviceUuid = $request->header('X-Device-UUID');

        if (!$deviceUuid) {
            return response()->json([
                'success' => false,
                'message' => 'X-Device-UUID header is required.',
            ], 422);
        }

        $job = PrinterJob::query()
            ->where('uuid', $uuid)
            ->where('device_uuid', $deviceUuid)
            ->firstOrFail();

        $job->update([
            'status' => 'failed',
            'error_message' => $request->input('error_message'),
        ]);

        return response()->json([
            'success' => true,
            'data' => $job->fresh(),
        ]);
    }
}