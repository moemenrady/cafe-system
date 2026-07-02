<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Menu;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    public function index()
{
    // جلب المنتجات المتاحة فقط لعرضها في قائمة البيع
    $menus = Menu::where('is_available', true)->get();
    
    return view('pos.index', compact('menus'));
}
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $totalPrice = 0;
            foreach ($request->items as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                $totalPrice += ($menu->price * $item['quantity']);
            }

            $sale = Sale::create([
                'total_price' => $totalPrice,
                'paid_amount' => $request->paid_amount,
                'change_amount' => $request->paid_amount - $totalPrice,
                'user_id' => Auth::id() ?? 1,
            ]);

            foreach ($request->items as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'menu_id' => $menu->id,
                    'quantity' => $item['quantity'],
                    'price' => $menu->price,
                ]);

                // الخصم من المخزن
                foreach ($menu->recipes as $recipe) {
                    $inventory = $recipe->inventoryItem;
                    if ($inventory) {
                        $inventory->decrement('quantity', $recipe->quantity_used * $item['quantity']);                    }
                }
            }

            return response()->json(['message' => 'تمت عملية البيع بنجاح وخصم المخزن!']);
        });
    }
}