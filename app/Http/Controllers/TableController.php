<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * عرض قائمة كل الطاولات (للأدمن والمشرف)
     */
    public function index()
    {
        // نجيب الطاولات مع معلومة الشغل أو لأ
        $tables = Table::latest()->get()->map(function ($table) {
            $table->is_occupied = $table->isOccupied();
            return $table;
        });

        return view('tables.index', compact('tables'));
    }

    /**
     * إظهار فورم إضافة طاولة جديدة
     */
    public function create()
    {
        return view('tables.create');
    }

    /**
     * حفظ طاولة جديدة
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100|unique:tables,name',
            'capacity' => 'nullable|integer|min:1|max:50',
            'area'     => 'nullable|string|max:100',
            'notes'    => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        Table::create([
            'name'     => $request->name,
            'capacity' => $request->capacity,
            'area'     => $request->area,
            'notes'    => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('tables.index')
            ->with('success', 'تم إضافة الطاولة بنجاح.');
    }

    /**
     * إظهار فورم التعديل
     */
    public function edit(Table $table)
    {
        return view('tables.edit', compact('table'));
    }

    public function show(Table $table)
    {
        return view('tables.show', compact('table'));
    }

    public function history()
    {
        $tables=Table::all();
        return view('tables.history', compact('tables'));
    }
    /**
     * تحديث بيانات الطاولة
     */
    public function update(Request $request, Table $table)
    {
        $request->validate([
            'name'     => 'required|string|max:100|unique:tables,name,' . $table->id,
            'capacity' => 'nullable|integer|min:1|max:50',
            'area'     => 'nullable|string|max:100',
            'notes'    => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        $table->update([
            'name'     => $request->name,
            'capacity' => $request->capacity,
            'area'     => $request->area,
            'notes'    => $request->notes,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('tables.index')
            ->with('success', 'تم تحديث بيانات الطاولة بنجاح.');
    }

    /**
     * حذف الطاولة – بشرط ما تكونش عندها طلبات نشطة
     */
    public function destroy(Table $table)
    {
        // نمنع الحذف لو في أوردر مفتوح على الطاولة دي
        if ($table->isOccupied()) {
            return redirect()->route('tables.index')
                ->with('error', 'لا يمكن حذف الطاولة لأن عليها طلب مفتوح الآن.');
        }

        // لو في أوردرات تاريخية → نلغي رابط الطاولة منها ونحذفها
        $table->orders()->update(['table_id' => null]);
        $table->delete();

        return redirect()->route('tables.index')
            ->with('success', 'تم حذف الطاولة بنجاح.');
    }

    /**
     * تفعيل / تعطيل الطاولة عبر AJAX (POST /tables/{table}/toggle)
     */
    public function toggle(Table $table)
    {
        // نمنع تعطيل طاولة عليها أوردر نشط
        if ($table->is_active && $table->isOccupied()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعطيل الطاولة لأن عليها طلب مفتوح الآن.',
            ], 422);
        }

        $table->update(['is_active' => !$table->is_active]);

        return response()->json([
            'success'   => true,
            'is_active' => $table->is_active,
            'message'   => $table->is_active ? 'تم تفعيل الطاولة.' : 'تم تعطيل الطاولة.',
        ]);
    }

    /**
     * JSON endpoint للـ POS – الطاولات النشطة مع بيانات الطلب المفتوح
     * GET /pos/tables
     */
    public function posIndex()
    {
        $tables = Table::active()
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

        return response()->json(['data' => $tables]);
    }
}
