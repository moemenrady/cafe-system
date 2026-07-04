<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Menu;
use App\Models\InventoryItem;
use App\Models\InventoryMovement; // 🌟 استدعاء موديل الحركات الجديد
use App\Models\InvoiceItem;
use App\Models\InvoiceTransaction;
use App\Helpers\InvoiceNumberHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    /**
     * شاشة الرقابة - عرض حركات وتعديلات المشرفين
     */
    public function movements()
    {
        $movements = InvoiceTransaction::with(['creator', 'invoice', 'invoice.items.menu'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Calculate statistics
        $stats = [
            'total' => InvoiceTransaction::count(),
            'updates' => InvoiceTransaction::where('action', 'update')->count(),
            'creates' => InvoiceTransaction::where('action', 'create')->count(),
            'deletes' => InvoiceTransaction::where('action', 'delete')->count(),
            'today' => InvoiceTransaction::whereDate('created_at', today())->count(),
        ];

        return view('admin.movements.index', compact('movements', 'stats'));
    }

    /**
     * Get detailed invoice data for modal
     */
    public function getInvoiceDetails($id)
    {
        try {
            $invoice = Invoice::with(['items.menu', 'creator'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'created_at' => $invoice->created_at->format('Y-m-d - h:i A'),
                    'total' => number_format($invoice->total, 2),
                    'discount' => number_format($invoice->discount ?? 0, 2),
                    'subtotal' => number_format(($invoice->total + ($invoice->discount ?? 0)), 2),
                    'payment_method' => $this->getPaymentMethodLabel($invoice->payment_method),
                    'creator' => $invoice->creator->name ?? 'غير معروف',
                    'note' => $invoice->note ?? 'لا يوجد',
                    'items' => $invoice->items->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->menu->name ?? 'منتج غير معروف',
                            'quantity' => $item->quantity,
                            'price' => number_format($item->item_price, 2),
                            'total' => number_format($item->total, 2)
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحميل البيانات'
            ], 500);
        }
    }

    private function getPaymentMethodLabel($method)
    {
        $methods = [
            'cash' => '💰 كاش',
            'InstaPay' => '📱 InstaPay',
            'card' => '💳 بطاقة'
        ];
        return $methods[$method] ?? $method;
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menu,id',
            'items.*.quantity' => 'required|integer|min:1',
            'discount' => 'nullable|numeric|min:0',
        ]);

        $invoice = Invoice::with('items')->findOrFail($id);
        $user = auth()->user();
        $oldDataSnapshot = $invoice->toArray();

        DB::beginTransaction();
        try {
            // 1. إرجاع الكميات القديمة للمخزن وتسجيل حركة الموجب (sale_update)
            foreach ($invoice->items as $oldItem) {
                $product = Menu::with('recipes.inventoryItem')->find($oldItem->menu_id);
                if ($product) {
                    foreach ($product->recipes as $recipe) {
                        $returnedQty = $recipe->quantity_used * $oldItem->quantity;
                        $recipe->inventoryItem->increment('quantity', $returnedQty);

                        // 🌟 تسجيل حركة إرجاع المخزن بسبب تعديل الفاتورة
                        InventoryMovement::create([
                            'inventory_item_id' => $recipe->inventoryItem->id,
                            'type' => 'sale_update',
                            'quantity' => $returnedQty, // بالموجب لأن الرصيد زاد
                            'balance_after' => $recipe->inventoryItem->quantity,
                            'invoice_id' => $invoice->id,
                            'user_id' => Auth::id() ?? 1
                        ]);
                    }
                }
            }

            $invoice->items()->delete();

            $totalInvoicePrice = 0;
            $compiledItems = [];
            $movementsToLog = [];

            // 2. فحص وتطبيق الخصم الجديد وتسجيل حركة السالب للمخزن
            foreach ($request->items as $itemData) {
                $product = Menu::with('recipes.inventoryItem')->findOrFail($itemData['menu_id']);
                $quantity = $itemData['quantity'];
                $itemPrice = $product->price;
                $itemTotal = $itemPrice * $quantity;

                $totalInvoicePrice += $itemTotal;

                foreach ($product->recipes as $recipe) {
                    $inventoryItem = $recipe->inventoryItem;
                    $neededQty = $recipe->quantity_used * $quantity;

                    if ($inventoryItem->quantity < $neededQty) {
                        throw new \Exception("المخزن لا يكفي من [{$inventoryItem->name}] لتلبية التعديل الجديد.");
                    }
                    $inventoryItem->decrement('quantity', $neededQty);

                    // 🌟 تجهيز بيانات الحركة الجديدة بالسالب
                    $movementsToLog[] = [
                        'inventory_item_id' => $inventoryItem->id,
                        'type' => 'sale',
                        'quantity' => -$neededQty, // بالسالب لأن المخزون نقص
                        'balance_after' => $inventoryItem->quantity,
                    ];
                }

                $compiledItems[] = [
                    'invoice_id' => $invoice->id,
                    'menu_id' => $product->id,
                    'quantity' => $quantity,
                    'item_price' => $itemPrice,
                    'total' => $itemTotal,
                ];
            }

            $discount = $request->input('discount', $invoice->discount);
            $finalTotal = $totalInvoicePrice - $discount;

            $invoice->update([
                'total' => $finalTotal < 0 ? 0 : $finalTotal,
                'discount' => $discount,
                'profit' => $totalInvoicePrice - $discount,
            ]);

            foreach ($compiledItems as $compiledItem) {
                InvoiceItem::create($compiledItem);
            }

            // 🌟 حفظ الحركات الجديدة في قاعدة البيانات بعد التأكد من تعديل الفاتورة
            foreach ($movementsToLog as $movement) {
                $movement['invoice_id'] = $invoice->id;
                $movement['user_id'] = Auth::id() ?? 1;
                InventoryMovement::create($movement);
            }

            if ($user && $user->role === 'supervisor') {
                InvoiceTransaction::create([
                    'invoice_id' => $invoice->id,
                    'action' => 'update',
                    'old_data' => $oldDataSnapshot,
                    'new_data' => $invoice->load('items')->toArray(),
                    'description' => "قام المشرف [{$user->name}] بتعديل الفاتورة رقم {$invoice->invoice_number}",
                    'created_by' => $user->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تعديل الفاتورة بنجاح وتحديث حركة المخزن.',
                'data' => $invoice
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage() . ' | الملف: ' . $e->getFile() . ' | السطر: ' . $e->getLine()
            ], 500);
        }
    }

    public function show($id)
    {
        $invoice = Invoice::with(['items.menu', 'creator'])->findOrFail($id);
        return response()->json([
            'invoice_number' => $invoice->invoice_number,
            'created_at' => $invoice->created_at->format('Y-m-d - h:i A'),
            'total' => number_format($invoice->total, 2),
            'payment_method' => $invoice->payment_method == 'cash' ? 'كاش' : $invoice->payment_method,
            'creator' => $invoice->creator,
            'items' => $invoice->items
        ]);
    }

    public function destroy($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $user = auth()->user();
        $oldDataSnapshot = $invoice->toArray();

        DB::beginTransaction();
        try {
            // 1. إرجاع المكونات والخامات القديمة للمخزن وتسجيل حركة موجب (sale_cancel)
            foreach ($invoice->items as $item) {
                $product = Menu::with('recipes.inventoryItem')->find($item->menu_id);
                if ($product) {
                    foreach ($product->recipes as $recipe) {
                        $returnedQty = $recipe->quantity_used * $item->quantity;
                        $recipe->inventoryItem->increment('quantity', $returnedQty);

                        // 🌟 تسجيل حركة إعادة المخزن بسبب إلغاء وحذف الفاتورة
                        InventoryMovement::create([
                            'inventory_item_id' => $recipe->inventoryItem->id,
                            'type' => 'sale_cancel', // إلغاء عملية بيع
                            'quantity' => $returnedQty, // بالموجب لأن المخزن زاد
                            'balance_after' => $recipe->inventoryItem->quantity,
                            'invoice_id' => $invoice->id,
                            'user_id' => Auth::id() ?? 1
                        ]);
                    }
                }
            }

            // 2. تسجيل حركة الحذف في شاشة الرقابة (Audit Log) للمشرفين
            if ($user && $user->role === 'supervisor') {
                InvoiceTransaction::create([
                    'invoice_id' => $invoice->id,
                    'action' => 'delete',
                    'old_data' => $oldDataSnapshot,
                    'new_data' => null,
                    'description' => "قام المشرف [{$user->name}] بحذف وإلغاء الفاتورة رقم {$invoice->invoice_number} بالكامل وإعادة موادها للمخزن.",
                    'created_by' => $user->id,
                ]);
            }

            // 3. مسح أصناف الفاتورة ثم الفاتورة نفسها
            $invoice->items()->delete();
            $invoice->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف وإلغاء الفاتورة بنجاح، وإعادة المواد الخام للمخزن.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'حدث خطأ أثناء محاولة الحذف: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cash,InstaPay,card',
            'items' => 'required|array|min:1',
            'items.*.menu_id' => 'required|exists:menu,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $totalInvoicePrice = 0;
            $compiledItems = [];
            $movementsToLog = []; // مصفوفة مؤقتة لجمع الحركات

            foreach ($request->items as $itemData) {
                $product = Menu::with('recipes.inventoryItem')->findOrFail($itemData['menu_id']);
                $quantity = $itemData['quantity'];
                $itemPrice = $product->price;
                $itemTotal = $itemPrice * $quantity;
                $totalInvoicePrice += $itemTotal;

                foreach ($product->recipes as $recipe) {
                    $inventoryItem = $recipe->inventoryItem;
                    $neededQty = $recipe->quantity_used * $quantity;
                    if ($inventoryItem->quantity < $neededQty) {
                        throw new \Exception("لا توجد كمية كافية من [{$inventoryItem->name}].");
                    }
                    $inventoryItem->decrement('quantity', $neededQty);

                    // 🌟 جمع بيانات الحركة الحالية وتخزين رصيد المخزن الفوري بعد الحذف
                    $movementsToLog[] = [
                        'inventory_item_id' => $inventoryItem->id,
                        'type' => 'sale',
                        'quantity' => -$neededQty, // القيمة سالبة لأنها استهلاك
                        'balance_after' => $inventoryItem->quantity
                    ];
                }

                $compiledItems[] = [
                    'menu_id' => $product->id,
                    'quantity' => $quantity,
                    'item_price' => $itemPrice,
                    'total' => $itemTotal,
                ];
            }

            $discount = $request->input('discount', 0);
            $finalTotal = max(0, $totalInvoicePrice - $discount);

            $invoice = Invoice::create([
                'invoice_number' => InvoiceNumberHelper::generate(),
                'total' => $finalTotal,
                'discount' => $discount,
                'client_id' => $request->client_id,
                'profit' => $totalInvoicePrice - $discount,
                'payment_method' => $request->payment_method,
                'created_by' => Auth::id() ?? 1,
            ]);

            foreach ($compiledItems as $compiledItem) {
                $compiledItem['invoice_id'] = $invoice->id;
                InvoiceItem::create($compiledItem);
            }

            // 🌟 حفظ حركات المخزن وربطها بمعرف الفاتورة الجديد والمستخدم
            foreach ($movementsToLog as $movement) {
                $movement['invoice_id'] = $invoice->id;
                $movement['user_id'] = Auth::id() ?? 1;
                InventoryMovement::create($movement);
            }

            // InvoiceTransaction::create([
            //     'invoice_id' => $invoice->id,
            //     'action' => 'create',
            //     'old_data' => null,
            //     'new_data' => $invoice->load('items')->toArray(),
            //     'description' => "تم إنشاء الفاتورة بواسطة الموظف " . ($invoice->created_by),
            //     'created_by' => $invoice->created_by,
            // ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'تم الحفظ بنجاح وتسجيل حركة المخزون.', 'data' => $invoice], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
