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

        // الطاولات النشطة مع تفاصيل الطلبات المفتوحة إن وجدت
        $activeTables = Table::active()
            ->with(['currentOrder.items.menu', 'currentOrder.customer', 'currentOrder.creator'])
            ->orderBy('name')
            ->get()
            ->map(function ($table) {
                $order = $table->currentOrder;
                return [
                    'id'          => $table->id,
                    'name'        => $table->name,
                    'capacity'    => $table->capacity,
                    'area'        => $table->area,
                    'is_occupied' => (bool) $order,
                    'order'       => $order ? [
                        'id'             => $order->id,
                        'order_number'   => $order->order_number,
                        'opened_at'      => $order->created_at?->format('Y-m-d H:i'),
                        'opened_at_time' => $order->created_at?->format('H:i'),
                        'employee'       => $order->creator?->name ?? 'الكاشير',
                        'customer_name'  => $order->customer?->name,
                        'customer_phone' => $order->customer?->phone ?? $order->phone,
                        'subtotal'       => (float) $order->subtotal,
                        'discount'       => (float) $order->discount,
                        'vat'            => (float) $order->vat,
                        'total'          => (float) $order->total,
                        'items'          => $order->items->map(function ($item) {
                            return [
                                'id'         => $item->id,
                                'menu_id'    => $item->menu_id,
                                'name'       => $item->menu?->name ?? 'صنف',
                                'quantity'   => $item->quantity,
                                'price'      => (float) $item->price,
                                'total'      => (float) $item->total,
                                'notes'      => $item->notes,
                            ];
                        })->values(),
                    ] : null,
                ];
            });

        return view('pos.index', compact('menus', 'categories', 'activeTables'));
    }
}