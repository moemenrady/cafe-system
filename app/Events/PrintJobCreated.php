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
        return [
            new Channel(
                'print-agent.' . $this->job->device_uuid
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'print.job';
    }

    public function broadcastWith(): array
    {
        return [
            'job' => [
                'uuid' => $this->job->uuid,

                'type' => $this->job->type,

                'printer_identifier' => $this->job->printer_identifier,

                'payload' => $this->job->payload,

                'status' => $this->job->status,
            ],
        ];
    }
}