<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Shift;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected Shift $shift;
    protected Table $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'  => 'admin',
            'email' => 'admin_crm@example.com',
        ]);

        $this->cashier = User::factory()->create([
            'role'  => 'cashier',
            'email' => 'cashier_crm@example.com',
        ]);

        $this->shift = Shift::create([
            'user_id'    => $this->cashier->id,
            'status'     => 'open',
            'start_time' => now(),
            'start_cash' => 1000.00,
        ]);

        $this->table = Table::create([
            'table_number' => 20,
            'name'         => 'طاولة 20',
            'capacity'     => 4,
            'is_active'    => true,
        ]);
    }

    public function test_manager_can_access_customers_index_and_view_kpis(): void
    {
        $this->actingAs($this->admin);

        $c1 = Customer::create([
            'name'    => 'محمد إبراهيم',
            'phone'   => '01011112222',
            'address' => 'التجمع الخامس',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'total'          => 750.00,
            'client_id'      => $c1->id,
            'payment_method' => 'cash',
            'created_by'     => $this->admin->id,
        ]);

        $response = $this->get(route('customers.index'));
        $response->assertStatus(200);
        $response->assertSee('قاعدة بيانات وسجل عملاء الكافيه');
        $response->assertSee('محمد إبراهيم');
        $response->assertSee('01011112222');
        $response->assertSee('750.00');
    }

    public function test_search_and_filters_on_customers_index(): void
    {
        $this->actingAs($this->admin);

        $vip = Customer::create([
            'name'    => 'محمود السعيد VIP',
            'phone'   => '01099998888',
            'address' => 'المعادي',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-VIP-001',
            'total'          => 3500.00,
            'client_id'      => $vip->id,
            'payment_method' => 'card',
            'created_by'     => $this->admin->id,
        ]);

        $regular = Customer::create([
            'name'    => 'علي كمال عادي',
            'phone'   => '01122334455',
            'address' => 'مدينة نصر',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-REG-001',
            'total'          => 150.00,
            'client_id'      => $regular->id,
            'payment_method' => 'cash',
            'created_by'     => $this->admin->id,
        ]);

        // 1. Search by phone
        $resPhone = $this->get(route('customers.index', ['search' => '01099998888']));
        $resPhone->assertSee('محمود السعيد VIP');
        $resPhone->assertDontSee('علي كمال عادي');

        // 2. Filter by VIP type
        $resVip = $this->get(route('customers.index', ['vip_type' => 'vip']));
        $resVip->assertSee('محمود السعيد VIP');
        $resVip->assertDontSee('علي كمال عادي');

        // 3. Filter by min spent
        $resSpent = $this->get(route('customers.index', ['min_spent' => 1000]));
        $resSpent->assertSee('محمود السعيد VIP');
        $resSpent->assertDontSee('علي كمال عادي');
    }

    public function test_ajax_search_customers(): void
    {
        $this->actingAs($this->cashier);

        Customer::create([
            'name'  => 'سارة عبد الله',
            'phone' => '01234567890',
        ]);

        $res = $this->getJson(route('customers.ajaxSearch', ['phone' => '0123456']));
        $res->assertStatus(200);
        $res->assertJsonFragment([
            'name'  => 'سارة عبد الله',
            'phone' => '01234567890',
        ]);
    }

    public function test_crud_operations_for_customers(): void
    {
        $this->actingAs($this->admin);

        // 1. Store
        $postData = [
            'name'    => 'كريم فتحي',
            'phone'   => '01555554444',
            'address' => 'الشيخ زايد',
            'notes'   => 'يفضل القهوة الاسبريسو دبل',
        ];
        $resStore = $this->post(route('customers.store'), $postData);
        $customer = Customer::where('phone', '01555554444')->first();
        $this->assertNotNull($customer);
        $resStore->assertRedirect(route('customers.show', $customer->id));

        // 2. Update
        $updateData = [
            'name'    => 'كريم فتحي المعدل',
            'phone'   => '01555554444',
            'address' => 'أكتوبر',
            'notes'   => 'عميل مميز',
        ];
        $resUpdate = $this->put(route('customers.update', $customer->id), $updateData);
        $this->assertEquals('كريم فتحي المعدل', $customer->fresh()->name);
        $this->assertEquals('أكتوبر', $customer->fresh()->address);

        // 3. Destroy
        $resDelete = $this->delete(route('customers.destroy', $customer->id));
        $resDelete->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_export_all_customers_csv(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'name'  => 'عميل تصدير إكسيل',
            'phone' => '01000000001',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-EXP-001',
            'total'          => 450.00,
            'client_id'      => $customer->id,
            'created_by'     => $this->admin->id,
        ]);

        $response = $this->get(route('customers.export'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('عميل تصدير إكسيل', $response->streamedContent());
        $this->assertStringContainsString('450.00', $response->streamedContent());
    }

    public function test_show_customer_profile_and_visits_history(): void
    {
        $this->actingAs($this->admin);

        $category = Category::create(['name' => 'مشروبات ساخنة']);
        $menuItem = Menu::create([
            'name'        => 'كابتشينو كراميل',
            'price'       => 65.00,
            'category_id' => $category->id,
        ]);

        $customer = Customer::create([
            'name'  => 'مروان الشريف',
            'phone' => '01088776655',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-MAR-001',
            'total'          => 130.00,
            'client_id'      => $customer->id,
            'payment_method' => 'InstaPay',
            'created_by'     => $this->admin->id,
        ]);

        $invoice->items()->create([
            'menu_id'    => $menuItem->id,
            'quantity'   => 2,
            'item_price' => 65.00,
            'total'      => 130.00,
        ]);

        $response = $this->get(route('customers.show', $customer->id));
        $response->assertStatus(200);
        $response->assertSee('مروان الشريف');
        $response->assertSee('01088776655');
        $response->assertSee('INV-MAR-001');
        $response->assertSee('130.00');
        $response->assertSee('كابتشينو كراميل');
    }

    public function test_export_single_customer_invoices_csv(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'name'  => 'ياسر جلال',
            'phone' => '01012345678',
        ]);

        Invoice::create([
            'invoice_number' => 'INV-SINGLE-001',
            'total'          => 220.00,
            'client_id'      => $customer->id,
            'payment_method' => 'cash',
            'created_by'     => $this->admin->id,
        ]);

        $response = $this->get(route('customers.exportSingle', $customer->id));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('INV-SINGLE-001', $response->streamedContent());
        $this->assertStringContainsString('220.00', $response->streamedContent());
    }

    public function test_table_checkout_with_existing_customer_phone_links_order_and_invoice(): void
    {
        $this->actingAs($this->cashier);

        // Existing customer
        $existingCustomer = Customer::create([
            'name'  => 'طارق العوضي',
            'phone' => '01033334444',
        ]);

        // Active Dine-in Order on table
        $order = Order::create([
            'order_number'   => 'ORD-TABLE-101',
            'table_id'       => $this->table->id,
            'type'           => 'dine_in',
            'status'         => 'open',
            'payment_status' => 'pending',
            'total'          => 180.00,
            'shift_id'       => $this->shift->id,
            'created_by'     => $this->cashier->id,
        ]);

        $checkoutPayload = [
            'payment_method' => 'cash',
            'force'          => true,
            'customer_phone' => '01033334444',
            'customer_name'  => 'طارق العوضي',
        ];

        $response = $this->postJson("/orders/{$order->id}/checkout", $checkoutPayload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Assert Order linked to customer
        $this->assertEquals($existingCustomer->id, $order->fresh()->customer_id);
        $this->assertEquals('paid', $order->fresh()->payment_status);

        // Assert Invoice created and linked to customer
        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($existingCustomer->id, $invoice->client_id);
    }

    public function test_table_checkout_with_new_customer_phone_creates_customer_and_links(): void
    {
        $this->actingAs($this->cashier);

        // Active Dine-in Order
        $order = Order::create([
            'order_number'   => 'ORD-TABLE-102',
            'table_id'       => $this->table->id,
            'type'           => 'dine_in',
            'status'         => 'open',
            'payment_status' => 'pending',
            'total'          => 250.00,
            'shift_id'       => $this->shift->id,
            'created_by'     => $this->cashier->id,
        ]);

        $checkoutPayload = [
            'payment_method' => 'InstaPay',
            'force'          => true,
            'customer_phone' => '01077778888',
            'customer_name'  => 'خالد الجوهري جديد',
        ];

        $response = $this->postJson("/orders/{$order->id}/checkout", $checkoutPayload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Customer created automatically
        $newCustomer = Customer::where('phone', '01077778888')->first();
        $this->assertNotNull($newCustomer);
        $this->assertEquals('خالد الجوهري جديد', $newCustomer->name);

        // Order & Invoice linked
        $this->assertEquals($newCustomer->id, $order->fresh()->customer_id);
        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertEquals($newCustomer->id, $invoice->client_id);
        $this->assertEquals('InstaPay', $invoice->payment_method);
    }

    public function test_table_checkout_without_customer_data_is_completely_optional(): void
    {
        $this->actingAs($this->cashier);

        $order = Order::create([
            'order_number'   => 'ORD-TABLE-103',
            'table_id'       => $this->table->id,
            'type'           => 'dine_in',
            'status'         => 'open',
            'payment_status' => 'pending',
            'total'          => 90.00,
            'shift_id'       => $this->shift->id,
            'created_by'     => $this->cashier->id,
        ]);

        // Payload without any customer fields
        $checkoutPayload = [
            'payment_method' => 'cash',
            'force'          => true,
        ];

        $response = $this->postJson("/orders/{$order->id}/checkout", $checkoutPayload);
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Checkout succeeded, order is paid, no customer forced
        $this->assertEquals('paid', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->customer_id);
    }
}
