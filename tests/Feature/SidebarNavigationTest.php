<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_gets_strictly_only_the_six_allowed_sidebar_items(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $available = $cashier->getAvailableSidebarItems();
        $keys = array_keys($available);

        // المستخدم بصلاحية الموظف يظهر له فقط: بيع ومينيو والترابيزات المشغولة والمصروفات والشيفت ورديتي واعدادات بسس
        $this->assertEqualsCanonicalizing(
            ['pos', 'busy_tables', 'menu', 'shifts', 'expenses', 'settings'],
            $keys
        );
        $this->assertCount(6, $keys);

        // التأكد من أن البيع هو الأبرز (Hero)
        $this->assertTrue($available['pos']['is_hero'] ?? false);

        // التأكد من عدم وجود أي عنصر آخر
        $this->assertArrayNotHasKey('tables', $available);
        $this->assertArrayNotHasKey('categories', $available);
        $this->assertArrayNotHasKey('recipes', $available);
        $this->assertArrayNotHasKey('shifts_management', $available);
        $this->assertArrayNotHasKey('sales_invoices', $available);
        $this->assertArrayNotHasKey('inventory', $available);
        $this->assertArrayNotHasKey('customers', $available);
        $this->assertArrayNotHasKey('employees', $available);
        $this->assertArrayNotHasKey('management', $available);
        $this->assertArrayNotHasKey('purchase_invoices', $available);
    }

    public function test_manager_gets_full_grouped_sidebar_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $available = $admin->getAvailableSidebarItems();

        $this->assertArrayHasKey('pos', $available);
        $this->assertArrayHasKey('tables', $available);
        $this->assertArrayHasKey('busy_tables', $available);
        $this->assertArrayHasKey('menu', $available);
        $this->assertArrayHasKey('categories', $available);
        $this->assertArrayHasKey('recipes', $available);
        $this->assertArrayHasKey('shifts_management', $available);
        $this->assertArrayHasKey('shifts', $available);
        $this->assertArrayHasKey('inventory', $available);
        $this->assertArrayHasKey('employees', $available);
        $this->assertArrayHasKey('management', $available);
        $this->assertArrayHasKey('settings', $available);
    }

    public function test_user_can_reorder_sidebar_items_and_persist_to_account(): void
    {
        $employee = User::factory()->create([
            'role' => 'cashier',
            'sidebar_order' => null,
        ]);

        $customOrder = ['expenses', 'shifts', 'pos', 'menu', 'busy_tables', 'settings'];

        $response = $this->actingAs($employee)->postJson(route('user.sidebar_order.update'), [
            'order' => $customOrder,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $employee->refresh();
        $this->assertEquals($customOrder, $employee->sidebar_order);

        $orderedItems = $employee->getOrderedSidebarItems();
        $this->assertSame($customOrder, array_keys($orderedItems));
    }

    public function test_user_can_reset_sidebar_order_to_default(): void
    {
        $employee = User::factory()->create([
            'role' => 'cashier',
            'sidebar_order' => ['expenses', 'settings', 'pos'],
        ]);

        $response = $this->actingAs($employee)->postJson(route('user.sidebar_order.reset'));

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $employee->refresh();
        $this->assertNull($employee->sidebar_order);
    }

    public function test_sidebar_renders_properly_for_authenticated_users(): void
    {
        $employee = User::factory()->create([
            'role' => 'cashier',
        ]);

        $response = $this->actingAs($employee)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('البيع (POS)');
        $response->assertSee('الترابيزات المشغولة');
        $response->assertSee('الشيفت (ورديتي)');
        $response->assertSee('المصروفات');
        $response->assertSee('المينيو');
        $response->assertSee('الإعدادات');

        // لا يجب أن يظهر لإدارة الترابيزات أو المخزن أو إدارة الموظفين
        $response->assertDontSee('إدارة الترابيزات');
        $response->assertDontSee('حركات المشرفين');
        $response->assertDontSee('إدارة الموظفين');
    }
}
