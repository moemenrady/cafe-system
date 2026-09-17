<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Category;
use App\Models\Table;
use Illuminate\Http\Request;

/**
 * SaleController – مسؤول عن تحميل شاشة الكاشير (GET /pos)
 *
 * ملاحظة: عملية حفظ الأوردر تتم عبر OrderController@store (POST /orders)
 * ولا شيء يُحفظ هنا مباشرة.
 */
class SaleController extends Controller
{
    public function index()
    {
        // المنتجات المتاحة فقط مع الفئات
        $menus = Menu::with('category')
            ->where('is_available', true)
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        // الفئات الموجودة فعلاً في المنتجات المتاحة (لا نجيب كل الفئات)
        $categories = Category::whereHas('menuItems', function ($q) {
            $q->where('is_available', true);
        })->orderBy('name')->get();

        // الطاولات النشطة فقط لعرضها في الـ POS (تُحمَّل مبدئياً ثم تُحدَّث عبر AJAX)
        $activeTables = Table::active()->orderBy('name')->get()->map(function ($table) {
            return [
                'id'          => $table->id,
                'name'        => $table->name,
                'capacity'    => $table->capacity,
                'area'        => $table->area,
                'is_occupied' => $table->isOccupied(),
            ];
        });

        return view('pos.index', compact('menus', 'categories', 'activeTables'));
    }
}