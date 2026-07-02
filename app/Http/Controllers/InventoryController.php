<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function movements()
    {
        // جلب الحركات مرتبة من الأحدث مع المشرف المسؤول (creator / user) والفاتورة
        $movements = InvoiceTransaction::with(['creator', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.movements.index', compact('movements'));
    }
    public function index()
    {
        $items = InventoryItem::latest()->get();
        return view('inventory.index', compact('items'));
    }

    public function create()
    {
        return view('inventory.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'reorder_level' => 'required|numeric|min:0',
        ]);

        InventoryItem::create($request->all());

        return redirect()->route('inventory.index')->with('success', 'تم إضافة المادة الخام بنجاح');
    }

    public function edit($id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);
        return view('inventory.edit', compact('inventoryItem'));
    }

    public function update(Request $request, $id)
    {
        $inventoryItem = InventoryItem::findOrFail($id);

        // تم ضبط الـ Validation لاستثناء السجل الحالي من شرط التكرار، مع إضافة حد الطلب
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:inventory_items,name,' . $id,
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'reorder_level' => 'required|numeric|min:0',
        ]);

        $inventoryItem->update($validated);

        return redirect()->route('inventory.index')->with('success', 'تم تحديث البيانات بنجاح');
    }

    public function destroy($id)
    {
        $item = InventoryItem::findOrFail($id);
        $item->delete();
        return redirect()->route('inventory.index')->with('success', 'تم حذف المادة الخام بنجاح');
    }
}
