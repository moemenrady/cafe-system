<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Shift;
use App\Models\ShiftAction;
use App\Models\User;
use App\Services\ShiftService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComprehensiveShiftTest extends TestCase
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
            'role'            => 'cashier',
            'is_active'       => true,
            'can_start_shift' => true,
        ]);
    }

    /**
     * 1. الموظف بدون شيفت يتم منعه من دخول البيع أو تسجيل المصروف مع رسالة التنبيه المحددة
     */
    public function test_employee_without_active_shift_is_blocked_with_specified_alert(): void
    {
        $this->actingAs($this->cashier);

        // محاولة دخول شاشة البيع GET /pos
        $posResponse = $this->get(route('pos.index'));
        $posResponse->assertRedirect(route('shifts.my_shift'));
        $posResponse->assertSessionHas('error', 'لا تتمكن من فعل هذه الخطوه دون بدء شيفت');

        // محاولة إضافة مصروف GET /expenses/create
        $expenseResponse = $this->get(route('expenses.create'));
        $expenseResponse->assertRedirect(route('shifts.my_shift'));
        $expenseResponse->assertSessionHas('error', 'لا تتمكن من فعل هذه الخطوه دون بدء شيفت');

        // محاولة إرسال طلب أوردر عبر AJAX POST /orders
        $orderResponse = $this->postJson(route('orders.store'), [
            'type'           => 'takeaway',
            'payment_method' => 'cash',
            'items'          => [],
        ]);

        $orderResponse->assertStatus(403);
        $orderResponse->assertJson([
            'success' => false,
            'message' => 'لا تتمكن من فعل هذه الخطوه دون بدء شيفت',
        ]);
    }

    /**
     * 2. المدير يمكنه منع الموظف من بدء شيفت جديد، وعند محاولة الموظف يظهر له "تم منعك من بدء الشيفت"
     */
    public function test_manager_can_block_employee_and_employee_sees_blocked_message(): void
    {
        $this->actingAs($this->admin);

        // المدير يمنع الكاشير
        $toggleResponse = $this->post(route('shifts.toggle_block', $this->cashier->id));
        $toggleResponse->assertSessionHas('success');

        $this->cashier->refresh();
        $this->assertFalse($this->cashier->canStartShift());

        // الآن الكاشير يحاول بدء شيفت
        $this->actingAs($this->cashier);
        $startResponse = $this->post(route('shifts.store'), [
            'opening_float' => 150,
        ]);

        $startResponse->assertSessionHas('error', 'تم منعك من بدء الشيفت');

        // فحص JSON أيضاً
        $jsonResponse = $this->postJson(route('shifts.store'), [
            'opening_float' => 150,
        ]);
        $jsonResponse->assertStatus(403);
        $jsonResponse->assertJson([
            'success' => false,
            'message' => 'تم منعك من بدء الشيفت',
        ]);

        // المدير يعيد السماح للموظف
        $this->actingAs($this->admin);
        $this->post(route('shifts.toggle_block', $this->cashier->id));
        $this->cashier->refresh();
        $this->assertTrue($this->cashier->canStartShift());
    }

    /**
     * 3. الموظف يبدأ الشيفت ويسجل طلبات ومصروفات وتظهر في سجل الحركات shift_actions
     */
    public function test_employee_can_start_shift_and_actions_are_logged(): void
    {
        $this->actingAs($this->cashier);

        // فتح الشيفت بعهدة 200 ج.م
        $openResponse = $this->post(route('shifts.store'), [
            'opening_float' => 200,
        ]);
        $openResponse->assertRedirect(route('shifts.my_shift'));

        $shift = Shift::where('user_id', $this->cashier->id)->where('status', 'open')->first();
        $this->assertNotNull($shift);
        $this->assertEquals(200.0, (float) $shift->opening_float);

        // تسجيل مصروف في الشيفت
        $cat = ExpenseCategory::create(['name' => 'نظافة', 'is_active' => true]);
        $expenseResponse = $this->post(route('expenses.store'), [
            'category_id'  => $cat->id,
            'amount'       => 50.0,
            'expense_date' => now()->format('Y-m-d'),
            'notes'        => 'شراء أدوات نظافة',
        ]);
        $expenseResponse->assertRedirect(route('expenses.index'));

        // التأكد من تسجيل المصروف بالشيفت وحفظ الأكشن
        $expense = Expense::latest('id')->first();
        $this->assertEquals($shift->id, $expense->shift_id);

        $action = ShiftAction::where('shift_id', $shift->id)->where('action_type', 'expense_created')->first();
        $this->assertNotNull($action);
        $this->assertEquals(50.0, (float) $action->amount);

        // فحص جلب تفاصيل الأكشن للمودال (Read-Only)
        $detailResponse = $this->getJson(route('shifts.actions.details', $action->id));
        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonPath('data.amount', 50);
        $detailResponse->assertJsonPath('data.details.title', 'نظافة');
    }

    /**
     * 4. إغلاق الشيفت بحساب الدخل والمصروفات والعهدة والصافي
     */
    public function test_shift_closing_reconciliation_calculation(): void
    {
        $this->actingAs($this->cashier);
        $shiftService = app(ShiftService::class);

        // عهدة 300
        $shift = $shiftService->openShift($this->cashier, 300.0);

        // إنشاء فاتورة كاش 400 ج.م وفاتورة فيزا 200 ج.م
        Invoice::create([
            'invoice_number' => 'INV-TEST-CASH',
            'total'          => 400.0,
            'payment_method' => 'cash',
            'shift_id'       => $shift->id,
            'created_by'     => $this->cashier->id,
        ]);

        Invoice::create([
            'invoice_number' => 'INV-TEST-CARD',
            'total'          => 200.0,
            'payment_method' => 'card',
            'shift_id'       => $shift->id,
            'created_by'     => $this->cashier->id,
        ]);

        // تسجيل مصروف 70 ج.م
        $cat = ExpenseCategory::create(['name' => 'خامات', 'is_active' => true]);
        Expense::create([
            'category_id'  => $cat->id,
            'title'        => 'شراء حليب',
            'amount'       => 70.0,
            'shift_id'     => $shift->id,
            'expense_date' => now(),
            'created_by'   => $this->cashier->id,
        ]);

        // تسجيل مسحوب نقدي 50 ج.م
        $shiftService->recordCashDrop($shift, 50.0, 'توريد للخزينة');

        // الحساب المتوقع في الدرج:
        // العهدة (300) + مبيعات الكاش (400) - المصروفات (70) - المسحوبات (50) = 580 ج.م
        $summary = $shiftService->getShiftLiveSummary($shift);
        $this->assertEquals(600.0, (float) $summary['total_sales']);
        $this->assertEquals(70.0, (float) $summary['expenses_total']);
        $this->assertEquals(580.0, (float) $summary['expected_cash']);

        // إغلاق الشيفت بمبلغ فعلي 580 (مطابق)
        $closeResponse = $this->post(route('shifts.close', $shift->id), [
            'actual_cash' => 580.0,
            'notes'       => 'حساب مطابق تماماً',
        ]);
        $closeResponse->assertRedirect(route('shifts.my_shift'));

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertEquals(580.0, (float) $shift->expected_cash);
        $this->assertEquals(580.0, (float) $shift->actual_cash);
        $this->assertEquals(0.0, (float) $shift->difference);
    }

    /**
     * 5. المدير يرى جميع الشيفتات ويمكنه إغلاق شيفت الموظف المفتوح
     */
    public function test_manager_can_view_and_force_close_employee_shift(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->openShift($this->cashier, 100.0);

        $this->actingAs($this->admin);

        // المدير يفتح صفحة index الشيفتات
        $indexResponse = $this->get(route('shifts.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($this->cashier->name);

        // المدير يفتح تفاصيل شيفت الموظف
        $showResponse = $this->get(route('shifts.show', $shift->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('إغلاق الشيفت للموظف');

        // المدير يقوم بإغلاق الشيفت للموظف
        $forceCloseResponse = $this->post(route('shifts.close', $shift->id), [
            'actual_cash' => 100.0,
            'notes'       => 'إغلاق إداري لتبديل الوردية',
        ]);
        $forceCloseResponse->assertRedirect(route('shifts.show', $shift->id));

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertStringContainsString('إغلاق إداري بواسطة', $shift->closing_notes);
    }

    /**
     * 6. الإغلاق التلقائي للشيفت عند منتصف الليل
     */
    public function test_shift_auto_closes_at_midnight(): void
    {
        $shiftService = app(ShiftService::class);

        // فتح شيفت أمس
        $yesterday = Carbon::yesterday()->setHour(14)->setMinute(0);
        $shift = Shift::create([
            'user_id'       => $this->cashier->id,
            'opening_float' => 200,
            'expected_cash' => 200,
            'start_time'    => $yesterday,
            'status'        => 'open',
        ]);

        $this->assertTrue($shift->shouldAutoCloseAtMidnight());

        // عند محاولة جلب الشيفت النشط يتم إغلاقه تلقائياً عند منتصف الليل
        $active = $shiftService->getActiveShift($this->cashier);
        $this->assertNull($active);

        $shift->refresh();
        $this->assertEquals('closed', $shift->status);
        $this->assertStringContainsString('12:00 منتصف الليل', $shift->closing_notes);
    }
}
