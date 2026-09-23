<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ShiftService
{
    public function openShift(User $user, float $openingFloat): Shift
    {
        $existing = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            throw new InvalidArgumentException('الموظف لديه شِفت مفتوح بالفعل.');
        }

        $openingFloat = round(max(0.0, $openingFloat), 2);

        return Shift::create([
            'user_id'       => $user->id,
            'opening_float' => $openingFloat,
            'expected_cash' => $openingFloat,
            'start_time'    => now(),
            'status'        => 'open',
        ]);
    }

    public function recordCashDrop(Shift $shift, float $amount, ?string $reason = null): Shift
    {
        return DB::transaction(function () use ($shift, $amount, $reason) {
            $lockedShift = Shift::where('id', $shift->id)->lockForUpdate()->firstOrFail();

            if ($lockedShift->status !== 'open') {
                throw new InvalidArgumentException('لا يمكن تسجيل مسحوبات نقدية لشِفت مغلق.');
            }

            $amount = round(max(0.0, $amount), 2);
            $newDrops = round((float) $lockedShift->cash_drops + $amount, 2);
            $newExpected = round((float) $lockedShift->expected_cash - $amount, 2);

            $notes = $lockedShift->closing_notes ? $lockedShift->closing_notes . "\n" : '';
            $notes .= sprintf('[%s] توريد/مسحوب نقدي بقيمة %s: %s', now()->format('H:i'), number_format($amount, 2), $reason ?? 'بدون سبب');

            $lockedShift->update([
                'cash_drops'    => $newDrops,
                'expected_cash' => $newExpected,
                'closing_notes' => $notes,
            ]);

            return $lockedShift;
        });
    }

    public function closeShift(Shift $shift, float $actualCash, ?string $notes = null): Shift
    {
        return DB::transaction(function () use ($shift, $actualCash, $notes) {
            /** @var Shift $lockedShift */
            $lockedShift = Shift::where('id', $shift->id)->lockForUpdate()->firstOrFail();

            if ($lockedShift->status === 'closed') {
                throw new InvalidArgumentException('هذا الشِفت مغلق بالفعل.');
            }

            // Aggregate invoices linked to this shift
            $sales = Invoice::where('shift_id', $lockedShift->id)
                ->selectRaw('payment_method, SUM(total) as aggregate')
                ->groupBy('payment_method')
                ->pluck('aggregate', 'payment_method');

            $cashSales = round((float) ($sales['cash'] ?? 0), 2);
            $cardSales = round((float) ($sales['card'] ?? 0), 2);
            $instapaySales = round((float) ($sales['InstaPay'] ?? 0), 2);

            $openingFloat = (float) $lockedShift->opening_float;
            $cashDrops = (float) $lockedShift->cash_drops;

            $expectedCash = round($openingFloat + $cashSales - $cashDrops, 2);
            $actualCash = round(max(0.0, $actualCash), 2);
            $difference = round($actualCash - $expectedCash, 2);

            $fullNotes = $lockedShift->closing_notes ? $lockedShift->closing_notes . "\n" : '';
            if ($notes) {
                $fullNotes .= "ملاحظات الإغلاق: " . $notes;
            }

            $lockedShift->update([
                'cash_sales'     => $cashSales,
                'card_sales'     => $cardSales,
                'instapay_sales' => $instapaySales,
                'expected_cash'  => $expectedCash,
                'actual_cash'    => $actualCash,
                'difference'     => $difference,
                'status'         => 'closed',
                'end_time'       => now(),
                'closing_notes'  => trim($fullNotes) ?: null,
            ]);

            return $lockedShift;
        });
    }

    public function getActiveShift(User $user): ?Shift
    {
        return Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('id')
            ->first();
    }
}
