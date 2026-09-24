<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\EmployeePayroll;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $cashier;
    protected User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'      => 'admin',
            'email'     => 'admin_emp@example.com',
            'is_active' => true,
        ]);

        $this->supervisor = User::factory()->create([
            'role'      => 'supervisor',
            'email'     => 'sup_emp@example.com',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role'      => 'cashier',
            'email'     => 'cashier_emp@example.com',
            'is_active' => true,
        ]);
    }

    public function test_non_manager_cannot_access_employee_management(): void
    {
        $this->actingAs($this->cashier);

        $res = $this->get(route('employees.index'));
        $res->assertStatus(403);
    }

    public function test_manager_can_access_employees_index_and_view_kpis(): void
    {
        $this->actingAs($this->admin);

        $emp = Employee::create([
            'name'        => 'محمود عبد العزيز',
            'phone'       => '01011223344',
            'job_title'   => 'باريستا رئيسي',
            'base_salary' => 4500.00,
            'hire_date'   => now()->subMonth(),
            'is_active'   => true,
        ]);

        $res = $this->get(route('employees.index'));
        $res->assertStatus(200);
        $res->assertSee('إدارة فريق العمل والرواتب الشهرية');
        $res->assertSee('محمود عبد العزيز');
        $res->assertSee('باريستا رئيسي');
        $res->assertSee('4,500.00');
    }

    public function test_manager_can_create_employee_and_payrolls_are_auto_generated(): void
    {
        $this->actingAs($this->admin);

        $hireDate = now()->subMonths(2)->format('Y-m-d');
        $postData = [
            'name'        => 'حسام حسن',
            'phone'       => '01122334455',
            'national_id' => '29501011234567',
            'address'     => 'القاهرة - المعادي',
            'job_title'   => 'كاشير',
            'base_salary' => 3800.00,
            'hire_date'   => $hireDate,
            'notes'       => 'موظف متميز في خدمة العملاء',
        ];

        $res = $this->post(route('employees.store'), $postData);

        $employee = Employee::where('phone', '01122334455')->first();
        $this->assertNotNull($employee);
        $this->assertEquals('حسام حسن', $employee->name);
        $this->assertEquals(3800.00, (float) $employee->base_salary);

        $res->assertRedirect(route('employees.show', $employee->id));

        // التأكد من توليد سجلات الشهور المستحقة تلقائياً
        $currentMonth = now()->format('Y-m');
        $currentPayroll = $employee->payrolls()->where('salary_month', $currentMonth)->first();
        $this->assertNotNull($currentPayroll);
        $this->assertEquals(3800.00, (float) $currentPayroll->net_salary);
        $this->assertEquals('pending', $currentPayroll->status);
    }

    public function test_manager_can_update_and_delete_employee(): void
    {
        $this->actingAs($this->admin);

        $emp = Employee::create([
            'name'        => 'طارق صبحي',
            'phone'       => '01222223333',
            'job_title'   => 'ويتر',
            'base_salary' => 3000.00,
            'is_active'   => true,
        ]);

        // 1. Update
        $updateData = [
            'name'        => 'طارق صبحي المعدل',
            'phone'       => '01222223333',
            'job_title'   => 'كابتن صالة',
            'base_salary' => 3500.00,
            'is_active'   => 1,
        ];
        $resUpdate = $this->put(route('employees.update', $emp->id), $updateData);
        $resUpdate->assertRedirect(route('employees.show', $emp->id));
        $this->assertEquals('طارق صبحي المعدل', $emp->fresh()->name);
        $this->assertEquals('كابتن صالة', $emp->fresh()->job_title);
        $this->assertEquals(3500.00, (float) $emp->fresh()->base_salary);

        // 2. Delete
        $resDelete = $this->delete(route('employees.destroy', $emp->id));
        $resDelete->assertRedirect(route('employees.index'));
        $this->assertSoftDeleted('employees', ['id' => $emp->id]);
    }

    public function test_employee_show_page_displays_payroll_ledger_and_profile(): void
    {
        $this->actingAs($this->admin);

        $emp = Employee::create([
            'name'        => 'عماد حمدي',
            'phone'       => '01511112222',
            'job_title'   => 'شيف حلويات',
            'base_salary' => 5000.00,
            'hire_date'   => now()->subMonths(1),
            'is_active'   => true,
        ]);

        $res = $this->get(route('employees.show', $emp->id));
        $res->assertStatus(200);
        $res->assertSee('عماد حمدي');
        $res->assertSee('شيف حلويات');
        $res->assertSee('5,000.00');
        $res->assertSee('سجل الرواتب الشهرية والقبض');
    }

    public function test_adding_bonus_increases_net_salary_for_current_or_past_month(): void
    {
        $this->actingAs($this->admin);

        $currentMonth = now()->format('Y-m');
        $emp = Employee::create([
            'name'        => 'وائل منصور',
            'phone'       => '01055556666',
            'job_title'   => 'باريستا',
            'base_salary' => 4000.00,
            'hire_date'   => now(),
            'is_active'   => true,
        ]);

        // إضافة بونص 300 ج.م
        $postData = [
            'type'         => 'bonus',
            'amount'       => 300.00,
            'salary_month' => $currentMonth,
            'reason'       => 'مكافأة تميز وإدارة وردية ناجحة',
            'date'         => now()->toDateString(),
        ];

        $res = $this->post(route('employees.addAdjustment', $emp->id), $postData);
        $res->assertRedirect(route('employees.show', $emp->id));

        $payroll = $emp->payrolls()->where('salary_month', $currentMonth)->first();
        $this->assertNotNull($payroll);
        $this->assertEquals(300.00, (float) $payroll->total_bonus);
        $this->assertEquals(4300.00, (float) $payroll->net_salary); // 4000 + 300
    }

    public function test_adding_deduction_decreases_net_salary_for_month(): void
    {
        $this->actingAs($this->admin);

        $currentMonth = now()->format('Y-m');
        $emp = Employee::create([
            'name'        => 'سعيد عثمان',
            'phone'       => '01199998888',
            'job_title'   => 'ويتر',
            'base_salary' => 3000.00,
            'hire_date'   => now(),
            'is_active'   => true,
        ]);

        // إضافة خصم 200 ج.م
        $postData = [
            'type'         => 'deduction',
            'amount'       => 200.00,
            'salary_month' => $currentMonth,
            'reason'       => 'تأخير متكرر',
            'date'         => now()->toDateString(),
        ];

        $res = $this->post(route('employees.addAdjustment', $emp->id), $postData);
        $res->assertRedirect(route('employees.show', $emp->id));

        $payroll = $emp->payrolls()->where('salary_month', $currentMonth)->first();
        $this->assertNotNull($payroll);
        $this->assertEquals(200.00, (float) $payroll->total_deductions);
        $this->assertEquals(2800.00, (float) $payroll->net_salary); // 3000 - 200
    }

    public function test_deleting_adjustment_recalculates_net_salary(): void
    {
        $this->actingAs($this->admin);

        $currentMonth = now()->format('Y-m');
        $emp = Employee::create([
            'name'        => 'عادل إمام',
            'phone'       => '01200001111',
            'job_title'   => 'مشرف',
            'base_salary' => 6000.00,
            'hire_date'   => now(),
            'is_active'   => true,
        ]);

        $adj = EmployeeAdjustment::create([
            'employee_id'  => $emp->id,
            'salary_month' => $currentMonth,
            'type'         => 'deduction',
            'amount'       => 500.00,
            'reason'       => 'سلفة نقدية',
            'date'         => now()->toDateString(),
        ]);

        // حذف الحركة
        $res = $this->delete(route('employees.deleteAdjustment', $adj->id));
        $res->assertRedirect(route('employees.show', $emp->id));

        $payroll = $emp->payrolls()->where('salary_month', $currentMonth)->first();
        $this->assertEquals(0.00, (float) $payroll->total_deductions);
        $this->assertEquals(6000.00, (float) $payroll->net_salary);
    }

    public function test_paying_single_month_salary_updates_status_and_paid_amount(): void
    {
        $this->actingAs($this->admin);

        $currentMonth = now()->format('Y-m');
        $emp = Employee::create([
            'name'        => 'مصطفى كامل',
            'phone'       => '01577778888',
            'job_title'   => 'باريستا',
            'base_salary' => 3500.00,
            'hire_date'   => now(),
            'is_active'   => true,
        ]);

        // التأكد من وجود سجل الشهر
        $payroll = EmployeePayroll::firstOrCreate(
            ['employee_id' => $emp->id, 'salary_month' => $currentMonth],
            ['base_salary' => 3500.00, 'net_salary' => 3500.00, 'paid_amount' => 0.00, 'status' => 'pending']
        );

        $payData = [
            'amount'         => 3500.00,
            'payment_method' => 'cash',
            'notes'          => 'صرف نقداً من خزينة الوردية',
        ];

        $res = $this->post(route('employees.payMonth', $payroll->id), $payData);
        $res->assertRedirect(route('employees.show', $emp->id));

        $freshPayroll = $payroll->fresh();
        $this->assertEquals('paid', $freshPayroll->status);
        $this->assertEquals(3500.00, (float) $freshPayroll->paid_amount);
        $this->assertEquals('cash', $freshPayroll->payment_method);
        $this->assertEquals($this->admin->id, $freshPayroll->paid_by);
    }

    public function test_paying_all_dues_settles_all_pending_months(): void
    {
        $this->actingAs($this->admin);

        $m1 = now()->subMonth()->format('Y-m');
        $m2 = now()->format('Y-m');

        $emp = Employee::create([
            'name'        => 'نادر سامي',
            'phone'       => '01033334444',
            'job_title'   => 'كاشير',
            'base_salary' => 3000.00,
            'hire_date'   => now()->subMonth(),
            'is_active'   => true,
        ]);

        EmployeePayroll::create([
            'employee_id'  => $emp->id,
            'salary_month' => $m1,
            'base_salary'  => 3000.00,
            'net_salary'   => 3000.00,
            'paid_amount'  => 0.00,
            'status'       => 'pending',
        ]);

        EmployeePayroll::create([
            'employee_id'  => $emp->id,
            'salary_month' => $m2,
            'base_salary'  => 3000.00,
            'net_salary'   => 3000.00,
            'paid_amount'  => 0.00,
            'status'       => 'pending',
        ]);

        $this->assertEquals(6000.00, $emp->total_dues);

        // تسديد جميع المستحقات
        $payData = [
            'payment_method' => 'instapay',
            'notes'          => 'تحويل إنستاباي لكافة المتأخرات',
        ];

        $res = $this->post(route('employees.payAllDues', $emp->id), $payData);
        $res->assertRedirect(route('employees.show', $emp->id));

        $this->assertEquals(0.00, $emp->fresh()->total_dues);
        $this->assertEquals(6000.00, $emp->fresh()->total_paid);

        $p1 = EmployeePayroll::where('employee_id', $emp->id)->where('salary_month', $m1)->first();
        $p2 = EmployeePayroll::where('employee_id', $emp->id)->where('salary_month', $m2)->first();
        $this->assertEquals('paid', $p1->status);
        $this->assertEquals('paid', $p2->status);
    }

    public function test_export_all_employees_excel(): void
    {
        $this->actingAs($this->admin);

        Employee::create([
            'name'        => 'سالم غانم',
            'phone'       => '01188887777',
            'job_title'   => 'شيف قهوة',
            'base_salary' => 4200.00,
            'is_active'   => true,
        ]);

        $res = $this->get(route('employees.export'));
        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $res->streamedContent();
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $content); // UTF-8 BOM
        $this->assertStringContainsString('سالم غانم', $content);
        $this->assertStringContainsString('01188887777', $content);
        $this->assertStringContainsString('شيف قهوة', $content);
    }

    public function test_export_single_employee_excel(): void
    {
        $this->actingAs($this->admin);

        $emp = Employee::create([
            'name'        => 'سامح شكري',
            'phone'       => '01066667777',
            'job_title'   => 'نظافة وتجهيز',
            'base_salary' => 2500.00,
            'is_active'   => true,
        ]);

        $res = $this->get(route('employees.exportSingle', $emp->id));
        $res->assertStatus(200);
        $res->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $res->streamedContent();
        $this->assertStringStartsWith(chr(0xEF).chr(0xBB).chr(0xBF), $content);
        $this->assertStringContainsString('كشف حساب ومسير رواتب الموظف', $content);
        $this->assertStringContainsString('سامح شكري', $content);
        $this->assertStringContainsString('2500.00', $content);
    }

    public function test_search_and_filter_on_employees_index(): void
    {
        $this->actingAs($this->admin);

        $activeEmp = Employee::create([
            'name'        => 'خالد الجندي',
            'phone'       => '01211119999',
            'job_title'   => 'باريستا محترف',
            'base_salary' => 4000.00,
            'is_active'   => true,
        ]);

        $inactiveEmp = Employee::create([
            'name'        => 'مروان خوري',
            'phone'       => '01044445555',
            'job_title'   => 'ويتر سابق',
            'base_salary' => 2800.00,
            'is_active'   => false,
        ]);

        // 1. Search by name
        $resSearch = $this->get(route('employees.index', ['search' => 'خالد الجندي']));
        $resSearch->assertSee('خالد الجندي');
        $resSearch->assertDontSee('مروان خوري');

        // 2. Filter by status active
        $resActive = $this->get(route('employees.index', ['status' => 'active']));
        $resActive->assertSee('خالد الجندي');
        $resActive->assertDontSee('مروان خوري');

        // 3. Filter by status inactive
        $resInactive = $this->get(route('employees.index', ['status' => 'inactive']));
        $resInactive->assertSee('مروان خوري');
        $resInactive->assertDontSee('خالد الجندي');
    }
}
