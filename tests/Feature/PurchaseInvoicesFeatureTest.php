<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoicesFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'  => 'admin',
            'email' => 'admin_purchases@example.com',
        ]);

        $this->cashier = User::factory()->create([
            'role'  => 'cashier',
            'email' => 'cashier_purchases@example.com',
        ]);
    }

    public function test_guests_and_employees_cannot_access_purchase_invoices(): void
    {
        // 1. Guest redirected to login
        $this->get(route('purchase-invoices.index'))->assertRedirect(route('login'));
        $this->get(route('purchase-invoices.create'))->assertRedirect(route('login'));

        // 2. Cashier (non-manager) receives 403 Forbidden
        $this->actingAs($this->cashier);
        $this->get(route('purchase-invoices.index'))->assertStatus(403);
        $this->get(route('purchase-invoices.create'))->assertStatus(403);
        $this->post(route('purchase-invoices.store'), [])->assertStatus(403);
    }

    public function test_manager_can_view_index_and_create_pages(): void
    {
        $this->actingAs($this->admin);

        $indexRes = $this->get(route('purchase-invoices.index'));
        $indexRes->assertStatus(200);
        $indexRes->assertSee('فواتير الشراء والتوريد');

        $createRes = $this->get(route('purchase-invoices.create'));
        $createRes->assertStatus(200);
        $createRes->assertSee('فاتورة شراء ومشتريات جديدة');
    }

    public function test_ajax_search_inventory_items(): void
    {
        $this->actingAs($this->admin);

        InventoryItem::create([
            'name'          => 'حليب جهينة كامل الدسم',
            'quantity'      => 10,
            'unit'          => 'لتر',
            'unit_price'    => 40.00,
            'reorder_level' => 5,
        ]);

        $response = $this->getJson(route('inventory.ajaxSearch', ['q' => 'حليب']));
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'حليب جهينة كامل الدسم',
        ]);
    }

    public function test_create_purchase_invoice_with_existing_items_increments_stock(): void
    {
        $this->actingAs($this->admin);

        $coffee = InventoryItem::create([
            'name'          => 'بن اسبريسو ايطالي',
            'quantity'      => 5.0,
            'unit'          => 'كجم',
            'unit_price'    => 200.00,
            'reorder_level' => 2,
        ]);

        $sugar = InventoryItem::create([
            'name'          => 'سكر أبيض ناعم',
            'quantity'      => 20.0,
            'unit'          => 'كجم',
            'unit_price'    => 30.00,
            'reorder_level' => 10,
        ]);

        $postData = [
            'invoice_number' => 'PINV-TEST-001',
            'supplier_name'  => 'شركة الأهرام للتوريدات',
            'invoice_date'   => Carbon::today()->format('Y-m-d'),
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'paid_amount'    => 1300.00,
            'discount'       => 50.00,
            'tax'            => 0.00,
            'notes'          => 'توريد خامات أول الأسبوع',
            'items'          => [
                [
                    'inventory_item_id' => $coffee->id,
                    'name'              => $coffee->name,
                    'unit'              => 'كجم',
                    'category'          => 'بن وقهوة',
                    'quantity'          => 5.0,
                    'unit_price'        => 210.00, // 5 * 210 = 1050
                    'notes'             => 'شيكارة ممتازة',
                ],
                [
                    'inventory_item_id' => $sugar->id,
                    'name'              => $sugar->name,
                    'unit'              => 'كجم',
                    'category'          => 'عام',
                    'quantity'          => 10.0,
                    'unit_price'        => 30.00, // 10 * 30 = 300
                    'notes'             => '',
                ],
            ],
        ];

        // Total = 1050 + 300 = 1350. Discount = 50. Net = 1300.
        $response = $this->post(route('purchase-invoices.store'), $postData);

        $invoice = PurchaseInvoice::where('invoice_number', 'PINV-TEST-001')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('purchase-invoices.show', $invoice->id));

        $this->assertEquals(1350.00, (float) $invoice->total_amount);
        $this->assertEquals(50.00, (float) $invoice->discount);
        $this->assertEquals(1300.00, (float) $invoice->net_amount);
        $this->assertEquals(1300.00, (float) $invoice->paid_amount);
        $this->assertEquals(0.00, (float) $invoice->remaining_amount);
        $this->assertEquals('paid', $invoice->payment_status);

        // Check Inventory Items stock updated
        $this->assertEquals(10.0, (float) $coffee->fresh()->quantity); // 5 + 5
        $this->assertEquals(210.00, (float) $coffee->fresh()->unit_price);
        $this->assertEquals(30.0, (float) $sugar->fresh()->quantity); // 20 + 10

        // Check Inventory Movements logged
        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $coffee->id,
            'type'              => 'restock',
            'quantity'          => 5.0,
            'balance_after'     => 10.0,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'inventory_item_id' => $sugar->id,
            'type'              => 'restock',
            'quantity'          => 10.0,
            'balance_after'     => 30.0,
        ]);
    }

    public function test_create_purchase_invoice_with_new_item_creates_it_in_inventory(): void
    {
        $this->actingAs($this->admin);

        $postData = [
            'supplier_name'  => 'مؤسسة النور للمشروبات',
            'invoice_date'   => Carbon::today()->format('Y-m-d'),
            'payment_method' => 'instapay',
            'payment_status' => 'partial',
            'paid_amount'    => 200.00,
            'discount'       => 0,
            'tax'            => 0,
            'items'          => [
                [
                    'name'          => 'صوص بستاشيو إيطالي جديد',
                    'unit'          => 'زجاجة',
                    'category'      => 'صوصات وحلويات',
                    'quantity'      => 4.0,
                    'unit_price'    => 125.00, // 4 * 125 = 500
                ],
            ],
        ];

        // Total = 500, Paid = 200, Remaining = 300
        $response = $this->post(route('purchase-invoices.store'), $postData);
        $response->assertSessionHasNoErrors();

        // Check new item was created
        $newItem = InventoryItem::where('name', 'صوص بستاشيو إيطالي جديد')->first();
        $this->assertNotNull($newItem);
        $this->assertEquals(4.0, (float) $newItem->quantity);
        $this->assertEquals('زجاجة', $newItem->unit);
        $this->assertEquals(125.00, (float) $newItem->unit_price);
        $this->assertEquals('صوصات وحلويات', $newItem->category);

        $invoice = PurchaseInvoice::latest('id')->first();
        $this->assertEquals(500.00, (float) $invoice->net_amount);
        $this->assertEquals(200.00, (float) $invoice->paid_amount);
        $this->assertEquals(300.00, (float) $invoice->remaining_amount);
        $this->assertEquals('partial', $invoice->payment_status);
        $this->assertEquals('instapay', $invoice->payment_method);
    }

    public function test_purchase_invoices_index_displays_date_grouping_and_filters(): void
    {
        $this->actingAs($this->admin);

        // Invoice today
        $invToday = PurchaseInvoice::create([
            'invoice_number'   => 'PINV-DATE-001',
            'supplier_name'    => 'مورد اليوم للألبان',
            'invoice_date'     => Carbon::today()->format('Y-m-d'),
            'total_amount'     => 400.00,
            'net_amount'       => 400.00,
            'paid_amount'      => 400.00,
            'remaining_amount' => 0.00,
            'payment_method'   => 'cash',
            'payment_status'   => 'paid',
            'user_id'          => $this->admin->id,
        ]);

        // Invoice yesterday
        $invYesterday = PurchaseInvoice::create([
            'invoice_number'   => 'PINV-DATE-002',
            'supplier_name'    => 'مورد الأمس للحلويات',
            'invoice_date'     => Carbon::yesterday()->format('Y-m-d'),
            'total_amount'     => 600.00,
            'net_amount'       => 600.00,
            'paid_amount'      => 0.00,
            'remaining_amount' => 600.00,
            'payment_method'   => 'credit',
            'payment_status'   => 'unpaid',
            'user_id'          => $this->admin->id,
        ]);

        $response = $this->get(route('purchase-invoices.index'));
        $response->assertStatus(200);
        $response->assertSee('PINV-DATE-001');
        $response->assertSee('PINV-DATE-002');
        $response->assertSee('مورد اليوم للألبان');
        $response->assertSee('مورد الأمس للحلويات');

        // Test search filter
        $searchRes = $this->get(route('purchase-invoices.index', ['search' => 'PINV-DATE-001']));
        $searchRes->assertSee('PINV-DATE-001');
        $searchRes->assertDontSee('PINV-DATE-002');
    }

    public function test_show_and_edit_purchase_invoice(): void
    {
        $this->actingAs($this->admin);

        $item = InventoryItem::create([
            'name'       => 'شاي أسود ناعم',
            'quantity'   => 15.0,
            'unit'       => 'كجم',
            'unit_price' => 50.00,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number'   => 'PINV-SHOW-001',
            'supplier_name'    => 'شركة الشاي الوطنية',
            'invoice_date'     => Carbon::today()->format('Y-m-d'),
            'total_amount'     => 250.00,
            'net_amount'       => 250.00,
            'paid_amount'      => 250.00,
            'remaining_amount' => 0.00,
            'payment_method'   => 'cash',
            'payment_status'   => 'paid',
            'user_id'          => $this->admin->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'inventory_item_id'   => $item->id,
            'item_name'           => $item->name,
            'unit'                => 'كجم',
            'quantity'            => 5.0,
            'unit_price'          => 50.00,
            'subtotal'            => 250.00,
        ]);

        // 1. Show page
        $showRes = $this->get(route('purchase-invoices.show', $invoice->id));
        $showRes->assertStatus(200);
        $showRes->assertSee('PINV-SHOW-001');
        $showRes->assertSee('شركة الشاي الوطنية');
        $showRes->assertSee('شاي أسود ناعم');

        // 2. Edit page
        $editRes = $this->get(route('purchase-invoices.edit', $invoice->id));
        $editRes->assertStatus(200);
        $editRes->assertSee('PINV-SHOW-001');
    }

    public function test_update_purchase_invoice_adjusts_inventory_stock(): void
    {
        $this->actingAs($this->admin);

        $tea = InventoryItem::create([
            'name'       => 'كرتونة شاي أحمر',
            'quantity'   => 10.0, // 5 base + 5 from invoice
            'unit'       => 'كرتونة',
            'unit_price' => 100.00,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number'   => 'PINV-UPD-001',
            'supplier_name'    => 'المورد القديم',
            'invoice_date'     => Carbon::today()->format('Y-m-d'),
            'total_amount'     => 500.00,
            'net_amount'       => 500.00,
            'paid_amount'      => 500.00,
            'remaining_amount' => 0.00,
            'payment_method'   => 'cash',
            'payment_status'   => 'paid',
            'user_id'          => $this->admin->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'inventory_item_id'   => $tea->id,
            'item_name'           => $tea->name,
            'unit'                => 'كرتونة',
            'quantity'            => 5.0,
            'unit_price'          => 100.00,
            'subtotal'            => 500.00,
        ]);

        // Update to 8 cartons at 110.00
        $updateData = [
            'supplier_name'  => 'المورد المحدث',
            'invoice_date'   => Carbon::today()->format('Y-m-d'),
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'items'          => [
                [
                    'inventory_item_id' => $tea->id,
                    'name'              => $tea->name,
                    'unit'              => 'كرتونة',
                    'quantity'          => 8.0,
                    'unit_price'        => 110.00,
                ],
            ],
        ];

        $res = $this->put(route('purchase-invoices.update', $invoice->id), $updateData);
        $res->assertRedirect(route('purchase-invoices.show', $invoice->id));

        // tea stock should be: 10 - 5 (old) + 8 (new) = 13
        $this->assertEquals(13.0, (float) $tea->fresh()->quantity);
        $this->assertEquals('المورد المحدث', $invoice->fresh()->supplier_name);
        $this->assertEquals(880.00, (float) $invoice->fresh()->net_amount);
    }

    public function test_delete_purchase_invoice_reverts_inventory(): void
    {
        $this->actingAs($this->admin);

        $cups = InventoryItem::create([
            'name'       => 'أكواب ورقية 9 أونص',
            'quantity'   => 100.0,
            'unit'       => 'قطعة',
            'unit_price' => 1.50,
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number'   => 'PINV-DEL-001',
            'supplier_name'    => 'مؤسسة التعبئة',
            'invoice_date'     => Carbon::today()->format('Y-m-d'),
            'total_amount'     => 150.00,
            'net_amount'       => 150.00,
            'paid_amount'      => 150.00,
            'remaining_amount' => 0.00,
            'payment_method'   => 'cash',
            'payment_status'   => 'paid',
            'user_id'          => $this->admin->id,
        ]);

        PurchaseInvoiceItem::create([
            'purchase_invoice_id' => $invoice->id,
            'inventory_item_id'   => $cups->id,
            'item_name'           => $cups->name,
            'unit'                => 'قطعة',
            'quantity'            => 100.0,
            'unit_price'          => 1.50,
            'subtotal'            => 150.00,
        ]);

        $res = $this->delete(route('purchase-invoices.destroy', $invoice->id));
        $res->assertRedirect(route('purchase-invoices.index'));

        // Stock reverted: 100 - 100 = 0
        $this->assertEquals(0.0, (float) $cups->fresh()->quantity);
        $this->assertDatabaseMissing('purchase_invoices', ['id' => $invoice->id]);
    }

    public function test_dashboard_reflects_today_purchase_invoices(): void
    {
        $this->actingAs($this->admin);

        // Create today purchase invoice
        PurchaseInvoice::create([
            'invoice_number'   => 'PINV-DASH-001',
            'supplier_name'    => 'توريدات اليوم كافيه',
            'invoice_date'     => Carbon::today()->format('Y-m-d'),
            'total_amount'     => 750.00,
            'net_amount'       => 750.00,
            'paid_amount'      => 500.00,
            'remaining_amount' => 250.00,
            'payment_method'   => 'cash',
            'payment_status'   => 'partial',
            'user_id'          => $this->admin->id,
        ]);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        // Check KPI card
        $response->assertSee('مشتريات اليوم');
        $response->assertSee('750.00');
        $response->assertSee('PINV-DASH-001');
        $response->assertSee('توريدات اليوم كافيه');
    }
}
