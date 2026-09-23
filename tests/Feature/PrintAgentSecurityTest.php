<?php

namespace Tests\Feature;

use App\Models\PrinterJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PrintAgentSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_print_agent_can_fetch_and_update_job_status(): void
    {
        $deviceUuid = 'pos-terminal-99';
        $jobUuid = (string) Str::uuid();

        $user = \App\Models\User::factory()->create();
        $order = \App\Models\Order::create([
            'order_number' => 'ORD-20260923-00001',
            'type'         => 'takeaway',
            'status'       => 'open',
            'created_by'   => $user->id,
        ]);

        $job = PrinterJob::create([
            'uuid'        => $jobUuid,
            'device_uuid' => $deviceUuid,
            'type'        => 'kitchen',
            'order_id'    => $order->id,
            'payload'     => ['items' => ['Coffee']],
            'status'      => 'pending',
        ]);

        // 1. Fetch pending jobs for this device
        $response = $this->getJson('/api/print-agent/jobs', [
            'X-Device-UUID' => $deviceUuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.uuid', $jobUuid);

        // 2. Mark processing
        $processingResponse = $this->postJson("/api/print-agent/jobs/{$jobUuid}/processing", [], [
            'X-Device-UUID' => $deviceUuid,
        ]);

        $processingResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'processing');

        // 3. Mark complete
        $completeResponse = $this->postJson("/api/print-agent/jobs/{$jobUuid}/complete", [], [
            'X-Device-UUID' => $deviceUuid,
        ]);

        $completeResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'printed');
    }

    public function test_print_agent_rejects_unauthorized_token_when_configured(): void
    {
        config(['services.print_agent.secret_key' => 'super-secret-token-123']);

        $response = $this->getJson('/api/print-agent/jobs', [
            'X-Device-UUID' => 'pos-terminal-99',
        ]);

        $response->assertStatus(401);

        $validResponse = $this->getJson('/api/print-agent/jobs', [
            'X-Device-UUID'      => 'pos-terminal-99',
            'X-Agent-Secret-Key' => 'super-secret-token-123',
        ]);

        $validResponse->assertStatus(200);
    }
}
