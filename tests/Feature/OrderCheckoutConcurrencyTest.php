<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\Table;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class OrderCheckoutConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_release_upon_checkout_and_double_checkout_prevention(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($user);

        $table = Table::create(['name' => 'Table 1', 'is_active' => true]);
        $category = Category::create(['name' => 'Drinks']);

        $item = InventoryItem::create([
            'name'          => 'Beans',
            'quantity'      => 500,
            'unit'          => 'g',
            'reorder_level' => 10,
            'unit_price'    => 1,
        ]);

        $menu = Menu::create([
            'name'         => 'Espresso',
            'category_id'  => $category->id,
            'price'        => 30.00,
            'is_available' => true,
        ]);

        Recipe::create([
            'menu_item_id'      => $menu->id,
            'inventory_item_id' => $item->id,
            'quantity_used'     => 10,
        ]);

        $orderService = app(OrderService::class);

        // 1. Create Dine-In Order
        $order = $orderService->createOrder([
            'type'     => 'dine_in',
            'table_id' => $table->id,
            'items'    => [
                ['menu_id' => $menu->id, 'quantity' => 2],
            ],
            'discount' => 10.00,
        ]);

        // Table must now be occupied
        $this->assertTrue($table->isOccupied());
        $this->assertSame('50.00', (string) $order->fresh()->total); // 60 subtotal - 10 discount = 50
        $this->assertSame('10.00', (string) $order->fresh()->discount);

        // 2. Perform checkout
        $orderService->checkout($order, [
            'payment_method' => 'card',
        ]);

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);

        // Table must now be released!
        $this->assertFalse($table->isOccupied());

        // 3. Double checkout attempt must be rejected
        $this->expectException(InvalidArgumentException::class);
        $orderService->checkout($order->fresh());
    }
}
