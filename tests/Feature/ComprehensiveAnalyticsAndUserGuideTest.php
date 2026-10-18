<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Shift;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveAnalyticsAndUserGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_comprehensive_analytics_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('analytics.comprehensive'));

        $response->assertOk();
        $response->assertViewIs('analytics.comprehensive');
        $response->assertSee('التحليل الشامل والأداء المالي والتشغيلي');
        $response->assertSee('إجمالي المبيعات والإيرادات');
        $response->assertSee('صافي الأرباح التقديري');
        $response->assertSee('مبيعات إنستاباي');
        $response->assertSee('ساعات الذروة');
    }

    public function test_supervisor_can_access_comprehensive_analytics(): void
    {
        $supervisor = User::factory()->create([
            'role' => 'supervisor',
        ]);

        $response = $this->actingAs($supervisor)->get(route('analytics.comprehensive'));
        $response->assertOk();
    }

    public function test_analytics_with_tables_and_orders_renders_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $table1 = Table::create(['name' => 'T1', 'is_active' => true]);
        $table2 = Table::create(['name' => 'T2', 'is_active' => true]);

        Order::create([
            'order_number' => 'ORD-101',
            'table_id' => $table1->id,
            'type' => 'dine-in',
            'status' => 'open',
            'payment_status' => 'pending',
            'subtotal' => 50,
            'total' => 50,
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('analytics.comprehensive'));
        $response->assertOk();
        $response->assertViewHas('busyTablesNow', 1);
        $response->assertViewHas('totalTables', 2);
    }

    public function test_cashier_cannot_access_comprehensive_analytics(): void
    {
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $response = $this->actingAs($cashier)->get(route('analytics.comprehensive'));
        $response->assertForbidden();
    }

    public function test_analytics_computes_sales_and_payment_breakdowns_correctly(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $cat = Category::create(['name' => 'قهوة ساخنة']);
        $menuItem = Menu::create([
            'name' => 'كابتشينو خاص',
            'category_id' => $cat->id,
            'price' => 50.00,
            'is_available' => true,
        ]);

        // فاتورة كاش 100 ج
        $inv1 = Invoice::create([
            'invoice_number' => 'INV-001',
            'created_by'     => $admin->id,
            'subtotal'       => 100.00,
            'total'          => 100.00,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status'         => 'completed',
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv1->id,
            'menu_id'    => $menuItem->id,
            'quantity'   => 2,
            'item_price' => 50.00,
            'total'      => 100.00,
        ]);

        // فاتورة إنستاباي 150 ج
        $inv2 = Invoice::create([
            'invoice_number' => 'INV-002',
            'created_by'     => $admin->id,
            'subtotal'       => 150.00,
            'total'          => 150.00,
            'payment_method' => 'instapay',
            'payment_status' => 'paid',
            'status'         => 'completed',
        ]);
        InvoiceItem::create([
            'invoice_id' => $inv2->id,
            'menu_id'    => $menuItem->id,
            'quantity'   => 3,
            'item_price' => 50.00,
            'total'      => 150.00,
        ]);

        // مصروفات 40 ج
        $expCat = ExpenseCategory::create(['name' => 'نثريات تشغيل']);
        Expense::create([
            'expense_category_id' => $expCat->id,
            'user_id'             => $admin->id,
            'title'               => 'أدوات نظافة',
            'amount'              => 40.00,
        ]);

        $response = $this->actingAs($admin)->get(route('analytics.comprehensive', ['period' => 'today']));

        $response->assertOk();
        $response->assertViewHas('totalRevenue', 250.00);
        $response->assertViewHas('cashSales', 100.00);
        $response->assertViewHas('instapaySales', 150.00);
        $response->assertViewHas('invoicesCount', 2);
        $response->assertViewHas('totalExpenses', 40.00);
        $response->assertSee('كابتشينو خاص');
    }

    public function test_analytics_filter_periods_execute_without_error(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $periods = ['today', 'yesterday', 'this_week', 'this_month', 'last_30_days', 'this_year', 'all'];

        foreach ($periods as $period) {
            $response = $this->actingAs($admin)->get(route('analytics.comprehensive', ['period' => $period]));
            $response->assertOk();
        }

        // اختبار فلتر مخصص custom range
        $customResponse = $this->actingAs($admin)->get(route('analytics.comprehensive', [
            'period' => 'custom',
            'from'   => Carbon::now()->subDays(5)->toDateString(),
            'to'     => Carbon::now()->toDateString(),
        ]));
        $customResponse->assertOk();
    }

    public function test_all_authenticated_users_can_access_user_guide(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);
        $admin = User::factory()->create(['role' => 'admin']);
        $barista = User::factory()->create(['role' => 'barista']);

        $resCashier = $this->actingAs($cashier)->get(route('user_guide.index'));
        $resCashier->assertOk();
        $resCashier->assertSee('دليل الاستخدام والتشغيل الرسمي');
        $resCashier->assertSee('إزاي أقدر أعمل طلب سريع سفري');

        $resAdmin = $this->actingAs($admin)->get(route('user_guide.index'));
        $resAdmin->assertOk();

        $resBarista = $this->actingAs($barista)->get(route('user_guide.index'));
        $resBarista->assertOk();
    }

    public function test_user_main_tab_redirects_properly(): void
    {
        $admin = User::factory()->create([
            'role'     => 'admin',
            'main_tab' => 'analytics_comprehensive',
        ]);

        $response = $this->actingAs($admin)->get('/');
        $response->assertRedirect(route('analytics.comprehensive'));

        // للكاشير الافتراضي يكون pos
        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $cashierResponse = $this->actingAs($cashier)->get('/');
        $cashierResponse->assertRedirect(route('pos.index'));
    }
}
