<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use App\Models\InventoryMovement;
use App\Models\InvoiceItem;
use App\Models\InvoiceTransaction;
use App\Services\ShiftActionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SalesInvoiceController extends Controller
{
    /**
     * التحقق من صلاحية تعديل الفاتورة
     * - المدير أو المشرف: حرية كاملة في أي وقت ولأي فاتورة
     * - الموظف/الكاشير: مقيد بفواتير ورديته الحالية المفتوحة فقط، مع التحقق من عدم استرجاع الفاتورة مسبقاً
     */
    public function canModifyInvoice(?User $user, Invoice $invoice): array
    {
        if (!$user) {
            return ['allowed' => false, 'message' => 'يجب تسجيل الدخول أولاً.'];
        }

        if ($invoice->isRefunded()) {
            return ['allowed' => false, 'message' => 'هذه الفاتورة تم استرجاعها بالفعل ولا يمكن تعديلها.'];
        }

        // المدير والمشرف لديهم صلاحية كاملة
        if ($user->isManager()) {
            return ['allowed' => true, 'message' => null];
        }

        // قيود الموظف
        $activeShift = $user->activeShift;
        if (!$activeShift) {
            return ['allowed' => false, 'message' => 'لا يمكنك تعديل الفاتورة لعدم وجود وردية نشطة ومفتوحة لك حالياً.'];
        }

        if ((int) $invoice->shift_id !== (int) $activeShift->id) {
            return ['allowed' => false, 'message' => 'غير مصرح لك بتعديل هذه الفاتورة. يسمح للموظف فقط بتعديل فواتير ورديته الحالية المفتوحة.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    /**
     * التحقق من صلاحية استرجاع/إلغاء الفاتورة (Refund)
     * - المدير أو المشرف: حرية كاملة
     * - الموظف/الكاشير: مقيد بفواتير ورديته الحالية المفتوحة فقط وبشرط ألا تكون مسترجعة سابقاً
     */
    public function canRefundInvoice(?User $user, Invoice $invoice): array
    {
        if (!$user) {
            return ['allowed' => false, 'message' => 'يجب تسجيل الدخول أولاً.'];
        }

        if ($invoice->isRefunded()) {
            return ['allowed' => false, 'message' => 'تم استرجاع هذه الفاتورة بالفعل سابقاً.'];
        }

        // المدير والمشرف لديهم صلاحية كاملة
        if ($user->isManager()) {
            return ['allowed' => true, 'message' => null];
        }

        // قيود الموظف
        $activeShift = $user->activeShift;
        if (!$activeShift) {
            return ['allowed' => false, 'message' => 'لا يمكنك استرجاع الفاتورة لعدم وجود وردية نشطة ومفتوحة لك حالياً.'];
        }

        if ((int) $invoice->shift_id !== (int) $activeShift->id) {
            return ['allowed' => false, 'message' => 'غير مصرح لك باسترجاع هذه الفاتورة؛ تخص وردية أخرى أو وردية مغلقة. يرجى مراجعة المشرف أو الإدارة.'];
        }

        return ['allowed' => true, 'message' => null];
    }

    /**
     * عرض قائمة الفواتير المسجلة مع الإحصائيات والفلاتر
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['creator', 'client', 'order.table', 'items.menu', 'refunder'])
            ->orderBy('created_at', 'desc');

        // البحث برقم الفاتورة أو الملاحظات أو اسم العميل
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhere('refund_reason', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // فلترة حالة الفاتورة (مدفوعة، مرتجعة، الكل)
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'refunded') {
                $query->where('status', 'refunded');
            } elseif ($request->status === 'paid') {
                $query->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'refunded');
                });
            }
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

        // استبعاد الفواتير المرتجعة من إجمالي المبيعات النشطة لحساب دقيق
        $activeInvoicesQuery = Invoice::where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'refunded');
        });

        $stats = [
            'total_sales'      => (clone $activeInvoicesQuery)->sum('total'),
            'invoices_count'   => (clone $activeInvoicesQuery)->count(),
            'cash_total'       => (clone $activeInvoicesQuery)->where('payment_method', 'cash')->sum('total'),
            'instapay_total'   => (clone $activeInvoicesQuery)->where('payment_method', 'InstaPay')->sum('total'),
            'card_total'       => (clone $activeInvoicesQuery)->where('payment_method', 'card')->sum('total'),
            'today_sales'      => (clone $activeInvoicesQuery)->whereDate('created_at', Carbon::today())->sum('total'),
            'today_count'      => (clone $activeInvoicesQuery)->whereDate('created_at', Carbon::today())->count(),
            'refunded_count'   => Invoice::where('status', 'refunded')->count(),
            'refunded_total'   => Invoice::where('status', 'refunded')->sum('total'),
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
            'shift',
            'refunder',
            'transactions.creator'
        ])->findOrFail($id);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'invoice_number' => $invoice->invoice_number,
                'status'         => $invoice->status,
                'is_refunded'    => $invoice->isRefunded(),
                'refund_reason'  => $invoice->refund_reason,
                'refunded_at'    => $invoice->refunded_at ? $invoice->refunded_at->format('Y-m-d h:i A') : null,
                'refunded_by'    => $invoice->refunder->name ?? null,
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

        $user = Auth::user();
        $canModify = $this->canModifyInvoice($user, $invoice);
        $canRefund = $this->canRefundInvoice($user, $invoice);

        return view('sales_invoices.show', compact('invoice', 'canModify', 'canRefund'));
    }

    /**
     * صفحة تعديل الفاتورة
     */
    public function edit($id)
    {
        $invoice = Invoice::with(['items.menu.category', 'creator', 'client', 'order.table', 'shift'])
            ->findOrFail($id);
        $user = Auth::user();

        $check = $this->canModifyInvoice($user, $invoice);
        if (!$check['allowed']) {
            return redirect()->route('sales-invoices.show', $invoice->id)
                ->with('error', $check['message']);
        }

        // استدعاء كل الأصناف المتاحة مرتبة بالفئة
        $menus = Menu::with('category')
            ->where('is_available', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        // تجهيز بيانات الأصناف الحالية بشكل نظيف للواجهة الأمامية
        $currentItems = $invoice->items->map(function ($item) {
            return [
                'menu_id'  => $item->menu_id,
                'name'     => $item->menu->name ?? 'صنف محذوف',
                'price'    => (float) $item->item_price,
                'quantity' => (int) $item->quantity,
            ];
        })->values();

        return view('sales_invoices.edit', compact('invoice', 'menus', 'currentItems'));
    }

    /**
     * تنفيذ تعديل الفاتورة – يُرجع JSON للـ AJAX
     */
    public function update(Request $request, $id)
    {
        $user      = Auth::user();
        $isManager = $user?->isManager();

        $request->validate([
            'items'            => 'required|array|min:1',
            'items.*.menu_id'  => 'required|exists:menu,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount'         => 'nullable|numeric|min:0',
            'reason'           => ($isManager ? 'nullable' : 'required') . '|string|min:3|max:500',
        ], [
            'reason.required'  => 'يجب كتابة سبب تعديل الفاتورة للرقابة وتوثيق الشيفت.',
            'reason.min'       => 'سبب التعديل يجب ألا يقل عن 3 أحرف.',
        ]);

        $invoice = Invoice::with('items')->findOrFail($id);

        $check = $this->canModifyInvoice($user, $invoice);
        if (!$check['allowed']) {
            return response()->json([
                'success' => false,
                'error'   => $check['message'],
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
                        if ($recipe->inventoryItem) {
                            $returnedQty = $recipe->quantity_used * $oldItem->quantity;
                            $recipe->inventoryItem->increment('quantity', $returnedQty);

                            InventoryMovement::create([
                                'inventory_item_id' => $recipe->inventoryItem->id,
                                'type'              => 'sale_update',
                                'quantity'          => $returnedQty,
                                'balance_after'     => $recipe->inventoryItem->quantity,
                                'invoice_id'        => $invoice->id,
                                'user_id'           => $user->id,
                            ]);
                        }
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
                    if (!$inventoryItem) {
                        continue;
                    }

                    $neededQty = $recipe->quantity_used * $quantity;

                    if ($inventoryItem->quantity < $neededQty) {
                        throw new \Exception("المخزن لا يكفي من [{$inventoryItem->name}] لتلبية التعديل الجديد (المتوفر: {$inventoryItem->quantity}).");
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
                $movement['user_id']    = $user->id;
                InventoryMovement::create($movement);
            }

            // 3. تسجيل audit log
            $roleLabel = $user->isManager() ? 'إدارة/مشرف' : 'موظف';
            $reasonNote = $request->filled('reason') ? " | سبب التعديل: {$request->reason}" : '';

            try {
                InvoiceTransaction::create([
                    'invoice_id'  => $invoice->id,
                    'action'      => 'update',
                    'old_data'    => $oldDataSnapshot,
                    'new_data'    => $invoice->load('items')->toArray(),
                    'description' => "قام [{$user->name}] ({$roleLabel}) بتعديل الفاتورة رقم {$invoice->invoice_number}{$reasonNote}",
                    'created_by'  => $user->id,
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("InvoiceTransaction update error: " . $e->getMessage());
            }

            app(ShiftActionService::class)->logInvoiceUpdate(
                $invoice->fresh(['items.menu', 'client', 'creator']),
                [
                    'old_total' => (float) $oldDataSnapshot['total'],
                    'new_total' => (float) $invoice->total,
                    'diff'      => (float) ($invoice->total - $oldDataSnapshot['total']),
                    'reason'    => $request->reason,
                ],
                $user
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

    /**
     * استرجاع الفاتورة بالكامل (Refund)
     */
    public function refund(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ], [
            'reason.required' => 'يجب توضيح سبب استرجاع الفاتورة.',
            'reason.min'      => 'سبب الاسترجاع يجب ألا يقل عن 3 أحرف.',
        ]);

        $invoice = Invoice::with(['items.menu.recipes.inventoryItem', 'shift'])->findOrFail($id);
        $user    = Auth::user();

        $check = $this->canRefundInvoice($user, $invoice);
        if (!$check['allowed']) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $check['message']], 403);
            }
            return redirect()->back()->with('error', $check['message']);
        }

        $oldDataSnapshot = $invoice->toArray();

        DB::beginTransaction();
        try {
            // 1. إعادة المواد الخام للمخزن وتسجيل حركة sale_cancel
            foreach ($invoice->items as $item) {
                $product = Menu::with('recipes.inventoryItem')->find($item->menu_id);
                if ($product) {
                    foreach ($product->recipes as $recipe) {
                        if ($recipe->inventoryItem) {
                            $returnedQty = $recipe->quantity_used * $item->quantity;
                            $recipe->inventoryItem->increment('quantity', $returnedQty);

                            InventoryMovement::create([
                                'inventory_item_id' => $recipe->inventoryItem->id,
                                'type'              => 'sale_cancel',
                                'quantity'          => $returnedQty,
                                'balance_after'     => $recipe->inventoryItem->quantity,
                                'invoice_id'        => $invoice->id,
                                'user_id'           => $user->id,
                            ]);
                        }
                    }
                }
            }

            // 2. تحديث حالة الفاتورة لتصبح مرتجعة
            $invoice->update([
                'status'        => 'refunded',
                'refunded_at'   => now(),
                'refund_reason' => $request->reason,
                'refunded_by'   => $user->id,
            ]);

            // 3. إلغاء الطلب المرتبط إن وجد
            if ($invoice->order_id) {
                try {
                    Order::where('id', $invoice->order_id)->update([
                        'status'         => 'cancelled',
                        'payment_status' => 'refunded',
                    ]);
                } catch (\Throwable $e) {
                    // حماية إضافية في حال كان عمود payment_status في قاعدة البيانات لم يحدث بعد بالميجراشن
                    Order::where('id', $invoice->order_id)->update([
                        'status' => 'cancelled',
                    ]);
                }
            }

            // 4. تسجيل في سجل الرقابة InvoiceTransaction
            $roleLabel = $user->isManager() ? 'إدارة/مشرف' : 'موظف';
            $refundDesc = "قام [{$user->name}] ({$roleLabel}) باسترجاع الفاتورة رقم {$invoice->invoice_number}. السبب: {$request->reason}";
            try {
                InvoiceTransaction::create([
                    'invoice_id'  => $invoice->id,
                    'action'      => 'refund',
                    'old_data'    => $oldDataSnapshot,
                    'new_data'    => $invoice->fresh()->toArray(),
                    'description' => $refundDesc,
                    'created_by'  => $user->id,
                ]);
            } catch (\Throwable $e) {
                // توافقية كاملة في حال كان عمود action لا يزال enum القديم قبل تشغيل الميجراشن
                InvoiceTransaction::create([
                    'invoice_id'  => $invoice->id,
                    'action'      => 'update',
                    'old_data'    => $oldDataSnapshot,
                    'new_data'    => $invoice->fresh()->toArray(),
                    'description' => "[استرجاع] " . $refundDesc,
                    'created_by'  => $user->id,
                ]);
            }

            // 5. تسجيل حركة استرجاع في الوردية
            app(ShiftActionService::class)->logInvoiceRefund($invoice, $request->reason, $user);

            DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success'  => true,
                    'message'  => 'تم استرجاع الفاتورة بنجاح، وإعادة المواد الخام للمخزن، وتسجيل العملية.',
                    'redirect' => route('sales-invoices.show', $invoice->id),
                ]);
            }

            return redirect()->route('sales-invoices.show', $invoice->id)
                ->with('success', 'تم استرجاع الفاتورة بنجاح وإعادة المواد للمخزن.');
        } catch (\Throwable $e) {
            DB::rollBack();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'حدث خطأ أثناء استرجاع الفاتورة: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ: ' . $e->getMessage());
        }
    }
}
