<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Table;
use App\Models\User;
use App\Services\OrderCalculationService;
use App\Services\OrderService;
use App\Services\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosLifecycleAndVatTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Menu $coffee;
    protected Menu $cake;
    protected Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $category = Category::create(['name' => 'Hot Drinks']);

        $this->coffee = Menu::create([
            'name' => 'Americano',
            'category_id' => $category->id,
            'price' => 50.00,
            'is_available' => true,
        ]);

        $this->cake = Menu::create([
            'name' => 'Cheesecake',
            'category_id' => $category->id,
            'price' => 50.00,
            'is_available' => true,
        ]);

        $this->table = Table::create([
            'name' => 'Table 1',
            'capacity' => 4,
            'is_active' => true,
        ]);
    }

    /**
     * 1. Dine-in VAT = 14%
     * Subtotal = 100, VAT = 14, Total = 114
     */
    public function test_dine_in_vat_is_14_percent(): void
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'dine_in',
            'table_id' => $this->table->id,
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 1], // 50
                ['menu_id' => $this->cake->id, 'quantity' => 1],   // 50
            ],
        ]);

        $this->assertEquals(100.00, (float) $order->subtotal);
        $this->assertEquals(14.00, (float) $order->vat);
        $this->assertEquals(114.00, (float) $order->total);
        $this->assertEquals('open', $order->status);
    }

    /**
     * 2. Takeaway VAT = 0%
     * Subtotal = 100, VAT = 0, Total = 100
     */
    public function test_takeaway_vat_is_zero(): void
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'takeaway',
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 1], // 50
                ['menu_id' => $this->cake->id, 'quantity' => 1],   // 50
            ],
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(100.00, (float) $order->subtotal);
        $this->assertEquals(0.00, (float) $order->vat);
        $this->assertEquals(100.00, (float) $order->total);
        $this->assertEquals('completed', $order->status);
        $this->assertEquals('paid', $order->payment_status);

        // Check Invoice
        $invoice = $order->invoice;
        $this->assertNotNull($invoice);
        $this->assertEquals(100.00, (float) $invoice->subtotal);
        $this->assertEquals(0.00, (float) $invoice->vat);
        $this->assertEquals(100.00, (float) $invoice->total);
    }

    /**
     * 3. Delivery VAT = 0%
     * Subtotal = 100, VAT = 0, Total = 100
     */
    public function test_delivery_vat_is_zero(): void
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'delivery',
            'phone' => '01012345678',
            'delivery_address' => 'Cairo, Egypt',
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 2], // 100
            ],
        ]);

        $this->assertEquals(100.00, (float) $order->subtotal);
        $this->assertEquals(0.00, (float) $order->vat);
        $this->assertEquals(100.00, (float) $order->total);
        $this->assertEquals('waiting_delivery', $order->status);
    }

    /**
     * 4. Invoice is idempotent (no duplicate invoices on multiple calls)
     */
    public function test_invoice_creation_is_idempotent(): void
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'dine_in',
            'table_id' => $this->table->id,
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 1],
            ],
        ]);

        $inv1 = $orderService->printInvoice($order);
        $inv2 = $orderService->printInvoice($order);

        $this->assertEquals($inv1->id, $inv2->id);
        $this->assertEquals($inv1->invoice_number, $inv2->invoice_number);
        $this->assertEquals(1, $order->invoice()->count());
    }

    /**
     * 5. Occupied table opens existing order (no duplicates)
     */
    public function test_occupied_table_returns_existing_active_order(): void
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'dine_in',
            'table_id' => $this->table->id,
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($this->table->fresh()->isOccupied());
        $this->assertEquals($order->id, $this->table->fresh()->currentOrder->id);
    }

    /**
     * 6. Close table releases table and marks order completed
     */
    public function test_close_table_releases_table_and_marks_completed(): void
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'dine_in',
            'table_id' => $this->table->id,
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 1],
            ],
        ]);

        $this->assertTrue($this->table->fresh()->isOccupied());

        $closedOrder = $orderService->closeTable($order, ['payment_method' => 'cash']);

        $this->assertEquals('completed', $closedOrder->status);
        $this->assertEquals('paid', $closedOrder->payment_status);
        $this->assertNotNull($closedOrder->closed_at);
        $this->assertFalse($this->table->fresh()->isOccupied());
    }

    /**
     * 7. Completed order cannot become active accidentally
     */
    public function test_completed_order_cannot_become_active(): void
    {
        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'dine_in',
            'table_id' => $this->table->id,
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 1],
            ],
        ]);

        $orderService->closeTable($order);

        // Open a new order on the same table
        $newOrder = $orderService->createOrder([
            'type' => 'dine_in',
            'table_id' => $this->table->id,
            'items' => [
                ['menu_id' => $this->cake->id, 'quantity' => 1],
            ],
        ]);

        $this->assertNotEquals($order->id, $newOrder->id);
        $this->assertEquals('completed', $order->fresh()->status);
        $this->assertEquals('open', $newOrder->fresh()->status);
        $this->assertEquals($newOrder->id, $this->table->fresh()->currentOrder->id);
    }

    /**
     * 8. Backend ignores manipulated frontend totals (Single source of truth)
     */
    public function test_backend_ignores_manipulated_frontend_totals(): void
    {
        $orderService = app(OrderService::class);

        // Client sends fake subtotal=10, vat=1, total=11 for 50 EGP item
        $order = $orderService->createOrder([
            'type' => 'dine_in',
            'table_id' => $this->table->id,
            'subtotal' => 10.00,
            'vat' => 1.00,
            'total' => 11.00,
            'items' => [
                ['menu_id' => $this->coffee->id, 'quantity' => 1], // Real price is 50.00
            ],
        ]);

        // Backend must recalculate from database Menu price
        $this->assertEquals(50.00, (float) $order->fresh()->subtotal);
        $this->assertEquals(7.00, (float) $order->fresh()->vat); // 50 * 0.14
        $this->assertEquals(57.00, (float) $order->fresh()->total);
    }
}
