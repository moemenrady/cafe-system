<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccupiedTablesAndNavbarDropdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_access_busy_tables_page_and_view_occupied_and_vacant_tables(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'name' => 'أحمد كاشير',
        ]);

        // Create tables
        $occupiedTable = Table::create([
            'name'      => 'طاولة رقم 5',
            'capacity'  => 4,
            'area'      => 'الصالة الرئيسية',
            'is_active' => true,
        ]);

        $vacantTable = Table::create([
            'name'      => 'طاولة رقم 2',
            'capacity'  => 2,
            'area'      => 'التراس',
            'is_active' => true,
        ]);

        // Create an active order on $occupiedTable
        $category = \App\Models\Category::create([
            'name' => 'مشروبات ساخنة',
        ]);

        $menuItem = Menu::create([
            'name'        => 'كابتشينو كراميل',
            'category_id' => $category->id,
            'price'       => 50.00,
            'status'      => 'active',
            'recipe_type' => 'instant',
        ]);

        $order = Order::create([
            'order_number'   => 'ORD-TABLE-55',
            'type'           => 'dine_in',
            'table_id'       => $occupiedTable->id,
            'status'         => 'open',
            'payment_status' => 'pending',
            'total'          => 100.00,
            'created_by'     => $cashier->id,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'menu_id'  => $menuItem->id,
            'quantity' => 2,
            'price'    => 50.00,
            'total'    => 100.00,
        ]);

        $this->actingAs($cashier);

        $response = $this->get(route('tables.busy_tables'));
        $response->assertStatus(200);
        $response->assertSee('الترابيزات المشغولة وحالة الصالة');
        $response->assertSee('طاولة رقم 5');
        $response->assertSee('طاولة رقم 2');
        $response->assertSee('ORD-TABLE-55');
        $response->assertSee('100.00');
        $response->assertSee(route('tables.show', $occupiedTable->id));
        $response->assertSee(route('tables.show', $vacantTable->id));
    }

    public function test_cashier_can_open_any_table_page_without_forbidden_error(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $table = Table::create([
            'name'      => 'طاولة رقم 9',
            'capacity'  => 6,
            'area'      => 'صالة العائلات',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);

        // Cashier opens table show page directly
        $response = $this->get(route('tables.show', $table->id));
        $response->assertStatus(200);
        $response->assertSee('طاولة رقم 9');
        $response->assertSee('صالة العائلات');
        $response->assertSee(route('tables.busy_tables')); // Back button for employee points to busy_tables
    }

    public function test_navbar_contains_dropdown_with_arrow_and_logout_button_in_both_header_and_sidebar(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
            'name' => 'محمد سمير',
        ]);

        $this->actingAs($cashier);

        $response = $this->get(route('tables.busy_tables'));
        $response->assertStatus(200);

        // Header profile dropdown and arrow
        $response->assertSee('headerProfileChevron');
        $response->assertSee('headerProfileDropdown');
        $response->assertSee('toggleHeaderProfileDropdown');

        // Sidebar profile dropdown and arrow
        $response->assertSee('profileChevron');
        $response->assertSee('profileDropdown');
        $response->assertSee('toggleProfileDropdown');

        // Logout route present in forms
        $response->assertSee(route('logout'));
        $response->assertSee('تسجيل الخروج');
    }
}
