<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\InventoryItem;
use App\Models\Menu;
use App\Models\Recipe;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_takeaway_order_is_completed_immediately_and_inventory_is_deducted(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::create(['name' => 'Coffee']);
        $inventoryItem = InventoryItem::create([
            'name' => 'Milk',
            'quantity' => 20,
            'unit' => 'L',
            'reorder_level' => 5,
            'unit_price' => 2.5,
        ]);

        $menu = Menu::create([
            'name' => 'Latte',
            'category_id' => $category->id,
            'price' => 15,
            'is_available' => true,
        ]);

        Recipe::create([
            'menu_item_id' => $menu->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity_used' => 2,
        ]);

        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'takeaway',
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 2],
            ],
            'notes' => 'Takeaway order',
        ]);

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'menu_id' => $menu->id, 'quantity' => 2]);
        $this->assertSame(16, $inventoryItem->fresh()->quantity);
        $this->assertDatabaseHas('invoices', ['order_id' => $order->id]);
        $this->assertDatabaseHas('printer_jobs', ['order_id' => $order->id, 'type' => 'kitchen']);
        $this->assertDatabaseHas('printer_jobs', ['order_id' => $order->id, 'type' => 'customer']);
    }

    public function test_dine_in_order_keeps_status_open_and_creates_only_kitchen_and_waiter_jobs(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $category = Category::create(['name' => 'Food']);
        $inventoryItem = InventoryItem::create([
            'name' => 'Tomato',
            'quantity' => 10,
            'unit' => 'kg',
            'reorder_level' => 2,
            'unit_price' => 1.5,
        ]);

        $menu = Menu::create([
            'name' => 'Pizza',
            'category_id' => $category->id,
            'price' => 20,
            'is_available' => true,
        ]);

        Recipe::create([
            'menu_item_id' => $menu->id,
            'inventory_item_id' => $inventoryItem->id,
            'quantity_used' => 1,
        ]);

        $orderService = app(OrderService::class);

        $order = $orderService->createOrder([
            'type' => 'dine_in',
            'table_number' => 5,
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1],
            ],
        ]);

        $this->assertSame('open', $order->fresh()->status);
        $this->assertSame('pending', $order->fresh()->payment_status);
        $this->assertSame(9, $inventoryItem->fresh()->quantity);
        $this->assertDatabaseMissing('invoices', ['order_id' => $order->id]);
        $this->assertDatabaseHas('printer_jobs', ['order_id' => $order->id, 'type' => 'kitchen']);
        $this->assertDatabaseHas('printer_jobs', ['order_id' => $order->id, 'type' => 'waiter']);
    }

    public function test_order_controller_warns_when_printer_is_disconnected(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        \Illuminate\Support\Facades\Cache::forget('pos_device_pos-cashier-01');

        $category = Category::create(['name' => 'Juice']);
        $menu = Menu::create([
            'name' => 'Orange Juice',
            'category_id' => $category->id,
            'price' => 10,
            'is_available' => true,
        ]);

        $response = $this->postJson(route('orders.store'), [
            'type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'success' => false,
                     'printer_warning' => true,
                 ]);
    }

    public function test_order_controller_allows_order_when_force_flag_is_true(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        \Illuminate\Support\Facades\Cache::forget('pos_device_pos-cashier-01');

        $category = Category::create(['name' => 'Juice']);
        $menu = Menu::create([
            'name' => 'Mango Juice',
            'category_id' => $category->id,
            'price' => 12,
            'is_available' => true,
        ]);

        $response = $this->postJson(route('orders.store'), [
            'type' => 'takeaway',
            'payment_method' => 'cash',
            'force' => true,
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                 ]);
    }

    public function test_order_controller_succeeds_when_printer_is_ready(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        \Illuminate\Support\Facades\Cache::put('pos_device_pos-cashier-01', [
            'status' => 'ready',
            'active_roles' => ['cashier', 'barista'],
            'printers' => [
                'cashier' => ['status' => 'online'],
                'barista' => ['status' => 'online'],
            ],
            'last_seen' => now()->toIso8601String(),
        ], 60);

        $category = Category::create(['name' => 'Juice']);
        $menu = Menu::create([
            'name' => 'Apple Juice',
            'category_id' => $category->id,
            'price' => 14,
            'is_available' => true,
        ]);

        $response = $this->postJson(route('orders.store'), [
            'type' => 'takeaway',
            'payment_method' => 'cash',
            'items' => [
                ['menu_id' => $menu->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                 ]);
    }
}
