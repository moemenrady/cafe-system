<?php

namespace App\Events;

use App\Models\PrinterJob;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrinterJobCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PrinterJob $job,
    ) {
    }

    public function broadcastOn(): array
    {
        $deviceUuid = $this->job->device_uuid ?: 'pos-cashier-01';

        return [
            new Channel('print-agent.' . $deviceUuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'print.job';
    }

    public function broadcastWith(): array
    {
        $printerIdentifier = $this->job->printer_identifier
            ?: (in_array($this->job->type, ['kitchen', 'barista', 'waiter']) ? 'barista' : 'cashier');

        return [
            'job' => [
                'uuid' => $this->job->uuid ?: ('job-' . ($this->job->id ?? uniqid())),
                'type' => $this->job->type,
                'printer_identifier' => $printerIdentifier,
                'payload' => $this->job->payload,
                'status' => $this->job->status,
            ],
        ];
    }
}

// Support alternative class name to prevent PSR-4 naming collisions
if (!class_exists('App\Events\PrintJobCreated', false)) {
    class_alias(PrinterJobCreated::class, 'App\Events\PrintJobCreated');
}