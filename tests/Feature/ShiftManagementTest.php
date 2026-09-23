<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ShiftManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_open_record_drop_and_close_shift_with_variance(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier);

        $shiftService = app(ShiftService::class);

        // 1. Open shift with 500 float
        $shift = $shiftService->openShift($cashier, 500.00);

        $this->assertSame('open', $shift->status);
        $this->assertEquals(500.00, (float) $shift->opening_float);
        $this->assertEquals(500.00, (float) $shift->expected_cash);

        // Attempting to open another shift must fail
        $this->expectException(InvalidArgumentException::class);
        $shiftService->openShift($cashier, 200.00);
    }

    public function test_shift_closing_calculates_cash_and_variance_correctly(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier);

        $shiftService = app(ShiftService::class);

        // Open shift with 300 float
        $shift = $shiftService->openShift($cashier, 300.00);

        // Record a cash drop of 100
        $shiftService->recordCashDrop($shift, 100.00, 'Mid-day cash deposit to safe');

        // Create mock invoices under this shift: 250 cash, 150 card
        Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'total'          => 250.00,
            'discount'       => 0,
            'payment_method' => 'cash',
            'shift_id'       => $shift->id,
            'created_by'     => $cashier->id,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-TEST-002',
            'total'          => 150.00,
            'discount'       => 0,
            'payment_method' => 'card',
            'shift_id'       => $shift->id,
            'created_by'     => $cashier->id,
        ]);

        // Expected cash in drawer = 300 (float) + 250 (cash sales) - 100 (drop) = 450.00
        // Cashier counts 440.00 (Shortage of -10.00)
        $closed = $shiftService->closeShift($shift, 440.00, 'Missing 10 EGP in drawer');

        $this->assertSame('closed', $closed->status);
        $this->assertEquals(250.00, (float) $closed->cash_sales);
        $this->assertEquals(150.00, (float) $closed->card_sales);
        $this->assertEquals(450.00, (float) $closed->expected_cash);
        $this->assertEquals(440.00, (float) $closed->actual_cash);
        $this->assertEquals(-10.00, (float) $closed->difference);
    }

    public function test_shift_handles_cash_card_and_instapay_sales_in_summary_and_views(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier);

        $shiftService = app(ShiftService::class);

        // Open shift with 500 float
        $shift = $shiftService->openShift($cashier, 500.00);

        // Create invoices: Cash: 1,025, Visa/Card: 130, InstaPay: 350
        Invoice::create([
            'invoice_number' => 'INV-CASH-1025',
            'total'          => 1025.00,
            'discount'       => 0,
            'payment_method' => 'cash',
            'shift_id'       => $shift->id,
            'created_by'     => $cashier->id,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-CARD-130',
            'total'          => 130.00,
            'discount'       => 0,
            'payment_method' => 'card',
            'shift_id'       => $shift->id,
            'created_by'     => $cashier->id,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-INSTA-350',
            'total'          => 350.00,
            'discount'       => 0,
            'payment_method' => 'InstaPay',
            'shift_id'       => $shift->id,
            'created_by'     => $cashier->id,
        ]);

        // Check live summary
        $summary = $shiftService->getShiftLiveSummary($shift);
        $this->assertEquals(1025.00, (float) $summary['cash_sales']);
        $this->assertEquals(130.00, (float) $summary['card_sales']);
        $this->assertEquals(350.00, (float) $summary['instapay_sales']);
        $this->assertEquals(1505.00, (float) $summary['total_sales']);
        // Expected drawer cash is opening_float (500) + cash_sales (1025) = 1525 (card and instapay are electronic)
        $this->assertEquals(1525.00, (float) $summary['expected_cash']);

        // Check my_shift view renders all three payment methods
        $response = $this->get(route('shifts.my_shift'));
        $response->assertStatus(200);
        $response->assertSee('كاش: 1,025', false);
        $response->assertSee('فيزا: 130', false);
        $response->assertSee('إنستا باي: 350', false);

        // Close shift with exact expected cash
        $closed = $shiftService->closeShift($shift, 1525.00, 'All matched');
        $this->assertSame('closed', $closed->status);
        $this->assertEquals(1025.00, (float) $closed->cash_sales);
        $this->assertEquals(130.00, (float) $closed->card_sales);
        $this->assertEquals(350.00, (float) $closed->instapay_sales);
        $this->assertEquals(1525.00, (float) $closed->expected_cash);
        $this->assertEquals(1525.00, (float) $closed->actual_cash);
        $this->assertEquals(0.00, (float) $closed->difference);

        // Check closed shift show view renders instapay
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $showResponse = $this->get(route('shifts.show', $shift->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('إنستا باي', false);
        $showResponse->assertSee('350', false);
    }
}

