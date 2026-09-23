<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryMovement; // 🌟 استدعاء موديل الحركات الجديد
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index()
    {
        $items = InventoryItem::latest()->get();
        return view('inventory.index', compact('items'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    /**
     * عند إضافة خامة جديدة تماماً
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'reorder_level' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $item = InventoryItem::create($validated);

            // 🌟 تسجيل أول حركة للمادة الخام (رصيد افتتاحي / توريد أول مرة)
            if ($item->quantity > 0) {
                InventoryMovement::create([
                    'inventory_item_id' => $item->id,
                    'type' => 'restock', // توريد جديد
                    'quantity' => $item->quantity, // كمية موجبة
                    'balance_after' => $item->quantity,
                    'user_id' => Auth::id() ?? 1,
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.index')->with('success', 'تم إضافة المادة الخام بنجاح وتسجيل الرصيد الافتتاحي.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء الحفظ.');
        }
    }

    public function edit($id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);
        return view('inventory.edit', compact('inventoryItem'));
    }
    public function adjust(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:restock,waste',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $item = InventoryItem::findOrFail($id);
        $amount = $request->amount;

        DB::beginTransaction();
        try {
            if ($request->type === 'restock') {
                // توريد زيادة للمخزن
                $item->increment('quantity', $amount);
                $newQty = $item->quantity;
                $logQty = $amount; // قيمة موجبة
                $message = "تم تسجيل توريد ({$amount} {$item->unit}) بنجاح لصنف [{$item->name}].";
            } else {
                // تسجيل تالف / فساد (Waste)
                if ($item->quantity < $amount) {
                    return redirect()->back()->with('error', "الكمية المراد إهلاكها أكبر من المتاح في المخزن حالياً ({$item->quantity})!");
                }
                $item->decrement('quantity', $amount);
                $newQty = $item->quantity;
                $logQty = -$amount; // قيمة سالبة لأنها عجز/هالك
                $message = "تم تسجيل إهلاك وتلف ({$amount} {$item->unit}) لصنف [{$item->name}].";
            }

            // تسجيل الحركة في السجل الهجين للرقابة
            InventoryMovement::create([
                'inventory_item_id' => $item->id,
                'type' => $request->type,
                'quantity' => $logQty,
                'balance_after' => $newQty,
                'user_id' => auth()->id() ?? 1,
            ]);

            DB::commit();
            return redirect()->route('inventory.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ غير متوقع أثناء تحديث المخزون.');
        }
    }

    /**
     * 🌟 دالة التعديل الذكية: تحسب الفرق وتسجله في الحركات
     */
    public function update(Request $request, $id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name,' . $id,
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'reorder_level' => 'required|numeric|min:0',
            'unit_price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 1. الاحتفاظ بالكمية القديمة قبل التحديث
            $oldQuantity = $inventoryItem->quantity;

            // 2. تحديث البيانات بالقيم الجديدة
            $inventoryItem->update($validated);
            $newQuantity = $inventoryItem->quantity;

            // 3. حساب الفرق (الكمية الجديدة - الكمية القديمة)
            $difference = $newQuantity - $oldQuantity;

            // إذا تغيرت الكمية فعلياً، نسجل الحركة
            if ($difference != 0) {
                InventoryMovement::create([
                    'inventory_item_id' => $inventoryItem->id,
                    // إذا كان الفرق موجب يعني زيادة (restock)، وإذا كان سالب يعني عجز/هالك (waste)
                    'type' => $difference > 0 ? 'restock' : 'waste',
                    'quantity' => $difference, // ستخزن بالإشارة (+ أو -) تلقائياً
                    'balance_after' => $newQuantity,
                    'user_id' => Auth::id() ?? 1,
                ]);
            }

            DB::commit();
            return redirect()->route('inventory.index')->with('success', 'تم تحديث البيانات بنجاح وتحديث دفتر حركات المخزن.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء تعديل البيانات.');
        }
    }

    /**
     * 🌟 دالة الحذف الآمنة (حماية السيستم من الانهيار)
     */
    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);

        // 🛡️ حماية: نتحقق إذا كانت المادة الخام مربوطة بأي منتج في المنيو (Recipes)
        // إذا كان لها علاقة، نمنع الحذف حتى لا تضرب فواتير الكاشير
        if ($item->recipes()->count() > 0) {
            return redirect()->route('inventory.index')->with('error', "لا يمكن حذف [{$item->name}] لأنها مرتبطة بمكونات مشروبات في المنيو! قم بإزالتها من المنيو أولاً.");
        }

        // إذا كانت آمنة وغير مربوطة بشيء، يتم الحذف
        // ملحوظة: جدول الـ movements سيحذف حركاتها تلقائياً بسبب onDelete('cascade') في الميجريشن
        $item->delete();

        return redirect()->route('inventory.index')->with('success', 'تم حذف المادة الخام بنجاح من المخزن.');
    }
    public function movements(Request $request)
    {
        $query = InventoryMovement::with([
            'inventoryItem',
            'invoice.items.menu',
            'user'
        ]);

        // فلترة بالتاريخ
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        // البحث باسم الخامة
        if ($request->filled('inventory')) {
            $query->whereHas('inventoryItem', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->inventory . '%');
            });
        }

        // البحث باسم المنتج المباع
        if ($request->filled('menu')) {
            $query->whereHas('invoice.items.menu', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->menu . '%');
            });
        }

        // نوع الحركة
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $movements = $query->latest()->paginate(30)->withQueryString();

        // --- حساب الإحصائيات (Summary Cards) لليوم الحالي ---
        $todaySales = \App\Models\InventoryMovement::whereDate('created_at', today())
            ->whereIn('type', ['sale', 'sale_update'])
            ->count();

        $todayWaste = \App\Models\InventoryMovement::whereDate('created_at', today())
            ->where('type', 'waste')
            ->count();

        $todayRestock = \App\Models\InventoryMovement::whereDate('created_at', today())
            ->where('type', 'restock')
            ->count();

        // جلب أكثر خامة تم استهلاكها اليوم (مبيعات أو هالك)
        $mostConsumed = \App\Models\InventoryMovement::whereDate('created_at', today())
            ->whereIn('type', ['sale', 'waste'])
            ->select('inventory_item_id', \DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('inventory_item_id')
            ->orderByDesc('total_qty')
            ->with('inventoryItem')
            ->first();

        $mostConsumedName = $mostConsumed ? $mostConsumed->inventoryItem->name : 'لا يوجد حركات اليوم';

        return view('inventory.movements', compact(
            'movements',
            'todaySales',
            'todayWaste',
            'todayRestock',
            'mostConsumedName'
        ));
    }
}
