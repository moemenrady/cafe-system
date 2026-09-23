<?php

use Illuminate\Support\Facades\Artisan;
use App\Models\PrinterJob;
use App\Events\PrinterJobCreated;

Artisan::command('print:test {id=65}', function ($id) {
    $job = PrinterJob::find($id);
    if (!$job) {
        $this->error("Job not found: {$id}");
        return 1;
    }
    $this->info("Dispatching event for job {$job->id}...");
    try {
        PrinterJobCreated::dispatch($job);
        $this->info("BROADCAST_SUCCESS");
    } catch (\Throwable $e) {
        $this->error("BROADCAST_FAILED: " . $e->getMessage());
        return 1;
    }
    return 0;
});
