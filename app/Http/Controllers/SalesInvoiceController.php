<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Menu;
use App\Models\InventoryMovement;
use App\Models\InvoiceItem;
use App\Models\InvoiceTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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

    /**
     * صفحة تعديل الفاتورة – لمشرف وأدمن فقط
     */
    public function edit($id)
    {
        $invoice = Invoice::with(['items.menu.category', 'creator', 'client', 'order.table'])
            ->findOrFail($id);

        // استدعاء كل الأصناف المتاحة مرتبة بالفئة
        $menus = Menu::with('category')
            ->where('is_available', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        return view('sales_invoices.edit', compact('invoice', 'menus'));
    }

    /**
     * تنفيذ تعديل الفاتورة – يُرجع JSON للـ AJAX
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.menu_id'  => 'required|exists:menu,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount'         => 'nullable|numeric|min:0',
        ]);

        $invoice = Invoice::with('items')->findOrFail($id);
        $user    = auth()->user();

        // صلاحية التعديل: مشرف أو أدمن فقط
        if (! $user || ! in_array($user->role, ['admin', 'supervisor'], true)) {
            return response()->json([
                'success' => false,
                'error'   => 'غير مصرح لك بتعديل الفواتير. يتطلب صلاحية مشرف أو مدير.',
            ], 403);
        }

        $oldDataSnapshot = $invoice->toArray();

        DB::beginTransaction();
        try {
            // 1. إرجاع الكميات القديمة للمخزن وتسجيل حركة sale_update
            foreach ($invoice->items as $oldItem) {
                $product = Menu::with('recipes.inventoryItem')->find($oldItem->menu_id);
                if ($product) {
                    foreach ($product->recipes as $recipe) {
                        $returnedQty = $recipe->quantity_used * $oldItem->quantity;
                        $recipe->inventoryItem->increment('quantity', $returnedQty);

                        InventoryMovement::create([
                            'inventory_item_id' => $recipe->inventoryItem->id,
                            'type'              => 'sale_update',
                            'quantity'          => $returnedQty,
                            'balance_after'     => $recipe->inventoryItem->quantity,
                            'invoice_id'        => $invoice->id,
                            'user_id'           => Auth::id() ?? 1,
                        ]);
                    }
                }
            }

            $invoice->items()->delete();

            $totalInvoicePrice = 0;
            $compiledItems     = [];
            $movementsToLog    = [];

            // 2. تطبيق الأصناف الجديدة وخصم المخزن
            foreach ($request->items as $itemData) {
                $product  = Menu::with('recipes.inventoryItem')->findOrFail($itemData['menu_id']);
                $quantity = $itemData['quantity'];
                $itemPrice = $product->price;
                $itemTotal = $itemPrice * $quantity;

                $totalInvoicePrice += $itemTotal;

                foreach ($product->recipes as $recipe) {
                    $inventoryItem = $recipe->inventoryItem;
                    $neededQty     = $recipe->quantity_used * $quantity;

                    if ($inventoryItem->quantity < $neededQty) {
                        throw new \Exception("المخزن لا يكفي من [{$inventoryItem->name}] لتلبية التعديل الجديد.");
                    }
                    $inventoryItem->decrement('quantity', $neededQty);

                    $movementsToLog[] = [
                        'inventory_item_id' => $inventoryItem->id,
                        'type'              => 'sale',
                        'quantity'          => -$neededQty,
                        'balance_after'     => $inventoryItem->quantity,
                    ];
                }

                $compiledItems[] = [
                    'invoice_id' => $invoice->id,
                    'menu_id'    => $product->id,
                    'quantity'   => $quantity,
                    'item_price' => $itemPrice,
                    'total'      => $itemTotal,
                ];
            }

            $discount   = $request->input('discount', $invoice->discount);
            $finalTotal = $totalInvoicePrice - $discount;

            $invoice->update([
                'total'    => $finalTotal < 0 ? 0 : $finalTotal,
                'discount' => $discount,
                'profit'   => $totalInvoicePrice - $discount,
            ]);

            foreach ($compiledItems as $compiledItem) {
                InvoiceItem::create($compiledItem);
            }

            foreach ($movementsToLog as $movement) {
                $movement['invoice_id'] = $invoice->id;
                $movement['user_id']    = Auth::id() ?? 1;
                InventoryMovement::create($movement);
            }

            // 3. تسجيل audit log
            InvoiceTransaction::create([
                'invoice_id'  => $invoice->id,
                'action'      => 'update',
                'old_data'    => $oldDataSnapshot,
                'new_data'    => $invoice->load('items')->toArray(),
                'description' => "قام [{$user->name}] بتعديل الفاتورة رقم {$invoice->invoice_number}",
                'created_by'  => $user->id,
            ]);

            app(\App\Services\ShiftActionService::class)->logInvoiceUpdate(
                $invoice->fresh(['items.menu', 'client', 'creator']),
                ['old_data' => $oldDataSnapshot, 'new_data' => $invoice->load('items')->toArray()]
            );

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'تم تعديل الفاتورة بنجاح وتحديث حركة المخزن.',
                'redirect' => route('sales-invoices.show', $invoice->id),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error'   => 'حدث خطأ أثناء تعديل الفاتورة: ' . $e->getMessage(),
            ], 500);
        }
    }
}
