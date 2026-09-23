<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    /**
     * عرض قائمة الفواتير المسجلة مع الإحصائيات والفلاتر
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['creator', 'client', 'order.table', 'items.menu'])
            ->orderBy('created_at', 'desc');

        // البحث برقم الفاتورة أو الملاحظات أو اسم العميل
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // فلترة طريقة الدفع
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // فلترة التاريخ السريع أو المخصص
        if ($request->filled('date_filter')) {
            match ($request->date_filter) {
                'today' => $query->whereDate('created_at', Carbon::today()),
                'yesterday' => $query->whereDate('created_at', Carbon::yesterday()),
                'this_week' => $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
                'this_month' => $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year),
                default => null,
            };
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }
        }

        // حساب الإحصائيات العامة لسجل الفواتير
        $stats = [
            'total_sales'      => Invoice::sum('total'),
            'invoices_count'   => Invoice::count(),
            'cash_total'       => Invoice::where('payment_method', 'cash')->sum('total'),
            'instapay_total'   => Invoice::where('payment_method', 'InstaPay')->sum('total'),
            'card_total'       => Invoice::where('payment_method', 'card')->sum('total'),
            'today_sales'      => Invoice::whereDate('created_at', Carbon::today())->sum('total'),
            'today_count'      => Invoice::whereDate('created_at', Carbon::today())->count(),
        ];

        $invoices = $query->paginate(15)->appends($request->query());

        return view('sales_invoices.index', compact('invoices', 'stats'));
    }

    /**
     * عرض تفاصيل الفاتورة (صفحة متكاملة أو JSON عبر AJAX)
     */
    public function show($id, Request $request)
    {
        $invoice = Invoice::with([
            'creator',
            'client',
            'order.table',
            'order.customer',
            'items.menu.category',
            'shift'
        ])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'invoice_number' => $invoice->invoice_number,
                'cashier'        => $invoice->creator->name ?? 'غير معروف',
                'created_at'     => $invoice->created_at->format('Y-m-d h:i A'),
                'payment_method' => $invoice->payment_method,
                'subtotal'       => number_format($invoice->total + $invoice->discount, 2),
                'discount'       => number_format($invoice->discount, 2),
                'total'          => number_format($invoice->total, 2),
                'note'           => $invoice->note,
                'items'          => $invoice->items->map(function ($item) {
                    return [
                        'name'       => $item->menu->name ?? 'منتج محذوف',
                        'quantity'   => $item->quantity,
                        'price'      => number_format($item->item_price, 2),
                        'total'      => number_format($item->total, 2),
                    ];
                }),
            ]);
        }

        return view('sales_invoices.show', compact('invoice'));
    }
}
