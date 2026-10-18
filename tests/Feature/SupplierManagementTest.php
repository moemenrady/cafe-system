<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'      => 'admin',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role'      => 'cashier',
            'is_active' => true,
        ]);
    }

    public function test_non_manager_cannot_access_suppliers()
    {
        $response = $this->actingAs($this->cashier)->get(route('suppliers.index'));
        $response->assertStatus(403);
    }

    public function test_manager_can_access_suppliers_index_and_view_kpis()
    {
        $supplier = Supplier::create([
            'name'         => 'شركة الأهرام للتوريدات',
            'company_name' => 'الأهرام',
            'phone'        => '01012345678',
        ]);

        PurchaseInvoice::create([
            'invoice_number'   => 'INV-TEST-001',
            'supplier_id'      => $supplier->id,
            'supplier_name'    => $supplier->name,
            'invoice_date'     => now()->toDateString(),
            'total_amount'     => 1000,
            'net_amount'       => 1000,
            'paid_amount'      => 600,
            'remaining_amount' => 400,
            'payment_method'   => 'cash',
            'payment_status'   => 'partial',
            'status'           => 'completed',
            'user_id'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('suppliers.index'));
        $response->assertOk();
        $response->assertSee('شركة الأهرام للتوريدات');
        $response->assertSee('1,000.00');
        $response->assertSee('400.00');
    }

    public function test_manager_can_create_new_supplier()
    {
        $response = $this->actingAs($this->admin)->post(route('suppliers.store'), [
            'name'            => 'مورد جديد كلياً',
            'company_name'    => 'شركة التوريد الذهبية',
            'phone'           => '01122334455',
            'email'           => 'supplier@test.com',
            'address'         => 'الإسكندرية',
            'opening_balance' => 250,
            'notes'           => 'مورد خامات حلويات',
        ]);

        $supplier = Supplier::where('name', 'مورد جديد كلياً')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals('شركة التوريد الذهبية', $supplier->company_name);
        $this->assertEquals(250, $supplier->opening_balance);

        $response->assertRedirect(route('suppliers.show', $supplier->id));
    }

    public function test_supplier_show_page_displays_ledger_and_direct_purchase_link()
    {
        $supplier = Supplier::create([
            'name'  => 'مورد عصائر طازجة',
            'phone' => '01099887766',
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number'   => 'INV-ZAIN-99',
            'supplier_id'      => $supplier->id,
            'supplier_name'    => $supplier->name,
            'invoice_date'     => now()->toDateString(),
            'total_amount'     => 2500,
            'net_amount'       => 2500,
            'paid_amount'      => 2000,
            'remaining_amount' => 500,
            'payment_method'   => 'cash',
            'payment_status'   => 'partial',
            'status'           => 'completed',
            'user_id'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('suppliers.show', $supplier->id));
        $response->assertOk();
        $response->assertSee('مورد عصائر طازجة');
        $response->assertSee('INV-ZAIN-99');
        $response->assertSee('2,500.00');
        $response->assertSee('500.00');
        // تأكيد وجود رابط إنشاء فاتورة جديدة مع تحديد المورد مسبقاً
        $response->assertSee(route('purchase-invoices.create', ['supplier_id' => $supplier->id]));
    }

    public function test_create_invoice_page_with_supplier_id_preselects_supplier()
    {
        $supplier = Supplier::create([
            'name'  => 'مورد هيثم المختار',
            'phone' => '01234567890',
        ]);

        $response = $this->actingAs($this->admin)->get(route('purchase-invoices.create', ['supplier_id' => $supplier->id]));
        $response->assertOk();
        $response->assertSee('value="' . $supplier->id . '" data-name="' . $supplier->name . '" selected', false);
    }

    public function test_manager_can_update_supplier()
    {
        $supplier = Supplier::create([
            'name'  => 'مورد قديم',
            'phone' => '01000000000',
        ]);

        $response = $this->actingAs($this->admin)->put(route('suppliers.update', $supplier->id), [
            'name'         => 'مورد تم تحديثه',
            'company_name' => 'شركة محدثة',
            'phone'        => '01011111111',
        ]);

        $response->assertRedirect(route('suppliers.show', $supplier->id));
        $this->assertEquals('مورد تم تحديثه', $supplier->fresh()->name);
        $this->assertEquals('01011111111', $supplier->fresh()->phone);
    }

    public function test_manager_can_delete_supplier_safely()
    {
        $supplier = Supplier::create([
            'name'  => 'مورد للحذف',
            'phone' => '01500000000',
        ]);

        $invoice = PurchaseInvoice::create([
            'invoice_number'   => 'PUR-DEL-01',
            'supplier_id'      => $supplier->id,
            'supplier_name'    => $supplier->name,
            'invoice_date'     => now()->toDateString(),
            'total_amount'     => 500,
            'net_amount'       => 500,
            'paid_amount'      => 500,
            'remaining_amount' => 0,
            'payment_method'   => 'cash',
            'payment_status'   => 'paid',
            'status'           => 'completed',
            'user_id'          => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('suppliers.destroy', $supplier->id));
        $response->assertRedirect(route('suppliers.index'));

        // تم حذف المورد مع الاحتفاظ بالفاتورة وسجلاتها
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseHas('purchase_invoices', [
            'id'            => $invoice->id,
            'supplier_name' => 'مورد للحذف',
            'supplier_id'   => null,
        ]);
    }
}
