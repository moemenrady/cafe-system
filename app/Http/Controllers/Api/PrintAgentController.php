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
            ->whereIn('status', [
                'pending',
                'failed',
            ])
            ->orderBy('created_at')
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'uuid' => 'job-' . $job->id,
                    'type' => $job->type,
                    'printer_identifier' => in_array($job->type, ['kitchen', 'barista', 'waiter']) ? 'barista' : 'cashier',
                    'payload' => $job->payload,
                    'status' => $job->status,
                    'created_at' => $job->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    protected function resolveJob(string $uuid): ?PrinterJob
    {
        $id = str_starts_with($uuid, 'job-') ? (int) substr($uuid, 4) : (is_numeric($uuid) ? (int) $uuid : null);
        if ($id) {
            return PrinterJob::find($id);
        }
        return null;
    }

    /**
     * Mark print job as processing.
     */
    public function processing(
        Request $request,
        string $uuid
    ): JsonResponse {
        $job = $this->resolveJob($uuid);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404);
        }

        $job->update([
            'status' => 'printing',
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
        $job = $this->resolveJob($uuid);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404);
        }

        $job->update([
            'status' => 'printed',
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
        $job = $this->resolveJob($uuid);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Job not found.',
            ], 404);
        }

        $job->update([
            'status' => 'failed',
        ]);

        return response()->json([
            'success' => true,
            'data' => $job->fresh(),
        ]);
    }
}