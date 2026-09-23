<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * هل المستخدم الحالي بصلاحية مدير / مشرف؟
     */
    protected function isManager(?User $user = null): bool
    {
        $user = $user ?: auth()->user();
        return $user && in_array($user->role, ['admin', 'supervisor'], true);
    }

    /**
     * التحقق من صلاحية تعديل أو حذف المصروف
     */
    protected function authorizeExpenseAccess(Expense $expense): void
    {
        $user = auth()->user();
        if (!$this->isManager($user) && $expense->created_by !== $user->id) {
            abort(403, 'غير مصرح لك بالتعامل مع مصروفات موظف آخر.');
        }
    }

    /**
     * عرض قائمة المصروفات مجمعة حسب التاريخ (Sub-groups)
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isManager = $this->isManager($user);

        $query = Expense::with(['category', 'creator']);

        // 1. عزل الصلاحيات: الموظف يرى سجلاته فقط، والمدير يرى الكل
        if (!$isManager) {
            $query->where('created_by', $user->id);
        } elseif ($request->filled('employee_id') && $request->employee_id !== 'all') {
            $query->where('created_by', $request->employee_id);
        }

        // 2. فلترة بنوع المصروف
        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        // 3. البحث بالكلمات المفتاحية في الملاحظات أو العنوان أو اسم الموظف
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhereHas('category', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('creator', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // 4. الفلترة بالتاريخ
        if ($request->filled('date_filter')) {
            match ($request->date_filter) {
                'today' => $query->whereDate('expense_date', Carbon::today()),
                'yesterday' => $query->whereDate('expense_date', Carbon::yesterday()),
                'this_week' => $query->whereBetween('expense_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]),
                'this_month' => $query->whereMonth('expense_date', Carbon::now()->month)->whereYear('expense_date', Carbon::now()->year),
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

        // حساب الإحصائيات (تراعي الفلاتر المحددة مع إمكانية عرض إجمالي اليوم)
        $statsBaseQuery = Expense::query();
        if (!$isManager) {
            $statsBaseQuery->where('created_by', $user->id);
        }

        $selectedCategory = ($request->filled('category_id') && $request->category_id !== 'all')
            ? ExpenseCategory::find($request->category_id)
            : null;

        $stats = [
            'total_amount'    => (clone $query)->sum('amount'),
            'today_amount'    => (clone $statsBaseQuery)->whereDate('expense_date', Carbon::today())->sum('amount'),
            'total_count'     => (clone $query)->count(),
            'employees_count' => $isManager ? Expense::distinct('created_by')->count('created_by') : 1,
        ];

        // ترتيب المصروفات من الأحدث للأقدم بالتاريخ
        $query->orderBy('expense_date', 'desc')->orderBy('created_at', 'desc');

        $paginatedExpenses = $query->paginate(25)->appends($request->query());

        // تجميع السجلات حسب التاريخ في مجموعات فرعية (Sub-groups)
        $groupedExpenses = $paginatedExpenses->getCollection()->groupBy(function ($item) {
            return $item->expense_date
                ? $item->expense_date->format('Y-m-d')
                : $item->created_at->format('Y-m-d');
        });

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        $employees = $isManager ? User::where('is_active', true)->orderBy('name')->get() : collect();

        return view('expenses.index', compact(
            'paginatedExpenses',
            'groupedExpenses',
            'stats',
            'categories',
            'employees',
            'isManager',
            'selectedCategory'
        ));
    }

    /**
     * فورم إضافة مصروف جديد
     */
    public function create()
    {
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        return view('expenses.create', compact('categories'));
    }

    /**
     * حفظ المصروف الجديد في قاعدة البيانات
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'category_id'  => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string|max:1000',
            'title'        => 'nullable|string|max:255',
        ], [
            'amount.required'       => 'يرجى إدخال مبلغ المصروف.',
            'amount.numeric'        => 'المبلغ يجب أن يكون رقماً صحيحاً.',
            'amount.min'            => 'المبلغ يجب أن يكون أكبر من صفر.',
            'category_id.required'  => 'يرجى اختيار نوع المصروف.',
            'category_id.exists'    => 'نوع المصروف المختار غير صحيح.',
            'expense_date.required' => 'يرجى تحديد تاريخ المصروف.',
            'expense_date.date'     => 'صيغة التاريخ غير صحيحة.',
        ]);

        $category = ExpenseCategory::findOrFail($validated['category_id']);
        $activeShift = \App\Models\Shift::where('user_id', auth()->id())->where('status', 'open')->latest('id')->first();

        $expense = Expense::create([
            'category_id'  => $validated['category_id'],
            'shift_id'     => $activeShift?->id,
            'title'        => !empty($validated['title']) ? $validated['title'] : $category->name,
            'amount'       => $validated['amount'],
            'notes'        => $validated['notes'] ?? null,
            'expense_date' => $validated['expense_date'],
            'created_by'   => auth()->id(),
        ]);

        app(\App\Services\ShiftActionService::class)->logExpense($expense->fresh(['category', 'creator']), 'expense_created');

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل المصروف بنجاح.');
    }

    /**
     * عرض تفاصيل مصروف محدد
     */
    public function show(Expense $expense)
    {
        $this->authorizeExpenseAccess($expense);
        $expense->load(['category', 'creator']);
        return view('expenses.show', compact('expense'));
    }

    /**
     * فورم تعديل المصروف
     */
    public function edit(Expense $expense)
    {
        $this->authorizeExpenseAccess($expense);
        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();
        return view('expenses.edit', compact('expense', 'categories'));
    }

    /**
     * تحديث بيانات المصروف
     */
    public function update(Request $request, Expense $expense)
    {
        $this->authorizeExpenseAccess($expense);

        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'category_id'  => 'required|exists:expense_categories,id',
            'expense_date' => 'required|date',
            'notes'        => 'nullable|string|max:1000',
            'title'        => 'nullable|string|max:255',
        ], [
            'amount.required'       => 'يرجى إدخال مبلغ المصروف.',
            'amount.numeric'        => 'المبلغ يجب أن يكون رقماً صحيحاً.',
            'amount.min'            => 'المبلغ يجب أن يكون أكبر من صفر.',
            'category_id.required'  => 'يرجى اختيار نوع المصروف.',
            'category_id.exists'    => 'نوع المصروف المختار غير صحيح.',
            'expense_date.required' => 'يرجى تحديد تاريخ المصروف.',
        ]);

        $category = ExpenseCategory::findOrFail($validated['category_id']);
        $oldSnapshot = $expense->toArray();

        $expense->update([
            'category_id'  => $validated['category_id'],
            'title'        => !empty($validated['title']) ? $validated['title'] : $category->name,
            'amount'       => $validated['amount'],
            'notes'        => $validated['notes'] ?? null,
            'expense_date' => $validated['expense_date'],
        ]);

        app(\App\Services\ShiftActionService::class)->logExpense(
            $expense->fresh(['category', 'creator']),
            'expense_updated',
            ['old' => $oldSnapshot, 'new' => $expense->toArray()]
        );

        return redirect()->route('expenses.index')->with('success', 'تم تعديل بيانات المصروف بنجاح.');
    }

    /**
     * حذف المصروف
     */
    public function destroy(Expense $expense)
    {
        $this->authorizeExpenseAccess($expense);
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'تم حذف المصروف بنجاح.');
    }
}
