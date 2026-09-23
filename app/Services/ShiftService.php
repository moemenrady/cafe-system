<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShiftService
{
    public function __construct(
        protected ?ShiftActionService $actionService = null
    ) {
        $this->actionService = $actionService ?: app(ShiftActionService::class);
    }

    /**
     * فتح شيفت جديد للموظف
     */
    public function openShift(User $user, float $openingFloat): Shift
    {
        // التحقق من صلاحية الموظف في بدء الشيفت
        if (!$user->canStartShift()) {
            throw new InvalidArgumentException('تم منعك من بدء الشيفت');
        }

        // فحص أي شيفت مفتوح حالياً وإغلاقه إذا كان قد تجاوز منتصف الليل
        $existing = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            if ($existing->shouldAutoCloseAtMidnight()) {
                $this->autoCloseMidnight($existing);
            } else {
                throw new InvalidArgumentException('الموظف لديه شِفت مفتوح بالفعل.');
            }
        }

        $openingFloat = round(max(0.0, $openingFloat), 2);

        $shift = Shift::create([
            'user_id'        => $user->id,
            'opening_float'  => $openingFloat,
            'expected_cash'  => $openingFloat,
            'cash_sales'     => 0,
            'card_sales'     => 0,
            'instapay_sales' => 0,
            'cash_drops'     => 0,
            'expenses_total' => 0,
            'start_time'     => now(),
            'status'         => 'open',
        ]);

        return $shift;
    }

    /**
     * تسجيل مسحوب نقدي من الشيفت
     */
    public function recordCashDrop(Shift $shift, float $amount, ?string $reason = null): Shift
    {
        return DB::transaction(function () use ($shift, $amount, $reason) {
            /** @var Shift $lockedShift */
            $lockedShift = Shift::where('id', $shift->id)->lockForUpdate()->firstOrFail();

            if ($lockedShift->status !== 'open') {
                throw new InvalidArgumentException('لا يمكن تسجيل مسحوبات نقدية لشِفت مغلق.');
            }

            $amount = round(max(0.0, $amount), 2);
            $newDrops = round((float) $lockedShift->cash_drops + $amount, 2);

            // حساب المصروفات الحالية للشيفت
            $expensesTotal = (float) Expense::where('shift_id', $lockedShift->id)->sum('amount');
            $newExpected = round((float) $lockedShift->opening_float + (float) $lockedShift->cash_sales - $expensesTotal - $newDrops, 2);

            $notes = $lockedShift->closing_notes ? $lockedShift->closing_notes . "\n" : '';
            $notes .= sprintf('[%s] توريد/مسحوب نقدي بقيمة %s: %s', now()->format('H:i'), number_format($amount, 2), $reason ?? 'بدون سبب');

            $lockedShift->update([
                'cash_drops'    => $newDrops,
                'expected_cash' => $newExpected,
                'closing_notes' => $notes,
            ]);

            $this->actionService->logCashDrop($lockedShift, $amount, $reason);

            return $lockedShift;
        });
    }

    /**
     * إغلاق الشيفت وتسوية الحسابات المالية (الدخل، المصروفات، الصافي)
     */
    public function closeShift(Shift $shift, float $actualCash, ?string $notes = null, string $closedBy = 'employee'): Shift
    {
        return DB::transaction(function () use ($shift, $actualCash, $notes, $closedBy) {
            /** @var Shift $lockedShift */
            $lockedShift = Shift::where('id', $shift->id)->lockForUpdate()->firstOrFail();

            if ($lockedShift->status === 'closed') {
                throw new InvalidArgumentException('هذا الشِفت مغلق بالفعل.');
            }

            // 1. تجميع المبيعات حسب طرق الدفع من الفواتير المرتبطة بالشيفت
            $salesData     = $this->calculateShiftSales($lockedShift->id);
            $cashSales     = $salesData['cash_sales'];
            $cardSales     = $salesData['card_sales'];
            $instapaySales = $salesData['instapay_sales'];

            // 2. تجميع إجمالي المصروفات المسجلة في هذا الشيفت
            $expensesTotal = round((float) Expense::where('shift_id', $lockedShift->id)->sum('amount'), 2);

            $openingFloat = (float) $lockedShift->opening_float;
            $cashDrops    = (float) $lockedShift->cash_drops;

            // 3. حساب المبلغ المطلوب توريده / الصافي المتوقع في الدرج:
            // المطلوب توريده = العهدة الافتتاحية + المبيعات النقدية - المصروفات النقدية - المسحوبات
            $expectedCash = round($openingFloat + $cashSales - $expensesTotal - $cashDrops, 2);
            $actualCash   = round(max(0.0, $actualCash), 2);
            $difference   = round($actualCash - $expectedCash, 2);

            $fullNotes = $lockedShift->closing_notes ? $lockedShift->closing_notes . "\n" : '';
            if ($notes) {
                $fullNotes .= "ملاحظات الإغلاق: " . $notes;
            }

            $lockedShift->update([
                'cash_sales'     => $cashSales,
                'card_sales'     => $cardSales,
                'instapay_sales' => $instapaySales,
                'expenses_total' => $expensesTotal,
                'expected_cash'  => $expectedCash,
                'actual_cash'    => $actualCash,
                'difference'     => $difference,
                'status'         => 'closed',
                'end_time'       => now(),
                'closing_notes'  => trim($fullNotes) ?: null,
            ]);

            $this->actionService->logShiftClosed($lockedShift, $closedBy);

            return $lockedShift;
        });
    }

    /**
     * إغلاق الشيفت بواسطة المشرف أو المدير
     */
    public function closeByManager(Shift $shift, User $manager, float $actualCash, ?string $notes = null): Shift
    {
        $managerNote = sprintf('[إغلاق إداري بواسطة: %s] %s', $manager->name, $notes ?? 'تم إغلاق الشيفت من قبل الإدارة.');
        return $this->closeShift($shift, $actualCash, $managerNote, 'manager');
    }

    /**
     * الإغلاق التلقائي للشيفت عند الساعة 12 منتصف الليل
     */
    public function autoCloseMidnight(Shift $shift): Shift
    {
        return DB::transaction(function () use ($shift) {
            /** @var Shift $lockedShift */
            $lockedShift = Shift::where('id', $shift->id)->lockForUpdate()->firstOrFail();

            if ($lockedShift->status === 'closed') {
                return $lockedShift;
            }

            // تجميع المبيعات
            $salesData     = $this->calculateShiftSales($lockedShift->id);
            $cashSales     = $salesData['cash_sales'];
            $cardSales     = $salesData['card_sales'];
            $instapaySales = $salesData['instapay_sales'];
            $expensesTotal = round((float) Expense::where('shift_id', $lockedShift->id)->sum('amount'), 2);

            $openingFloat = (float) $lockedShift->opening_float;
            $cashDrops    = (float) $lockedShift->cash_drops;
            $expectedCash = round($openingFloat + $cashSales - $expensesTotal - $cashDrops, 2);

            // الإغلاق التلقائي يسجل الفعلي كالمتوقع افتراضياً مع تدوين الملاحظة
            $actualCash = $expectedCash;
            $difference = 0.0;

            $midnightTime = $lockedShift->start_time ? $lockedShift->start_time->copy()->endOfDay() : now();

            $fullNotes = $lockedShift->closing_notes ? $lockedShift->closing_notes . "\n" : '';
            $fullNotes .= '[إغلاق تلقائي] تم إغلاق الشيفت تلقائياً في تمام الساعة 12:00 منتصف الليل.';

            $lockedShift->update([
                'cash_sales'     => $cashSales,
                'card_sales'     => $cardSales,
                'instapay_sales' => $instapaySales,
                'expenses_total' => $expensesTotal,
                'expected_cash'  => $expectedCash,
                'actual_cash'    => $actualCash,
                'difference'     => $difference,
                'status'         => 'closed',
                'end_time'       => $midnightTime,
                'closing_notes'  => trim($fullNotes),
            ]);

            $this->actionService->logShiftClosed($lockedShift, 'auto_midnight');

            return $lockedShift;
        });
    }

    /**
     * جلب الشيفت المفتوح والنشط للموظف، مع الفحص الآني للإغلاق التلقائي عند منتصف الليل
     */
    public function getActiveShift(User $user): ?Shift
    {
        $shift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('id')
            ->first();

        if ($shift && $shift->shouldAutoCloseAtMidnight()) {
            $this->autoCloseMidnight($shift);
            return null;
        }

        return $shift;
    }

    /**
     * حساب مبيعات الشيفت مجمعة حسب طرق الدفع (كاش، فيزا، إنستا باي)
     */
    public function calculateShiftSales(int $shiftId): array
    {
        $invoices = Invoice::where('shift_id', $shiftId)->get();

        $cashSales     = 0.0;
        $cardSales     = 0.0;
        $instapaySales = 0.0;

        foreach ($invoices as $invoice) {
            $method = strtolower(trim((string) $invoice->payment_method));
            $total  = (float) $invoice->total;

            if ($method === 'instapay') {
                $instapaySales += $total;
            } elseif ($method === 'card' || $method === 'visa') {
                $cardSales += $total;
            } else {
                $cashSales += $total;
            }
        }

        $cashSales     = round($cashSales, 2);
        $cardSales     = round($cardSales, 2);
        $instapaySales = round($instapaySales, 2);
        $totalSales    = round($cashSales + $cardSales + $instapaySales, 2);

        return [
            'cash_sales'     => $cashSales,
            'card_sales'     => $cardSales,
            'instapay_sales' => $instapaySales,
            'total_sales'    => $totalSales,
        ];
    }

    /**
     * حساب إحصائيات الشيفت الحية للعرض بالواجهات
     */
    public function getShiftLiveSummary(Shift $shift): array
    {
        $shift->refresh();

        $salesData     = $this->calculateShiftSales($shift->id);
        $cashSales     = $salesData['cash_sales'];
        $cardSales     = $salesData['card_sales'];
        $instapaySales = $salesData['instapay_sales'];
        $totalSales    = $salesData['total_sales'];

        $expensesTotal = round((float) Expense::where('shift_id', $shift->id)->sum('amount'), 2);
        $openingFloat  = (float) $shift->opening_float;
        $cashDrops     = (float) $shift->cash_drops;

        // الصافي المطلوب توريده
        $expectedCash = round($openingFloat + $cashSales - $expensesTotal - $cashDrops, 2);
        $netIncome    = round($totalSales - $expensesTotal, 2);

        return [
            'opening_float'  => $openingFloat,
            'cash_sales'     => $cashSales,
            'card_sales'     => $cardSales,
            'instapay_sales' => $instapaySales,
            'total_sales'    => $totalSales,
            'expenses_total' => $expensesTotal,
            'cash_drops'     => $cashDrops,
            'expected_cash'  => $expectedCash, // المبلغ المطلوب توريده
            'net_income'     => $netIncome,    // صافي الدخل (المبيعات - المصروفات)
            'actions_count'  => $shift->actions()->count(),
            'invoices_count' => Invoice::where('shift_id', $shift->id)->count(),
            'expenses_count' => Expense::where('shift_id', $shift->id)->count(),
        ];
    }
}
