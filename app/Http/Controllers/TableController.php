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
        // نجيب الطاولات مع معلومة الشغل باستعلام واحد
        $tables = Table::withExists(['activeOrders as is_occupied'])
            ->latest()
            ->get();

        return view('tables.index', compact('tables'));
    }
    /**
     * عرض قائمة الطاولات المشغولة والصالة وإمكانية فتح أي طاولة
     */
    public function busyTables()
    {
        // 1. جلب الطاولات المشغولة حالياً مع تفاصيل الطلبات النشطة والأصناف ومُنشئ الطلب
        $busyTables = Table::whereHas('activeOrders')
            ->with([
                'activeOrders.items.menu',
                'activeOrders.customer',
                'activeOrders.creator',
            ])
            ->withExists(['activeOrders as is_occupied'])
            ->orderBy('name')
            ->get();

        // 2. جلب جميع الطاولات (المشغولة والشاغرة) لتمكين المستخدم من تصفح وفتح أي طاولة
        $allTables = Table::with([
                'activeOrders.items.menu',
                'activeOrders.creator',
            ])
            ->withExists(['activeOrders as is_occupied'])
            ->orderBy('name')
            ->get();

        // 3. حساب الإحصائيات الحية
        $totalCount = $allTables->count();
        $busyCount = $busyTables->count();
        $vacantCount = $allTables->where('is_occupied', false)->where('is_active', true)->count();
        $occupancyRate = $totalCount > 0 ? round(($busyCount / $totalCount) * 100) : 0;
        
        // إجمالي المبالغ المطلوبة على الطاولات المفتوحة
        $totalUnpaidAmount = $busyTables->sum(function ($table) {
            return $table->activeOrders->sum('total');
        });

        // المناطق الفريدة للفلترة
        $areas = $allTables->pluck('area')->filter()->unique()->values();

        // نمرر $tables للتوافقية الكاملة
        $tables = $busyTables;

        return view('tables.busy_tables', compact(
            'tables',
            'busyTables',
            'allTables',
            'totalCount',
            'busyCount',
            'vacantCount',
            'occupancyRate',
            'totalUnpaidAmount',
            'areas'
        ));
    }

    /**
     * عرض تفاصيل الطاولة والطلبات المرتبطة بها (إذا كانت مشغولة أو سجل الطلبات)
     */
    public function show(Table $table)
    {
        $table->load([
            'activeOrders.items.menu',
            'activeOrders.customer',
            'activeOrders.creator',
            'orders' => function ($q) {
                $q->latest()->take(10)->with(['items.menu', 'creator']);
            }
        ]);

        $isOccupied = $table->isOccupied();
        $activeOrders = $table->activeOrders;

        return view('tables.show', compact('table', 'isOccupied', 'activeOrders'));
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
     * JSON endpoint للـ POS – الطاولات النشطة مع حالة الشغل
     * GET /pos/tables
     */
    public function posIndex()
    {
        $tables = Table::active()
            ->withExists(['activeOrders as is_occupied'])
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'area']);

        return response()->json(['data' => $tables]);
    }
}
