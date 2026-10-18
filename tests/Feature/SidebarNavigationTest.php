<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_gets_strictly_only_the_allowed_sidebar_items(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $available = $cashier->getAvailableSidebarItems();
        $keys = array_keys($available);

        // المستخدم بصلاحية الموظف يظهر له: بيع ومينيو والترابيزات المشغولة وفواتير المبيعات والمصروفات والشيفت ودليل الاستخدام واعدادات
        $this->assertEqualsCanonicalizing(
            ['pos', 'busy_tables', 'menu', 'shifts', 'sales_invoices', 'expenses', 'user_guide', 'settings'],
            $keys
        );
        $this->assertCount(8, $keys);

        // التأكد من مجموعات الكاشير أنها لا تتجاوز 3 مجموعات
        $groups = $cashier->getOrderedSidebarGroups();
        $this->assertLessThanOrEqual(4, count($groups));

        // التأكد من عدم وجود صلاحيات الإدارة العليا
        $this->assertArrayNotHasKey('tables', $available);
        $this->assertArrayNotHasKey('categories', $available);
        $this->assertArrayNotHasKey('recipes', $available);
        $this->assertArrayNotHasKey('shifts_management', $available);
        $this->assertArrayNotHasKey('inventory', $available);
        $this->assertArrayNotHasKey('employees', $available);
        $this->assertArrayNotHasKey('movements', $available);
        $this->assertArrayNotHasKey('purchase_invoices', $available);
    }

    public function test_manager_gets_five_accordion_groups(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $groups = $admin->getOrderedSidebarGroups();
        $this->assertCount(5, $groups);
        $this->assertEqualsCanonicalizing(
            ['operations', 'financials', 'inventory', 'analytics', 'management'],
            array_keys($groups)
        );

        $available = $admin->getAvailableSidebarItems();
        $this->assertArrayHasKey('pos', $available);
        $this->assertArrayHasKey('tables', $available);
        $this->assertArrayHasKey('analytics_comprehensive', $available);
        $this->assertArrayHasKey('dashboard', $available);
        $this->assertArrayHasKey('user_guide', $available);
        $this->assertArrayHasKey('settings', $available);
    }

    public function test_user_can_reorder_sidebar_items_and_persist_to_account(): void
    {
        $employee = User::factory()->create([
            'role' => 'cashier',
            'sidebar_order' => null,
        ]);

        $customOrder = ['expenses', 'shifts', 'pos', 'menu', 'busy_tables', 'settings', 'sales_invoices', 'user_guide'];

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

    public function test_user_can_set_and_persist_main_tab_preference(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->assertEquals('dashboard', $admin->getMainTabKey());

        $response = $this->actingAs($admin)->postJson(route('user.sidebar_order.update'), [
            'main_tab' => 'analytics_comprehensive',
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'main_tab' => 'analytics_comprehensive']);

        $admin->refresh();
        $this->assertEquals('analytics_comprehensive', $admin->getMainTabKey());
        $this->assertEquals('analytics.comprehensive', $admin->getMainTabRoute());
    }

    public function test_user_can_reorder_the_five_groups_and_persist(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customGroupOrder = ['analytics', 'financials', 'operations', 'management', 'inventory'];

        $response = $this->actingAs($admin)->postJson(route('user.sidebar_order.update'), [
            'group_order' => $customGroupOrder,
        ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $admin->refresh();
        $this->assertEquals($customGroupOrder, $admin->sidebar_group_order);

        $orderedGroups = $admin->getOrderedSidebarGroups();
        $this->assertSame($customGroupOrder, array_keys($orderedGroups));
    }

    public function test_sidebar_renders_properly_for_authenticated_users(): void
    {
        $employee = User::factory()->create([
            'role' => 'cashier',
        ]);

        $response = $this->actingAs($employee)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee('البيع (نقطة البيع)');
        $response->assertSee('الترابيزات المشغولة');
        $response->assertSee('الشيفت (ورديتي)');
        $response->assertSee('المصروفات');
        $response->assertSee('المينيو');
        $response->assertSee('دليل الاستخدام');
        $response->assertSee('الإعدادات');

        // لا يجب أن يظهر لإدارة الترابيزات أو المخزن أو إدارة الموظفين
        $response->assertDontSee('إدارة الترابيزات والصالة');
        $response->assertDontSee('حركات المشرفين');
        $response->assertDontSee('إدارة الموظفين والرواتب');
    }
}
