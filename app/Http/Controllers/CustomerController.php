<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    /**
     * عرض قائمة وسجل العملاء الشامل كمرجع استراتيجي لإدارة الكافيه (CRM)
     */
    public function index(Request $request)
    {
        $query = Customer::query()
            ->withCount(['invoices', 'orders'])
            ->withSum('invoices', 'total')
            ->withMax('invoices', 'created_at');

        // 1. بحث بالاسم أو رقم الهاتف
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // 2. فلتر نطاق التاريخ (تاريخ التسجيل)
        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // 3. فلتر الحد الأدنى والأقصى للإنفاق
        if ($request->filled('min_spent')) {
            $min = (float) $request->input('min_spent');
            $query->whereRaw("(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) >= {$min}");
        }
        if ($request->filled('max_spent')) {
            $max = (float) $request->input('max_spent');
            $query->whereRaw("(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) <= {$max}");
        }

        // 4. فلتر الحد الأدنى لعدد الزيارات
        if ($request->filled('min_visits')) {
            $minVisits = (int) $request->input('min_visits');
            $query->whereRaw("(SELECT COUNT(*) FROM invoices WHERE invoices.client_id = customers.id) >= {$minVisits}");
        }

        // 5. فلتر تصنيف العميل (VIP / دائم / جديد)
        $vipFilter = $request->input('vip_type');
        if ($vipFilter === 'vip') {
            $query->whereRaw('(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) >= 2000');
        } elseif ($vipFilter === 'regular') {
            $query->whereRaw('(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) >= 500')
                  ->whereRaw('(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) < 2000');
        } elseif ($vipFilter === 'new') {
            $query->whereRaw('(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) < 500');
        }

        // الترتيب: الأكثر إنفاقاً أولاً أو الأحدث
        $sortBy = $request->input('sort_by', 'total_spent');
        if ($sortBy === 'latest_visit') {
            $query->orderByDesc('invoices_max_created_at');
        } elseif ($sortBy === 'visits') {
            $query->orderByDesc('invoices_count');
        } elseif ($sortBy === 'name') {
            $query->orderBy('name');
        } else {
            $query->orderByDesc('invoices_sum_total')->orderByDesc('id');
        }

        $customers = $query->paginate(15)->withQueryString();

        // مؤشرات أداء عامة لجميع العملاء (CRM Top KPIs)
        $totalCustomersCount = Customer::count();
        $totalRevenueFromCustomers = (float) Invoice::whereNotNull('client_id')->sum('total');
        $averageCustomerSpend = $totalCustomersCount > 0
            ? round($totalRevenueFromCustomers / $totalCustomersCount, 2)
            : 0;

        $activeThisMonthCount = Invoice::whereNotNull('client_id')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->distinct('client_id')
            ->count('client_id');

        $vipCustomersCount = Customer::withSum('invoices', 'total')
            ->get()
            ->filter(fn($c) => ($c->invoices_sum_total ?? 0) >= 2000 || $c->invoices()->count() >= 15)
            ->count();

        return view('customers.index', compact(
            'customers',
            'totalCustomersCount',
            'totalRevenueFromCustomers',
            'averageCustomerSpend',
            'activeThisMonthCount',
            'vipCustomersCount'
        ));
    }

    /**
     * تصدير بيانات وسجل العملاء كشيت إكسيل احترافي (CSV بترميز UTF-8 BOM)
     */
    public function export(Request $request): StreamedResponse
    {
        $query = Customer::query()
            ->withCount(['invoices', 'orders'])
            ->withSum('invoices', 'total')
            ->withMax('invoices', 'created_at');

        // تطبيق الفلاتر الحالية ذاتها
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($request->filled('min_spent')) {
            $min = (float) $request->input('min_spent');
            $query->whereRaw("(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) >= {$min}");
        }
        if ($request->filled('max_spent')) {
            $max = (float) $request->input('max_spent');
            $query->whereRaw("(SELECT COALESCE(SUM(total), 0) FROM invoices WHERE invoices.client_id = customers.id) <= {$max}");
        }
        if ($request->filled('min_visits')) {
            $minVisits = (int) $request->input('min_visits');
            $query->whereRaw("(SELECT COUNT(*) FROM invoices WHERE invoices.client_id = customers.id) >= {$minVisits}");
        }

        $customers = $query->orderByDesc('invoices_sum_total')->get();

        $filename = 'تقرير_عملاء_الكافيه_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($customers) {
            $handle = fopen('php://output', 'w');

            // إضافة UTF-8 BOM لضمان فتح اللغة العربية بسلاسة في Excel
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // عناوين الأعمدة
            fputcsv($handle, [
                '# كود العميل',
                'اسم العميل',
                'رقم الهاتف',
                'العنوان',
                'تصنيف العميل',
                'عدد الزيارات والفواتير',
                'إجمالي المدفوعات (ج.م)',
                'متوسط الفاتورة (ج.م)',
                'طريقة الدفع المفضلة',
                'تاريخ التسجيل',
                'تاريخ آخر زيارة',
                'ملاحظات العميل',
            ]);

            $totalSumSpent = 0.0;
            $totalSumVisits = 0;

            foreach ($customers as $c) {
                $spent = (float) ($c->invoices_sum_total ?? $c->total_spent);
                $visits = (int) ($c->invoices_count ?? $c->visits_count);
                $avg = $visits > 0 ? round($spent / $visits, 2) : 0;
                $vip = $c->vip_badge['label'] ?? 'عميل جديد';
                $lastVisit = $c->last_visit_date ? $c->last_visit_date->format('Y-m-d H:i') : 'لا يوجد';

                $totalSumSpent += $spent;
                $totalSumVisits += $visits;

                fputcsv($handle, [
                    $c->id,
                    $c->name,
                    $c->phone ? "'{$c->phone}'" : 'غير مسجل',
                    $c->address ?: '-',
                    $vip,
                    $visits,
                    number_format($spent, 2, '.', ''),
                    number_format($avg, 2, '.', ''),
                    $c->favorite_payment_method,
                    $c->created_at->format('Y-m-d'),
                    $lastVisit,
                    $c->notes ?: '-',
                ]);
            }

            // سطر الإجماليات والملخص
            fputcsv($handle, []);
            fputcsv($handle, [
                'الإجمالي العام',
                'عدد العملاء: ' . $customers->count(),
                '-',
                '-',
                '-',
                'إجمالي الزيارات: ' . $totalSumVisits,
                number_format($totalSumSpent, 2, '.', '') . ' ج.م',
                'متوسط عام: ' . ($totalSumVisits > 0 ? number_format($totalSumSpent / $totalSumVisits, 2, '.', '') : '0.00'),
                '-',
                '-',
                '-',
                'تم التصدير من سيستم UNO Cafe',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * عرض بروفايل العميل الشامل وسجل زياراته وفواتيره بالتفصيل
     */
    public function show(Customer $customer)
    {
        $customer->load([
            'invoices.items.menu',
            'invoices.creator',
            'invoices.order.table',
            'orders.items.menu',
            'orders.table',
            'orders.creator',
        ]);

        // قائمة الفواتير مرتبة تنازلياً
        $invoices = $customer->invoices()->latest('created_at')->paginate(20);

        // إحصائيات فردية
        $totalSpent = $customer->total_spent;
        $visitsCount = $customer->visits_count;
        $avgTicket = $customer->average_order_value;
        $firstVisit = $customer->first_visit_date;
        $lastVisit = $customer->last_visit_date;
        $favPayment = $customer->favorite_payment_method;
        $vipBadge = $customer->vip_badge;

        // الأصناف الأكثر طلباً من هذا العميل
        $favoriteItems = DB::table('invoice_items')
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('menu', 'menu.id', '=', 'invoice_items.menu_id')
            ->where('invoices.client_id', $customer->id)
            ->select('menu.name', DB::raw('SUM(invoice_items.quantity) as total_qty'), DB::raw('SUM(invoice_items.total) as total_amount'))
            ->groupBy('menu.name')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();

        return view('customers.show', compact(
            'customer',
            'invoices',
            'totalSpent',
            'visitsCount',
            'avgTicket',
            'firstVisit',
            'lastVisit',
            'favPayment',
            'vipBadge',
            'favoriteItems'
        ));
    }

    /**
     * تصدير سجل فواتير وزيارات عميل واحد إلى ملف Excel/CSV
     */
    public function exportSingle(Customer $customer): StreamedResponse
    {
        $customer->load(['invoices.items.menu', 'invoices.creator', 'invoices.order.table']);
        $invoices = $customer->invoices()->latest('created_at')->get();

        $safeName = preg_replace('/[^\p{Arabic}\p{L}\p{N}_-]/u', '_', $customer->name);
        $filename = "سجل_زيارات_العميل_{$safeName}_" . date('Y-m-d') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($customer, $invoices) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // معلومات العميل الترويسية
            fputcsv($handle, ['تقرير كشف حساب وزيارات العميل: ' . $customer->name]);
            fputcsv($handle, ['رقم الهاتف:', $customer->phone ?: 'غير مسجل', 'العنوان:', $customer->address ?: 'غير مسجل']);
            fputcsv($handle, ['إجمالي الإنفاق:', number_format($customer->total_spent, 2) . ' ج.م', 'عدد الزيارات:', $customer->visits_count]);
            fputcsv($handle, []);

            // ترويسة الجدول
            fputcsv($handle, [
                '#',
                'رقم الفاتورة',
                'رقم الطلب',
                'التاريخ والوقت',
                'نوع الطلب / الطاولة',
                'طريقة الدفع',
                'الأصناف والطلبات',
                'الكاشير المسؤول',
                'المبلغ الإجمالي (ج.م)',
                'ملاحظات',
            ]);

            $grandTotal = 0.0;

            foreach ($invoices as $index => $inv) {
                $grandTotal += (float) $inv->total;

                $tableInfo = 'سفري / تيك أواي';
                if ($inv->order && $inv->order->table) {
                    $tableInfo = 'صالة - طاولة ' . ($inv->order->table->name ?? $inv->order->table->table_number);
                }

                $itemsText = $inv->items->map(function ($it) {
                    $mName = $it->menu->name ?? 'صنف';
                    return "{$mName} (x{$it->quantity})";
                })->implode(' ، ');

                fputcsv($handle, [
                    $index + 1,
                    $inv->invoice_number,
                    $inv->order ? '#' . $inv->order->order_number : '-',
                    $inv->created_at->format('Y-m-d h:i A'),
                    $tableInfo,
                    $inv->payment_method ?: 'كاش',
                    $itemsText ?: 'طلب صالة',
                    $inv->creator->name ?? 'غير معروف',
                    number_format($inv->total, 2, '.', ''),
                    $inv->note ?: '-',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, [
                'الإجمالي',
                'عدد الفواتير: ' . $invoices->count(),
                '-',
                '-',
                '-',
                '-',
                '-',
                'المجموع الكلي:',
                number_format($grandTotal, 2, '.', '') . ' ج.م',
                'سيستم UNO Cafe',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * بحث فوري عبر AJAX برقم الهاتف أو الاسم (للشاشات المنبثقة ومحاسبة الترابيزات)
     */
    public function ajaxSearch(Request $request): JsonResponse
    {
        $term = trim((string) ($request->input('phone') ?? $request->input('q') ?? ''));

        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $customers = Customer::where('phone', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->take(10)
            ->get()
            ->map(function ($c) {
                return [
                    'id'           => $c->id,
                    'name'         => $c->name,
                    'phone'        => $c->phone,
                    'address'      => $c->address,
                    'visits_count' => $c->visits_count,
                    'total_spent'  => number_format($c->total_spent, 2),
                    'vip_label'    => $c->vip_badge['label'],
                    'last_visit'   => $c->last_visit_date ? $c->last_visit_date->diffForHumans() : 'جديد',
                ];
            });

        return response()->json($customers);
    }

    /**
     * حفظ عميل جديد يدوياً
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50|unique:customers,phone',
            'address' => 'nullable|string|max:255',
            'email'   => 'nullable|email|max:255',
            'notes'   => 'nullable|string',
        ], [
            'name.required' => 'اسم العميل مطلوب.',
            'phone.unique'  => 'رقم الهاتف مسجل بالفعل لعميل آخر.',
        ]);

        $customer = Customer::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم إضافة العميل {$customer->name} بنجاح.",
                'data'    => $customer,
            ]);
        }

        return redirect()->route('customers.show', $customer->id)
            ->with('success', "تم إضافة العميل \"{$customer->name}\" بنجاح.");
    }

    /**
     * تحديث بيانات عميل
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50|unique:customers,phone,' . $customer->id,
            'address' => 'nullable|string|max:255',
            'email'   => 'nullable|email|max:255',
            'notes'   => 'nullable|string',
        ], [
            'name.required' => 'اسم العميل مطلوب.',
            'phone.unique'  => 'رقم الهاتف مسجل بالفعل لعميل آخر.',
        ]);

        $customer->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم تحديث بيانات العميل بنجاح.",
                'data'    => $customer,
            ]);
        }

        return redirect()->back()
            ->with('success', "تم تحديث بيانات العميل \"{$customer->name}\" بنجاح.");
    }

    /**
     * حذف عميل
     */
    public function destroy(Customer $customer)
    {
        // فك الارتباط بالفواتير والطلبات لضمان سلامة السجلات المالية
        Invoice::where('client_id', $customer->id)->update(['client_id' => null]);
        DB::table('orders')->where('customer_id', $customer->id)->update(['customer_id' => null]);

        $name = $customer->name;
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', "تم حذف العميل \"{$name}\" بنجاح مع الحفاظ على الفواتير في النظام.");
    }
}
