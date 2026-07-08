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
        // جلب المنتجات مع الأقسام والوصفات
        $menus = Menu::with(['category', 'recipes.inventoryItem'])->latest()->get();
        // جلب الخامات لاستخدامها في المودال (النافذة المنبثقة)
        $inventories = InventoryItem::all();

        return view('recipes.index', compact('menus', 'inventories'));
    }

    // دالة التحديث الجديدة (تعمل كإضافة وتعديل في نفس الوقت)
    public function update(Request $request, $recipe)
    {
        $request->validate([
            'inventory_ids' => 'nullable|array',
            'inventory_ids.*' => 'exists:inventory_items,id',
            'quantities' => 'nullable|array',
            'quantities.*' => 'numeric|min:0.01',
        ]);

        $menu = Menu::findOrFail($recipe);

        // مسح جميع المكونات القديمة لهذا المنتج (لتجنب التكرار وبناء الوصفة من جديد)
        Recipe::where('menu_item_id', $menu->id)->delete();

        // إذا أرسل المستخدم مكونات جديدة، نقوم بإضافتها
        if ($request->has('inventory_ids') && $request->has('quantities')) {
            foreach ($request->inventory_ids as $index => $invId) {
                // التأكد من وجود كمية مقابلة للخامة
                if(isset($request->quantities[$index]) && $request->quantities[$index] > 0) {
                    Recipe::create([
                        'menu_item_id' => $menu->id,
                        'inventory_item_id' => $invId,
                        'quantity_used' => $request->quantities[$index],
                    ]);
                }
            }
        }

        return redirect()->route('recipes.index')->with('success', 'تم حفظ وتحديث وصفة المنتج بنجاح.');
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
