<?php

namespace App\Http\Controllers;

use App\Models\Invoice;

use App\Models\Sale; // الموديل الذي تستخدمه لحفظ الفواتير
use Illuminate\Http\Request;

class SalesInvoiceController extends Controller
{
    /**
     * عرض قائمة الفواتير المسجلة
     */

    public function index()
    {
        // جلب الفواتير الجديدة مرتبة من الأحدث للأقدم مع بيانات الكاشير (creator)
        $invoices = Invoice::with('creator')->orderBy('created_at', 'desc')->paginate(10);

        return view('sales_invoices.index', compact('invoices'));
    }

    /**
     * عرض تفاصيل الفاتورة عند الضغط عليها
     */
    public function show($id)
    {
        // جلب الفاتورة مع العلاقات المحددة في الموديلات لديك
        $invoice = Sale::with(['user', 'items.menu'])->findOrFail($id);

        return response()->json([
            'invoice_number' => $invoice->id,
            'cashier' => $invoice->user->name ?? 'غير معروف',
            'created_at' => $invoice->created_at->format('Y-m-d h:i A'),
            'paid_amount' => number_format($invoice->paid_amount, 2),
            'total' => number_format($invoice->total_price, 2),
            'items' => $invoice->items->map(function ($item) {
                return [
                    'name' => $item->menu->name ?? 'منتج محذوف',
                    'quantity' => $item->quantity,
                    'price' => number_format($item->price, 2),
                    'total' => number_format($item->price * $item->quantity, 2),
                ];
            })
        ]);
    }
}
