<?php

namespace Tests\Feature;

use App\Helpers\InvoiceNumberHelper;
use App\Services\DocumentSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_number_sequence_increments_atomically(): void
    {
        $orderNum1 = DocumentSequenceService::getNextOrderNumber();
        $orderNum2 = DocumentSequenceService::getNextOrderNumber();
        $orderNum3 = DocumentSequenceService::getNextOrderNumber();

        $today = now()->format('Ymd');
        $this->assertSame("ORD-{$today}-00001", $orderNum1);
        $this->assertSame("ORD-{$today}-00002", $orderNum2);
        $this->assertSame("ORD-{$today}-00003", $orderNum3);
    }

    public function test_invoice_number_sequence_increments_atomically(): void
    {
        $inv1 = InvoiceNumberHelper::generate();
        $inv2 = InvoiceNumberHelper::generate();

        $today = now()->format('Ymd');
        $this->assertSame("INV-{$today}-00001", $inv1);
        $this->assertSame("INV-{$today}-00002", $inv2);
    }
}
