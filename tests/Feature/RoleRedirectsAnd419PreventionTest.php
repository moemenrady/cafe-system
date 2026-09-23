<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Tests\TestCase;

class RoleRedirectsAnd419PreventionTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_login_and_dashboard_access_redirects_strictly_to_pos(): void
    {
        $cashier = User::factory()->create([
            'role'  => 'cashier',
            'email' => 'cashier@example.com',
        ]);

        // 1. Employee visiting login page while authenticated should be redirected to POS
        $this->actingAs($cashier);
        $response = $this->get(route('login'));
        $response->assertRedirect(route('pos.index'));

        // 2. Employee accessing root / or /dashboard should be redirected to POS
        $dashResponse = $this->get(route('dashboard'));
        $dashResponse->assertRedirect(route('pos.index'));
    }

    public function test_manager_login_and_dashboard_access_opens_today_manager_dashboard(): void
    {
        $admin = User::factory()->create([
            'role'  => 'admin',
            'email' => 'admin@example.com',
        ]);

        // Create invoice today
        Invoice::create([
            'invoice_number' => 'INV-TODAY-001',
            'total'          => 500.00,
            'payment_method' => 'cash',
            'created_by'     => $admin->id,
        ]);

        // Create low stock inventory item
        \App\Models\InventoryItem::create([
            'name'          => 'حبوب بن برازيلي',
            'quantity'      => 2,
            'reorder_level' => 5,
            'unit'          => 'كجم',
            'unit_price'    => 120.00,
        ]);

        $this->actingAs($admin);

        // 1. Manager visiting login page while authenticated should be redirected to dashboard
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertRedirect(route('dashboard'));

        // 2. Manager accessing dashboard gets 200 OK and sees today metrics
        $dashResponse = $this->get(route('dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('لوحة التحكم اليومية');
        $dashResponse->assertSee('إجمالي مبيعات اليوم');
        $dashResponse->assertSee('500.00');
        $dashResponse->assertSee('نواقص المخزون اليوم');
    }

    public function test_token_mismatch_419_exception_handles_web_and_json_gracefully(): void
    {
        \Illuminate\Support\Facades\Route::get('/test-csrf-mismatch', function () {
            throw new TokenMismatchException('CSRF Token Mismatch');
        });

        $cashier = User::factory()->create([
            'role' => 'cashier',
        ]);
        $this->actingAs($cashier);

        // 1. Web HTML Request -> Redirects gracefully to POS (not 419 error page)
        $webResponse = $this->get('/test-csrf-mismatch');
        $webResponse->assertRedirect(route('pos.index'));
        $webResponse->assertSessionHas('warning');

        // 2. JSON AJAX Request -> Returns 419 JSON response without crashing
        $jsonResponse = $this->getJson('/test-csrf-mismatch');
        $jsonResponse->assertStatus(419);
        $jsonResponse->assertJson(['status' => 419]);
    }
}
