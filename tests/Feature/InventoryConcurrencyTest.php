<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InventoryConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_deduction_and_insufficient_stock_exception(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($user);

        $category = Category::create(['name' => 'Coffee']);
        $item = InventoryItem::create([
            'name'          => 'Coffee Beans',
            'quantity'      => 25.00,
            'unit'          => 'g',
            'reorder_level' => 5,
            'unit_price'    => 0.5,
        ]);

        $menu = Menu::create([
            'name'         => 'Single Espresso',
            'category_id'  => $category->id,
            'price'        => 20.00,
            'is_available' => true,
        ]);

        Recipe::create([
            'menu_item_id'      => $menu->id,
            'inventory_item_id' => $item->id,
            'quantity_used'     => 10.00,
        ]);

        $orderService = app(OrderService::class);

        // 1. Order 2 espressos (uses 20g)
        $order = $orderService->createOrder([
            'type'  => 'takeaway',
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 2],
            ],
        ]);

        $this->assertEquals(5.00, (float) $item->fresh()->quantity);
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'type'              => 'sale',
            'quantity'          => -20.00,
            'balance_after'     => 5.00,
        ]);

        // 2. Ordering another 2 espressos (needs 20g, only 5g left) must throw RuntimeException
        $this->expectException(RuntimeException::class);
        $orderService->createOrder([
            'type'  => 'takeaway',
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 2],
            ],
        ]);
    }

    public function test_invoice_cancellation_restores_inventory_and_logs_sale_cancel(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $this->actingAs($supervisor);

        $category = Category::create(['name' => 'Dessert']);
        $item = InventoryItem::create([
            'name'          => 'Chocolate',
            'quantity'      => 10.00,
            'unit'          => 'pcs',
            'reorder_level' => 2,
            'unit_price'    => 5,
        ]);

        $menu = Menu::create([
            'name'         => 'Cake',
            'category_id'  => $category->id,
            'price'        => 50.00,
            'is_available' => true,
        ]);

        Recipe::create([
            'menu_item_id'      => $menu->id,
            'inventory_item_id' => $item->id,
            'quantity_used'     => 1.00,
        ]);

        $orderService = app(OrderService::class);
        $order = $orderService->createOrder([
            'type'  => 'takeaway',
            'items' => [['menu_id' => $menu->id, 'quantity' => 2]],
        ]);

        $this->assertEquals(8.00, (float) $item->fresh()->quantity);
        $invoice = Invoice::where('order_id', $order->id)->firstOrFail();

        // Supervisor deletes invoice
        $response = $this->deleteJson(route('invoices.destroy', $invoice->id));
        $response->assertStatus(200);

        // Inventory should be restored to 10.00
        $this->assertEquals(10.00, (float) $item->fresh()->quantity);

        // sale_cancel movement must be logged
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $item->id,
            'type'              => 'sale_cancel',
            'quantity'          => 2.00,
        ]);

        // Linked order should be cancelled
        $this->assertSame('cancelled', $order->fresh()->status);
    }
}
