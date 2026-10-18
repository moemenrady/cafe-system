<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\PurchaseInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    /**
     * قائمة الموردين مع الإحصائيات وفلاتر البحث
     */
    public function index(Request $request)
    {
        $query = Supplier::withCount('invoices');

        // البحث بالاسم، اسم الشركة، أو رقم الهاتف
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $suppliers = $query->orderBy('name')->get();

        // بطاقات المؤشرات العامة (KPIs)
        $totalSuppliersCount = Supplier::count();
        $totalPurchasesAll = (float) PurchaseInvoice::sum('net_amount');
        $totalPaidAll = (float) PurchaseInvoice::sum('paid_amount');
        $totalDueAll = (float) PurchaseInvoice::sum('remaining_amount');

        return view('suppliers.index', compact(
            'suppliers',
            'totalSuppliersCount',
            'totalPurchasesAll',
            'totalPaidAll',
            'totalDueAll'
        ));
    }

    /**
     * صفحة إضافة مورد جديد
     */
    public function create()
    {
        return view('suppliers.create');
    }

    /**
     * حفظ مورد جديد
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255|unique:suppliers,name',
            'company_name'    => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric',
            'notes'           => 'nullable|string',
        ], [
            'name.required' => 'يرجى إدخال اسم المورد.',
            'name.unique'   => 'هذا المورد مسجل بالفعل مسبقاً.',
        ]);

        $validated['opening_balance'] = (float) ($request->opening_balance ?? 0);

        $supplier = Supplier::create($validated);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'  => true,
                'supplier' => $supplier,
                'message'  => 'تم إضافة المورد بنجاح.',
            ]);
        }

        return redirect()->route('suppliers.show', $supplier->id)
            ->with('success', "تم إضافة المورد [{$supplier->name}] بنجاح.");
    }

    /**
     * عرض صفحة المورد الشاملة (ملف المورد، كشف الحساب، فواتير الشراء، وزر الشراء المباشر)
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['invoices' => function ($q) {
            $q->with(['items.inventoryItem', 'user'])->orderByDesc('invoice_date')->orderByDesc('id');
        }]);

        $invoices = $supplier->invoices;
        $totalPurchases = (float) $invoices->sum('net_amount');
        $totalPaid = (float) $invoices->sum('paid_amount');
        $totalDue = (float) $invoices->sum('remaining_amount') + (float) $supplier->opening_balance;
        $invoicesCount = $invoices->count();

        return view('suppliers.show', compact(
            'supplier',
            'invoices',
            'totalPurchases',
            'totalPaid',
            'totalDue',
            'invoicesCount'
        ));
    }

    /**
     * فورم تعديل بيانات المورد
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * تحديث بيانات المورد
     */
    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255|unique:suppliers,name,' . $supplier->id,
            'company_name'    => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'email'           => 'nullable|email|max:255',
            'address'         => 'nullable|string|max:255',
            'opening_balance' => 'nullable|numeric',
            'notes'           => 'nullable|string',
        ], [
            'name.required' => 'يرجى إدخال اسم المورد.',
            'name.unique'   => 'اسم المورد مستخدم مسبقاً لمورد آخر.',
        ]);

        $validated['opening_balance'] = (float) ($request->opening_balance ?? 0);

        $oldName = $supplier->name;
        $supplier->update($validated);

        // إذا تم تغيير الاسم، نحدث اسم المورد في الفواتير التابعة له
        if ($oldName !== $supplier->name) {
            $supplier->invoices()->update(['supplier_name' => $supplier->name]);
        }

        return redirect()->route('suppliers.show', $supplier->id)
            ->with('success', "تم تحديث بيانات المورد [{$supplier->name}] بنجاح.");
    }

    /**
     * حذف مورد
     */
    public function destroy(Supplier $supplier)
    {
        $name = $supplier->name;
        $invoicesCount = $supplier->invoices()->count();

        // إبقاء الفواتير مع فك الربط للحفاظ على السجلات المحاسبية
        $supplier->invoices()->update(['supplier_id' => null]);
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', "تم حذف المورد [{$name}] بنجاح (مع الاحتفاظ بـ {$invoicesCount} فاتورة كأرشيف محاسبي).");
    }
}
