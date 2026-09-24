<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceController extends Controller
{
    /**
     * قائمة فواتير الشراء مقسمة ذكياً حسب التاريخ مع البحث والفلاتر
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoice::with(['items.inventoryItem', 'user']);

        // 1. البحث بالنص (رقم الفاتورة، اسم المورد، أو اسم صنف تم شراؤه)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                  ->orWhere('supplier_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('item_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // 2. الفلترة بالتاريخ
        $dateFilter = $request->input('date_filter', 'all');
        $today = Carbon::today();

        if ($dateFilter === 'today') {
            $query->whereDate('invoice_date', $today);
        } elseif ($dateFilter === 'yesterday') {
            $query->whereDate('invoice_date', Carbon::yesterday());
        } elseif ($dateFilter === 'week') {
            $query->whereBetween('invoice_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($dateFilter === 'month') {
            $query->whereMonth('invoice_date', Carbon::now()->month)
                  ->whereYear('invoice_date', Carbon::now()->year);
        } elseif ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('invoice_date', [$request->from_date, $request->to_date]);
        }

        // 3. الفلترة بحالة السداد
        if ($request->filled('payment_status') && $request->payment_status !== 'all') {
            $query->where('payment_status', $request->payment_status);
        }

        // 4. الفلترة بطريقة الدفع
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // جلب الفواتير مرتبة بأحدث تاريخ وأحدث رقم
        $invoices = $query->orderByDesc('invoice_date')->orderByDesc('id')->get();

        // التقسيم الذكي حسب التاريخ (Grouped by Date)
        $groupedInvoices = $invoices->groupBy(function ($inv) {
            return $inv->invoice_date ? $inv->invoice_date->format('Y-m-d') : $inv->created_at->format('Y-m-d');
        });

        // 5. إحصائيات سريعة للبطاقات العلوية
        $allPurchases = PurchaseInvoice::all();
        $totalPurchases = (float) $allPurchases->sum('net_amount');
        $todayPurchases = (float) $allPurchases->where('invoice_date', $today)->sum('net_amount');
        $totalUnpaid = (float) $allPurchases->sum('remaining_amount');
        $invoicesCount = $allPurchases->count();

        return view('purchase_invoices.index', compact(
            'groupedInvoices',
            'invoices',
            'totalPurchases',
            'todayPurchases',
            'totalUnpaid',
            'invoicesCount',
            'dateFilter'
        ));
    }

    /**
     * البحث التلقائي السريع في الأصناف عبر AJAX
     */
    public function searchInventoryItems(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        if (empty($q)) {
            $items = InventoryItem::take(15)->get();
        } else {
            $items = InventoryItem::where('name', 'LIKE', "%{$q}%")
                ->orWhere('code', 'LIKE', "%{$q}%")
                ->take(20)
                ->get();
        }

        return response()->json($items->map(function ($item) {
            return [
                'id'            => $item->id,
                'name'          => $item->name,
                'code'          => $item->code ?: ('ITM-' . str_pad((string) $item->id, 3, '0', STR_PAD_LEFT)),
                'unit'          => $item->unit ?: 'قطعة',
                'category'      => $item->category ?: 'عام',
                'unit_price'    => (float) $item->unit_price,
                'quantity'      => (float) $item->quantity,
                'reorder_level' => (float) $item->reorder_level,
            ];
        }));
    }

    /**
     * فورم إنشاء فاتورة شراء جديدة
     */
    public function create()
    {
        $nextInvoiceNumber = PurchaseInvoice::generateNextInvoiceNumber();
        $units = ['كجم', 'جرام', 'لتر', 'مل', 'علبة', 'كرتونة', 'باكت', 'شيكارة', 'قطعة', 'زجاجة'];
        $categories = InventoryItem::pluck('category')->filter()->unique()->values()->all();
        if (empty($categories)) {
            $categories = ['خامات المشروبات', 'الألبان ومشتقاتها', 'البن والقهوة', 'المعجنات والحلويات', 'أدوات ومستهلكات'];
        }

        $allInventoryItems = InventoryItem::select('id', 'name', 'code', 'unit', 'unit_price', 'quantity', 'category', 'reorder_level')
            ->orderBy('name')
            ->get();

        return view('purchase_invoices.create', compact(
            'nextInvoiceNumber',
            'units',
            'categories',
            'allInventoryItems'
        ));
    }

    /**
     * حفظ فاتورة الشراء وتحديث أرصدة المخزن وحركات التوريد
     */
    public function store(Request $request)
    {
        $request->validate([
            'invoice_number' => 'nullable|string|max:100|unique:purchase_invoices,invoice_number',
            'supplier_name'  => 'required|string|max:255',
            'invoice_date'   => 'required|date',
            'payment_method' => 'required|string',
            'payment_status' => 'required|string',
            'paid_amount'    => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'tax'            => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.name'          => 'required|string|max:255',
            'items.*.unit'          => 'required|string|max:50',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.unit_price'    => 'required|numeric|min:0',
            'items.*.inventory_item_id' => 'nullable|integer',
            'items.*.category'      => 'nullable|string',
            'items.*.notes'         => 'nullable|string',
        ], [
            'supplier_name.required' => 'يرجى إدخال اسم المورد.',
            'invoice_date.required'  => 'يرجى تحديد تاريخ الفاتورة.',
            'items.required'         => 'يجب إضافة صنف واحد على الأقل في الفاتورة.',
            'items.*.name.required'  => 'يرجى تحديد اسم كل صنف.',
            'items.*.quantity.min'   => 'كمية الصنف يجب أن تكون أكبر من صفر.',
        ]);

        return DB::transaction(function () use ($request) {
            // 1. حساب إجماليات الأصناف
            $totalAmount = 0.0;
            foreach ($request->items as $itemData) {
                $qty = (float) $itemData['quantity'];
                $price = (float) $itemData['unit_price'];
                $totalAmount += round($qty * $price, 2);
            }

            $discount = (float) ($request->discount ?? 0);
            $tax = (float) ($request->tax ?? 0);
            $netAmount = max(0, round($totalAmount - $discount + $tax, 2));

            $paymentStatus = $request->payment_status;
            if ($paymentStatus === 'paid') {
                $paidAmount = $netAmount;
                $remainingAmount = 0.0;
            } elseif ($paymentStatus === 'unpaid') {
                $paidAmount = 0.0;
                $remainingAmount = $netAmount;
            } else { // partial
                $paidAmount = min($netAmount, (float) ($request->paid_amount ?? 0));
                $remainingAmount = max(0, round($netAmount - $paidAmount, 2));
            }

            $invoiceNumber = $request->invoice_number ?: PurchaseInvoice::generateNextInvoiceNumber();

            // 2. إنشاء الفاتورة
            $invoice = PurchaseInvoice::create([
                'invoice_number'   => $invoiceNumber,
                'supplier_name'    => $request->supplier_name,
                'invoice_date'     => $request->invoice_date,
                'total_amount'     => $totalAmount,
                'discount'         => $discount,
                'tax'              => $tax,
                'net_amount'       => $netAmount,
                'paid_amount'      => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_method'   => $request->payment_method,
                'payment_status'   => $paymentStatus,
                'status'           => 'completed',
                'notes'            => $request->notes,
                'user_id'          => auth()->id(),
            ]);

            // 3. معالجة الأصناف وإضافتها للمخزن
            foreach ($request->items as $itemData) {
                $inventoryItemId = $itemData['inventory_item_id'] ?? null;
                $itemName = trim($itemData['name']);
                $quantity = (float) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $unit = trim($itemData['unit'] ?? 'قطعة');
                $category = trim($itemData['category'] ?? 'عام');

                // البحث عن الصنف في المخزن إذا لم يتم تمرير ID
                $inventoryItem = null;
                if ($inventoryItemId) {
                    $inventoryItem = InventoryItem::find($inventoryItemId);
                }
                if (!$inventoryItem) {
                    $inventoryItem = InventoryItem::where('name', $itemName)->first();
                }

                // إذا كان صنفاً جديداً تماماً، ننشئه في المخزن فوراً
                if (!$inventoryItem) {
                    $inventoryItem = InventoryItem::create([
                        'name'          => $itemName,
                        'unit'          => $unit,
                        'category'      => $category ?: 'عام',
                        'quantity'      => $quantity, // رصيد افتتاحي بالشروة
                        'unit_price'    => $unitPrice,
                        'reorder_level' => (float) ($itemData['reorder_level'] ?? 5),
                    ]);
                } else {
                    // زيادة الرصيد وتحديث سعر التكلفة
                    $inventoryItem->increment('quantity', $quantity);
                    $inventoryItem->update([
                        'unit_price' => $unitPrice,
                        'unit'       => $unit,
                    ]);
                }

                // إضافة الصنف لسجل الفاتورة
                $subtotal = round($quantity * $unitPrice, 2);
                $invoice->items()->create([
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name'         => $itemName,
                    'unit'              => $unit,
                    'quantity'          => $quantity,
                    'unit_price'        => $unitPrice,
                    'subtotal'          => $subtotal,
                    'notes'             => $itemData['notes'] ?? null,
                ]);

                // تسجيل حركة المخزون
                InventoryMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type'              => 'restock',
                    'quantity'          => $quantity,
                    'balance_after'     => $inventoryItem->fresh()->quantity,
                    'user_id'           => auth()->id(),
                ]);
            }

            return redirect()->route('purchase-invoices.show', $invoice->id)
                ->with('success', "تم إنشاء فاتورة الشراء رقم {$invoice->invoice_number} بنجاح وإضافة الكميات للمخزن.");
        });
    }

    /**
     * عرض تفاصيل فاتورة الشراء (شاشة الطباعة والاستعراض)
     */
    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['items.inventoryItem', 'user']);

        return view('purchase_invoices.show', compact('purchaseInvoice'));
    }

    /**
     * فورم تعديل فاتورة الشراء
     */
    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['items.inventoryItem']);

        $units = ['كجم', 'جرام', 'لتر', 'مل', 'علبة', 'كرتونة', 'باكت', 'شيكارة', 'قطعة', 'زجاجة'];
        $categories = InventoryItem::pluck('category')->filter()->unique()->values()->all();
        if (empty($categories)) {
            $categories = ['خامات المشروبات', 'الألبان ومشتقاتها', 'البن والقهوة', 'المعجنات والحلويات', 'أدوات ومستهلكات'];
        }

        $allInventoryItems = InventoryItem::select('id', 'name', 'code', 'unit', 'unit_price', 'quantity', 'category', 'reorder_level')
            ->orderBy('name')
            ->get();

        $initialItems = $purchaseInvoice->items->map(function ($item) {
            return [
                'id'                => $item->id,
                'inventory_item_id' => $item->inventory_item_id,
                'name'              => $item->item_name,
                'unit'              => $item->unit,
                'category'          => $item->inventoryItem->category ?? 'عام',
                'quantity'          => (float) $item->quantity,
                'unit_price'        => (float) $item->unit_price,
                'notes'             => $item->notes ?? '',
            ];
        });

        return view('purchase_invoices.edit', compact(
            'purchaseInvoice',
            'units',
            'categories',
            'allInventoryItems',
            'initialItems'
        ));
    }

    /**
     * تحديث فاتورة الشراء وتعديل فروق المخزون
     */
    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $request->validate([
            'supplier_name'  => 'required|string|max:255',
            'invoice_date'   => 'required|date',
            'payment_method' => 'required|string',
            'payment_status' => 'required|string',
            'paid_amount'    => 'nullable|numeric|min:0',
            'discount'       => 'nullable|numeric|min:0',
            'tax'            => 'nullable|numeric|min:0',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.name'          => 'required|string|max:255',
            'items.*.unit'          => 'required|string|max:50',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.unit_price'    => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($request, $purchaseInvoice) {
            // 1. التراجع عن الكميات القديمة في المخزن
            foreach ($purchaseInvoice->items as $oldItem) {
                if ($oldItem->inventoryItem) {
                    $oldItem->inventoryItem->decrement('quantity', $oldItem->quantity);
                }
            }

            // حذف الأصناف القديمة
            $purchaseInvoice->items()->delete();

            // 2. حساب الإجماليات الجديدة
            $totalAmount = 0.0;
            foreach ($request->items as $itemData) {
                $qty = (float) $itemData['quantity'];
                $price = (float) $itemData['unit_price'];
                $totalAmount += round($qty * $price, 2);
            }

            $discount = (float) ($request->discount ?? 0);
            $tax = (float) ($request->tax ?? 0);
            $netAmount = max(0, round($totalAmount - $discount + $tax, 2));

            $paymentStatus = $request->payment_status;
            if ($paymentStatus === 'paid') {
                $paidAmount = $netAmount;
                $remainingAmount = 0.0;
            } elseif ($paymentStatus === 'unpaid') {
                $paidAmount = 0.0;
                $remainingAmount = $netAmount;
            } else { // partial
                $paidAmount = min($netAmount, (float) ($request->paid_amount ?? 0));
                $remainingAmount = max(0, round($netAmount - $paidAmount, 2));
            }

            // 3. تحديث الفاتورة
            $purchaseInvoice->update([
                'supplier_name'    => $request->supplier_name,
                'invoice_date'     => $request->invoice_date,
                'total_amount'     => $totalAmount,
                'discount'         => $discount,
                'tax'              => $tax,
                'net_amount'       => $netAmount,
                'paid_amount'      => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'payment_method'   => $request->payment_method,
                'payment_status'   => $paymentStatus,
                'notes'            => $request->notes,
            ]);

            // 4. إضافة الأصناف الجديدة وزيادة الأرصدة
            foreach ($request->items as $itemData) {
                $inventoryItemId = $itemData['inventory_item_id'] ?? null;
                $itemName = trim($itemData['name']);
                $quantity = (float) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $unit = trim($itemData['unit'] ?? 'قطعة');

                $inventoryItem = null;
                if ($inventoryItemId) {
                    $inventoryItem = InventoryItem::find($inventoryItemId);
                }
                if (!$inventoryItem) {
                    $inventoryItem = InventoryItem::where('name', $itemName)->first();
                }

                if (!$inventoryItem) {
                    $inventoryItem = InventoryItem::create([
                        'name'          => $itemName,
                        'unit'          => $unit,
                        'category'      => $itemData['category'] ?? 'عام',
                        'quantity'      => $quantity,
                        'unit_price'    => $unitPrice,
                        'reorder_level' => 5,
                    ]);
                } else {
                    $inventoryItem->increment('quantity', $quantity);
                    $inventoryItem->update(['unit_price' => $unitPrice, 'unit' => $unit]);
                }

                $subtotal = round($quantity * $unitPrice, 2);
                $purchaseInvoice->items()->create([
                    'inventory_item_id' => $inventoryItem->id,
                    'item_name'         => $itemName,
                    'unit'              => $unit,
                    'quantity'          => $quantity,
                    'unit_price'        => $unitPrice,
                    'subtotal'          => $subtotal,
                    'notes'             => $itemData['notes'] ?? null,
                ]);

                InventoryMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    'type'              => 'restock',
                    'quantity'          => $quantity,
                    'balance_after'     => $inventoryItem->fresh()->quantity,
                    'user_id'           => auth()->id(),
                ]);
            }

            return redirect()->route('purchase-invoices.show', $purchaseInvoice->id)
                ->with('success', 'تم تعديل فاتورة الشراء وتحديث الأرصدة بنجاح.');
        });
    }

    /**
     * حذف فاتورة الشراء والتراجع عن كميات المخزن
     */
    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        return DB::transaction(function () use ($purchaseInvoice) {
            // التراجع عن الكميات التي دخلت المخزن
            foreach ($purchaseInvoice->items as $item) {
                if ($item->inventoryItem) {
                    $item->inventoryItem->decrement('quantity', $item->quantity);

                    InventoryMovement::create([
                        'inventory_item_id' => $item->inventoryItem->id,
                        'type'              => 'waste',
                        'quantity'          => -$item->quantity,
                        'balance_after'     => $item->inventoryItem->fresh()->quantity,
                        'user_id'           => auth()->id(),
                    ]);
                }
            }

            $invoiceNum = $purchaseInvoice->invoice_number;
            $purchaseInvoice->delete();

            return redirect()->route('purchase-invoices.index')
                ->with('success', "تم حذف فاتورة الشراء رقم {$invoiceNum} واسترجاع أرصدة المخزون بنجاح.");
        });
    }
}
