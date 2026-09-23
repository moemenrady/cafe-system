<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Shift;
use App\Models\ShiftAction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ShiftActionService
{
    /**
     * تسجيل حركة طلب بيع في الشيفت
     */
    public function logOrder(Order $order, string $actionType = 'order_created'): ?ShiftAction
    {
        $shiftId = $order->shift_id;
        if (!$shiftId) {
            $shiftId = Shift::where('user_id', $order->created_by ?: Auth::id())
                ->where('status', 'open')
                ->latest('id')
                ->value('id');
            if ($shiftId) {
                $order->update(['shift_id' => $shiftId]);
            }
        }

        if (!$shiftId) {
            return null;
        }

        $order->loadMissing(['items.menu', 'table', 'customer']);

        $typeArabic = match ($order->type) {
            'dine_in' => 'صالة',
            'takeaway' => 'سفري / تيك أواي',
            'delivery' => 'دليفري / توصيل',
            default => $order->type,
        };

        $title = $actionType === 'order_created'
            ? sprintf('طلب بيع (%s) #%s', $typeArabic, $order->order_number)
            : sprintf('تعديل طلب بيع (%s) #%s', $typeArabic, $order->order_number);

        $items = $order->items->map(function ($item) {
            return [
                'id'       => $item->id,
                'name'     => $item->menu->name ?? 'صنف غير معروف',
                'quantity' => (int) $item->quantity,
                'price'    => (float) $item->price,
                'total'    => (float) $item->total,
            ];
        })->toArray();

        $details = [
            'order_id'         => $order->id,
            'order_number'     => $order->order_number,
            'type'             => $order->type,
            'type_arabic'      => $typeArabic,
            'table'            => $order->table ? ($order->table->name ?? ('طاولة ' . $order->table->table_number)) : null,
            'customer_name'    => $order->customer->name ?? null,
            'customer_phone'   => $order->phone ?? $order->customer->phone ?? null,
            'delivery_address' => $order->delivery_address ?? null,
            'delivery_person'  => $order->delivery_person ?? null,
            'payment_status'   => $order->payment_status,
            'payment_method'   => $order->payment_method ?? 'cash',
            'subtotal'         => (float) ($order->total + $order->discount),
            'discount'         => (float) $order->discount,
            'total'            => (float) $order->total,
            'notes'            => $order->notes,
            'items'            => $items,
        ];

        return ShiftAction::create([
            'shift_id'       => $shiftId,
            'user_id'        => $order->created_by ?: (Auth::id() ?? 1),
            'action_type'    => $actionType,
            'action_title'   => $title,
            'model_type'     => Order::class,
            'model_id'       => $order->id,
            'amount'         => (float) $order->total,
            'payment_method' => $order->payment_method ?? 'cash',
            'details'        => $details,
        ]);
    }

    /**
     * تسجيل حركة فاتورة مبيعات في الشيفت
     */
    public function logInvoice(Invoice $invoice, string $actionType = 'invoice_created'): ?ShiftAction
    {
        $shiftId = $invoice->shift_id;
        if (!$shiftId) {
            $shiftId = Shift::where('user_id', $invoice->created_by ?: Auth::id())
                ->where('status', 'open')
                ->latest('id')
                ->value('id');
            if ($shiftId) {
                $invoice->update(['shift_id' => $shiftId]);
            }
        }

        if (!$shiftId) {
            return null;
        }

        $invoice->loadMissing(['items.menu', 'client', 'creator']);

        $title = $actionType === 'invoice_created'
            ? sprintf('إصدار فاتورة مبيعات #%s', $invoice->invoice_number)
            : sprintf('تعديل فاتورة مبيعات #%s', $invoice->invoice_number);

        $items = $invoice->items->map(function ($item) {
            return [
                'id'       => $item->id,
                'name'     => $item->menu->name ?? 'صنف غير معروف',
                'quantity' => (int) $item->quantity,
                'price'    => (float) $item->item_price,
                'total'    => (float) $item->total,
            ];
        })->toArray();

        $details = [
            'invoice_id'     => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name'  => $invoice->client->name ?? null,
            'customer_phone' => $invoice->client->phone ?? null,
            'payment_method' => $invoice->payment_method,
            'subtotal'       => (float) ($invoice->total + $invoice->discount),
            'discount'       => (float) $invoice->discount,
            'total'          => (float) $invoice->total,
            'note'           => $invoice->note,
            'items'          => $items,
        ];

        return ShiftAction::create([
            'shift_id'       => $shiftId,
            'user_id'        => $invoice->created_by ?: (Auth::id() ?? 1),
            'action_type'    => $actionType,
            'action_title'   => $title,
            'model_type'     => Invoice::class,
            'model_id'       => $invoice->id,
            'amount'         => (float) $invoice->total,
            'payment_method' => $invoice->payment_method,
            'details'        => $details,
        ]);
    }

    /**
     * تسجيل تعديل على فاتورة
     */
    public function logInvoiceUpdate(Invoice $invoice, array $changes = []): ?ShiftAction
    {
        return $this->logInvoice($invoice, 'invoice_updated');
    }

    /**
     * تسجيل حركة مصروف في الشيفت
     */
    public function logExpense(Expense $expense, string $actionType = 'expense_created', array $changes = []): ?ShiftAction
    {
        $shiftId = $expense->shift_id;
        if (!$shiftId) {
            $shiftId = Shift::where('user_id', $expense->created_by ?: Auth::id())
                ->where('status', 'open')
                ->latest('id')
                ->value('id');
            if ($shiftId) {
                $expense->update(['shift_id' => $shiftId]);
            }
        }

        if (!$shiftId) {
            return null;
        }

        $expense->loadMissing(['category', 'creator']);

        $title = $actionType === 'expense_created'
            ? sprintf('تسجيل مصروف: %s', $expense->title ?: ($expense->category->name ?? 'مصروف عام'))
            : sprintf('تعديل مصروف: %s', $expense->title ?: ($expense->category->name ?? 'مصروف عام'));

        $details = [
            'expense_id'    => $expense->id,
            'title'         => $expense->title,
            'category_name' => $expense->category->name ?? 'عام',
            'amount'        => (float) $expense->amount,
            'expense_date'  => $expense->expense_date ? $expense->expense_date->format('Y-m-d') : null,
            'notes'         => $expense->notes,
            'changes'       => $changes,
        ];

        return ShiftAction::create([
            'shift_id'       => $shiftId,
            'user_id'        => $expense->created_by ?: (Auth::id() ?? 1),
            'action_type'    => $actionType,
            'action_title'   => $title,
            'model_type'     => Expense::class,
            'model_id'       => $expense->id,
            'amount'         => (float) $expense->amount,
            'payment_method' => 'cash',
            'details'        => $details,
        ]);
    }

    /**
     * تسجيل مسحوب نقدي
     */
    public function logCashDrop(Shift $shift, float $amount, ?string $reason = null): ShiftAction
    {
        return ShiftAction::create([
            'shift_id'       => $shift->id,
            'user_id'        => Auth::id() ?: $shift->user_id,
            'action_type'    => 'cash_drop',
            'action_title'   => 'تسجيل مسحوب نقدي من الدرج',
            'model_type'     => Shift::class,
            'model_id'       => $shift->id,
            'amount'         => $amount,
            'payment_method' => 'cash',
            'details'        => [
                'amount' => $amount,
                'reason' => $reason ?? 'توريد/مسحوب نقدي للدرج',
                'time'   => now()->format('Y-m-d h:i A'),
            ],
        ]);
    }

    /**
     * تسجيل إغلاق الشيفت
     */
    public function logShiftClosed(Shift $shift, string $closedBy = 'employee'): ShiftAction
    {
        $title = match ($closedBy) {
            'manager'       => 'إغلاق الشيفت بواسطة المشرف / المدير',
            'auto_midnight' => 'إغلاق الشيفت تلقائياً في تمام الساعة 12:00 منتصف الليل',
            default         => 'إغلاق الشيفت وتسوية الحسابات من قبل الموظف',
        };

        return ShiftAction::create([
            'shift_id'       => $shift->id,
            'user_id'        => Auth::id() ?: $shift->user_id,
            'action_type'    => 'shift_closed',
            'action_title'   => $title,
            'model_type'     => Shift::class,
            'model_id'       => $shift->id,
            'amount'         => (float) $shift->actual_cash,
            'payment_method' => 'cash',
            'details'        => [
                'closed_by'      => $closedBy,
                'closed_by_name' => Auth::user()->name ?? 'النظام',
                'opening_float'  => (float) $shift->opening_float,
                'cash_sales'     => (float) $shift->cash_sales,
                'card_sales'     => (float) $shift->card_sales,
                'instapay_sales' => (float) $shift->instapay_sales,
                'total_sales'    => (float) ($shift->cash_sales + $shift->card_sales + $shift->instapay_sales),
                'expenses_total' => (float) $shift->expenses_total,
                'cash_drops'     => (float) $shift->cash_drops,
                'expected_cash'  => (float) $shift->expected_cash,
                'actual_cash'    => (float) $shift->actual_cash,
                'difference'     => (float) $shift->difference,
                'closing_notes'  => $shift->closing_notes,
            ],
        ]);
    }
}
