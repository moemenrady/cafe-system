<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    /**
     * التحقق المزدوج من أن المستخدم مدير أو مشرف
     */
    protected function authorizeManager(): void
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['admin', 'supervisor'], true)) {
            abort(403, 'غير مصرح لك بالوصول. هذه الصفحة مخصصة للمديرين والمشرفين فقط.');
        }
    }

    /**
     * تطبيق فلتر التاريخ على كويري المصروفات
     */
    protected function applyDateFilter($query, Request $request): void
    {
        if ($request->filled('date_filter')) {
            match ($request->date_filter) {
                'today' => $query->whereDate('expense_date', Carbon::today()),
                'yesterday' => $query->whereDate('expense_date', Carbon::yesterday()),
                'this_week' => $query->whereBetween('expense_date', [
                    Carbon::now()->startOfWeek(),
                    Carbon::now()->endOfWeek()
                ]),
                'this_month' => $query->whereMonth('expense_date', Carbon::now()->month)
                    ->whereYear('expense_date', Carbon::now()->year),
                default => null,
            };
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->filled('date_from')) {
                $query->whereDate('expense_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('expense_date', '<=', $request->date_to);
            }
        }
    }

    /**
     * عرض قائمة أنواع المصروفات مع الإحصائيات وفلترة التاريخ
     */
    public function index(Request $request)
    {
        $this->authorizeManager();

        $query = ExpenseCategory::query();

        // 1. فلترة البحث بالاسم أو الوصف
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // 2. فلترة حالة التفعيل
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // 3. حساب عدد المصروفات وإجمالي المبالغ لكل نوع بناءً على فلتر التاريخ
        $query->withCount([
            'expenses as expenses_count' => function ($q) use ($request) {
                $this->applyDateFilter($q, $request);
            }
        ])->withSum([
            'expenses as total_amount' => function ($q) use ($request) {
                $this->applyDateFilter($q, $request);
            }
        ], 'amount');

        // ترتيب الأنواع افتراضياً حسب الأكثر إنفاقاً ثم الاسم
        $categories = $query->orderByDesc('total_amount')->orderBy('name')->get();

        // 4. بطاقات الإحصائيات العامة للفترة المحددة
        $expensesBaseQuery = Expense::query();
        $this->applyDateFilter($expensesBaseQuery, $request);

        $totalPeriodAmount = (clone $expensesBaseQuery)->sum('amount');
        $totalPeriodCount = (clone $expensesBaseQuery)->count();

        // تحديد أعلى تصنيف إنفاقاً في هذه الفترة
        $topCategory = $categories->first(fn($c) => ($c->total_amount ?? 0) > 0);

        $stats = [
            'total_period_amount' => $totalPeriodAmount,
            'total_period_count'  => $totalPeriodCount,
            'categories_count'    => ExpenseCategory::count(),
            'active_count'        => ExpenseCategory::where('is_active', true)->count(),
            'top_category_name'   => $topCategory ? $topCategory->name : 'لا يوجد',
            'top_category_amount' => $topCategory ? ($topCategory->total_amount ?? 0) : 0,
        ];

        return view('expense_categories.index', compact('categories', 'stats'));
    }

    /**
     * صفحة إضافة نوع مصروف جديد
     */
    public function create()
    {
        $this->authorizeManager();
        return view('expense_categories.create');
    }

    /**
     * حفظ نوع المصروف الجديد
     */
    public function store(Request $request)
    {
        $this->authorizeManager();

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required' => 'يرجى إدخال اسم نوع المصروف.',
            'name.unique'   => 'اسم نوع المصروف مسجل مسبقاً.',
            'name.max'      => 'اسم نوع المصروف لا يمكن أن يتجاوز 255 حرفاً.',
        ]);

        ExpenseCategory::create([
            'name'        => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->has('is_active') ? (bool)$request->is_active : true,
        ]);

        return redirect()->route('expense-categories.index')->with('success', 'تمت إضافة نوع المصروف بنجاح.');
    }

    /**
     * عند استدعاء show لنوع المصروف: نقله لصفحة المصروفات مفلترة عليه
     */
    public function show(ExpenseCategory $expenseCategory, Request $request)
    {
        $this->authorizeManager();

        $params = array_filter([
            'category_id' => $expenseCategory->id,
            'date_filter' => $request->date_filter,
            'date_from'   => $request->date_from,
            'date_to'     => $request->date_to,
        ]);

        return redirect()->route('expenses.index', $params);
    }

    /**
     * صفحة تعديل نوع المصروف
     */
    public function edit(ExpenseCategory $expenseCategory)
    {
        $this->authorizeManager();
        return view('expense_categories.edit', compact('expenseCategory'));
    }

    /**
     * تحديث بيانات نوع المصروف
     */
    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $this->authorizeManager();

        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ], [
            'name.required' => 'يرجى إدخال اسم نوع المصروف.',
            'name.unique'   => 'اسم نوع المصروف مسجل مسبقاً لنوع آخر.',
            'name.max'      => 'اسم نوع المصروف لا يمكن أن يتجاوز 255 حرفاً.',
        ]);

        $expenseCategory->update([
            'name'        => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        return redirect()->route('expense-categories.index')->with('success', 'تم تعديل بيانات نوع المصروف بنجاح.');
    }

    /**
     * حذف نوع المصروف
     */
    public function destroy(ExpenseCategory $expenseCategory)
    {
        $this->authorizeManager();

        $expensesCount = $expenseCategory->expenses()->count();

        // حماية سلامة البيانات المالية: منع حذف الأنواع التي تحتوي على مصروفات سابقة
        if ($expensesCount > 0) {
            return redirect()->route('expense-categories.index')->with('error', "لا يمكن حذف هذا النوع لوجود ({$expensesCount}) مصروف مسجل به. يمكنك تعطيل حالته بدلاً من حذفه لحماية السجلات المالية.");
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')->with('success', 'تم حذف نوع المصروف بنجاح.');
    }
}
