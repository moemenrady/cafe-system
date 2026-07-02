<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Menu;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index()
    {
        $menus = Menu::with('category')->latest()->get();
        
        // تم إرجاع اسم العلاقة إلى menuItem لتطابق الموديل الخاص بك تماماً
        $recipes = Recipe::with(['menuItem', 'inventoryItem'])->get(); 
        
        return view('recipes.index', compact('menus', 'recipes'));
    }

    public function create()
    {
        $menus = Menu::all();
        $inventories = InventoryItem::all();
        
        return view('recipes.create', compact('menus', 'inventories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            // 🌟 تم التعديل هنا ليفحص جدول menu المفرد بناءً على الـ Migration الفعلي لشركتك
            'menu_item_id' => 'required|exists:menu,id', 
            'inventory_ids' => 'required|array',
            'inventory_ids.*' => 'exists:inventory_items,id',
            'quantities' => 'required|array',
            'quantities.*' => 'numeric|min:0.01',
        ]);

        foreach ($request->inventory_ids as $index => $invId) {
            Recipe::create([
                'menu_item_id' => $request->menu_item_id, 
                'inventory_item_id' => $invId,
                'quantity_used' => $request->quantities[$index],
            ]);
        }

        return redirect()->route('recipes.index')->with('success', 'تم حفظ جميع المكونات بنجاح');
    }

    public function destroy($id)
    {
        Recipe::findOrFail($id)->delete();
        return redirect()->route('recipes.index')->with('success', 'تم إزالة المكون');
    }
}