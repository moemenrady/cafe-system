<?php

namespace App\Http\Controllers;

use App\Models\Invoice;

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
        $invoice = Invoice::with(['creator', 'items.menu'])->findOrFail($id);

        return response()->json([
            'invoice_number' => $invoice->invoice_number,
            'cashier' => $invoice->creator->name ?? 'غير معروف',
            'created_at' => $invoice->created_at->format('Y-m-d h:i A'),
            'total' => number_format($invoice->total, 2),
            'items' => $invoice->items->map(function ($item) {
                return [
                    'name' => $item->menu->name ?? 'منتج محذوف',
                    'quantity' => $item->quantity,
                    'price' => number_format($item->item_price, 2),
                    'total' => number_format($item->total, 2),
                ];
            })
        ]);
    }
}
