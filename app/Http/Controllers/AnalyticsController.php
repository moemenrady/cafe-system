<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Menu;
use App\Models\Order;
use App\Models\PurchaseInvoice;
use App\Models\Shift;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    /**
     * التحليل الشامل والذكاء التشغيلي لـ UNO Cafe
     * مستوحى من أفضل ممارسات التحليلات المتقدمة وشامل لجميع تفاصيل التشغيل
     */
    public function comprehensive(Request $request)
    {
        $period = $request->query('period', 'this_month');
        $now = Carbon::now();

        switch ($period) {
            case 'today':
                $from = $now->toDateString();
                $to = $now->toDateString();
                break;
            case 'yesterday':
                $from = $now->copy()->subDay()->toDateString();
                $to = $now->copy()->subDay()->toDateString();
                break;
            case 'this_week':
                $from = $now->copy()->startOfWeek()->toDateString();
                $to = $now->toDateString();
                break;
            case 'last_30_days':
                $from = $now->copy()->subDays(30)->toDateString();
                $to = $now->toDateString();
                break;
            case 'this_year':
                $from = $now->copy()->startOfYear()->toDateString();
                $to = $now->toDateString();
                break;
            case 'all':
                $from = null;
                $to = null;
                break;
            case 'custom':
            default:
                if ($request->filled('from') && $request->filled('to')) {
                    $from = $request->query('from');
                    $to = $request->query('to');
                    $period = 'custom';
                } else {
                    $period = 'this_month';
                    $from = $now->copy()->startOfMonth()->toDateString();
                    $to = $now->toDateString();
                }
                break;
        }

        $fromDateTime = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDateTime = $to ? Carbon::parse($to)->endOfDay() : null;
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        // ==========================================
        // 1️⃣ المبيعات والفواتير (Sales & Invoices)
        // ==========================================
        $invoicesQuery = Invoice::query()->where('status', '!=', 'cancelled');
        if ($fromDateTime && $toDateTime) {
            $invoicesQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
        }

        $allInvoices = (clone $invoicesQuery)->get();
        $totalRevenue = (float) $allInvoices->sum('total');
        $invoicesCount = $allInvoices->count();
        $totalDiscounts = (float) $allInvoices->sum('discount_amount');
        $totalTaxes = (float) $allInvoices->sum('tax_amount');
        $avgInvoiceValue = $invoicesCount > 0 ? round($totalRevenue / $invoicesCount, 2) : 0;

        // تفصيل طرق الدفع (Cash, Card/Visa, InstaPay)
        $cashSales = 0.0;
        $cardSales = 0.0;
        $instapaySales = 0.0;
        $otherSales = 0.0;

        foreach ($allInvoices as $inv) {
            $pm = strtolower(trim((string) $inv->payment_method));
            $amt = (float) $inv->total;
            if ($pm === 'instapay') {
                $instapaySales += $amt;
            } elseif (in_array($pm, ['card', 'visa', 'mastercard', 'pos_card'])) {
                $cardSales += $amt;
            } elseif ($pm === 'cash' || empty($pm)) {
                $cashSales += $amt;
            } else {
                $otherSales += $amt;
            }
        }

        // ==========================================
        // 2️⃣ المصروفات والتكاليف والأرباح (Expenses & Profit)
        // ==========================================
        $expensesQuery = Expense::query();
        if ($fromDateTime && $toDateTime) {
            $expensesQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
        }
        $allExpenses = (clone $expensesQuery)->with('category')->get();
        $totalExpenses = (float) $allExpenses->sum('amount');
        $expensesCount = $allExpenses->count();

        // توزيع المصروفات حسب التصنيف
        $expensesByCategory = [];
        foreach ($allExpenses as $exp) {
            $catName = $exp->category ? $exp->category->name : ($exp->title ?? 'نثريات عامة');
            if (!isset($expensesByCategory[$catName])) {
                $expensesByCategory[$catName] = 0.0;
            }
            $expensesByCategory[$catName] += (float) $exp->amount;
        }
        arsort($expensesByCategory);

        // احتساب تكلفة البضاعة المباعة (COGS) من بنود الفواتير والوصفات
        $invoiceItemsQuery = InvoiceItem::query();
        if ($fromDateTime && $toDateTime) {
            $invoiceItemsQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
        }
        $allInvoiceItems = (clone $invoiceItemsQuery)->with(['menu.recipes.inventoryItem', 'menu.category'])->get();

        // حساب تكلفة الخامات الفردية المباعة
        $cogs = 0.0;
        foreach ($allInvoiceItems as $item) {
            $qty = (float) $item->quantity;
            $unitCost = 0.0;
            if ($item->menu && $item->menu->recipes->count() > 0) {
                $unitCost = (float) $item->menu->cost;
            } else {
                // في حال عدم ربط وصفة، احتساب تكلفة تقديرية 35% من سعر الصنف
                $unitCost = (float) ($item->item_price * 0.35);
            }
            $cogs += ($unitCost * $qty);
        }

        $grossProfit = $totalRevenue - $cogs;
        $netProfit = $totalRevenue - ($cogs + $totalExpenses);
        $grossProfitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;
        $netProfitMargin = $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 1) : 0;

        // ==========================================
        // 3️⃣ فواتير الشراء والتوريدات (Purchases & Suppliers)
        // ==========================================
        $purchasesQuery = PurchaseInvoice::query()->where('status', '!=', 'cancelled');
        if ($fromDateTime && $toDateTime) {
            $purchasesQuery->whereBetween('invoice_date', [
                $fromDateTime->toDateString(),
                $toDateTime->toDateString(),
            ]);
        }
        $allPurchases = (clone $purchasesQuery)->with('supplier')->get();
        $totalPurchases = (float) $allPurchases->sum('net_amount');
        $purchasesPaid = (float) $allPurchases->sum('paid_amount');
        $purchasesRemaining = (float) $allPurchases->sum('remaining_amount');
        $purchasesCount = $allPurchases->count();

        // ==========================================
        // 4️⃣ تحليلات الأصناف والتصنيفات (Items & Categories)
        // ==========================================
        $itemsAggregated = [];
        $categoriesAggregated = [];

        foreach ($allInvoiceItems as $item) {
            $name = $item->menu ? $item->menu->name : 'صنف مجهول';
            $qty = (float) $item->quantity;
            $itemPrice = (float) $item->item_price;
            $subtotal = (float) ($item->total > 0 ? $item->total : ($itemPrice * $qty));
            $catName = ($item->menu && $item->menu->category) ? $item->menu->category->name : 'أصناف متنوعة';
            $unitCost = ($item->menu && $item->menu->recipes->count() > 0) ? (float) $item->menu->cost : (float) ($itemPrice * 0.35);
            $totalCost = $unitCost * $qty;
            $itemProfit = $subtotal - $totalCost;

            if (!isset($itemsAggregated[$name])) {
                $itemsAggregated[$name] = [
                    'name'         => $name,
                    'category'     => $catName,
                    'quantity'     => 0,
                    'revenue'      => 0.0,
                    'cost'         => 0.0,
                    'profit'       => 0.0,
                ];
            }
            $itemsAggregated[$name]['quantity'] += $qty;
            $itemsAggregated[$name]['revenue'] += $subtotal;
            $itemsAggregated[$name]['cost'] += $totalCost;
            $itemsAggregated[$name]['profit'] += $itemProfit;

            if (!isset($categoriesAggregated[$catName])) {
                $categoriesAggregated[$catName] = [
                    'name'     => $catName,
                    'quantity' => 0,
                    'revenue'  => 0.0,
                ];
            }
            $categoriesAggregated[$catName]['quantity'] += $qty;
            $categoriesAggregated[$catName]['revenue'] += $subtotal;
        }

        // ترتيب الأصناف بالأكثر مبيعاً
        uasort($itemsAggregated, fn($a, $b) => $b['revenue'] <=> $a['revenue']);
        $topProducts = array_slice($itemsAggregated, 0, 10, true);

        // ترتيب التصنيفات بالأعلى إيراداً
        uasort($categoriesAggregated, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        // ==========================================
        // 5️⃣ ساعات الذروة على مدار الـ 24 ساعة (Peak Hours)
        // ==========================================
        $peakHoursData = array_fill(0, 24, 0);
        $peakHoursRevenue = array_fill(0, 24, 0.0);

        foreach ($allInvoices as $inv) {
            $hr = (int) $inv->created_at->format('G');
            $peakHoursData[$hr]++;
            $peakHoursRevenue[$hr] += (float) $inv->total;
        }

        // ==========================================
        // 6️⃣ تحليلات الصالة والترابيزات (Tables & Orders)
        // ==========================================
        $ordersQuery = Order::query();
        if ($fromDateTime && $toDateTime) {
            $ordersQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
        }
        $allOrders = (clone $ordersQuery)->with('table')->get();
        $totalOrdersCount = $allOrders->count();

        $dineInCount = 0;
        $takeawayCount = 0;
        $deliveryCount = 0;
        $tableOrdersStats = [];

        foreach ($allOrders as $order) {
            $type = strtolower(trim((string) $order->order_type));
            if ($type === 'dine-in' || $order->table_id !== null) {
                $dineInCount++;
                $tblName = $order->table ? ($order->table->name ?? 'ترابيزة ' . $order->table->table_number) : 'صالة غير محددة';
                if (!isset($tableOrdersStats[$tblName])) {
                    $tableOrdersStats[$tblName] = ['count' => 0, 'revenue' => 0.0];
                }
                $tableOrdersStats[$tblName]['count']++;
                $tableOrdersStats[$tblName]['revenue'] += (float) $order->total;
            } elseif ($type === 'delivery') {
                $deliveryCount++;
            } else {
                $takeawayCount++;
            }
        }
        uasort($tableOrdersStats, fn($a, $b) => $b['count'] <=> $a['count']);

        $totalTables = Table::count();
        $busyTablesNow = Table::where('status', 'busy')->count();

        // ==========================================
        // 7️⃣ تحليلات الورديات والخزينة (Shifts Intelligence)
        // ==========================================
        $shiftsQuery = Shift::query();
        if ($fromDateTime && $toDateTime) {
            $shiftsQuery->whereBetween('created_at', [$fromDateTime, $toDateTime]);
        }
        $allShifts = (clone $shiftsQuery)->with('user')->latest()->get();
        $shiftsCount = $allShifts->count();
        $openShiftsCount = $allShifts->where('status', 'open')->count();
        $closedShiftsCount = $allShifts->where('status', 'closed')->count();
        $shiftsTotalSales = (float) $allShifts->sum('total_sales');
        $shiftsTotalExpenses = (float) $allShifts->sum('total_expenses');

        // ==========================================
        // 8️⃣ المسار الزمني اليومي للرسم البياني (Timeline Trend)
        // ==========================================
        $timelineDates = [];
        $timelineRevenue = [];
        $timelineExpenses = [];
        $timelineProfit = [];

        // إذا كانت الفترة محددة بيوم أو يومين، نعرض الساعات أو الأيام، وإلا نعرض الأيام
        $rangeStart = $fromDateTime ? $fromDateTime->copy()->startOfDay() : ($allInvoices->min('created_at') ?? Carbon::today()->subDays(14));
        $rangeEnd = $toDateTime ? $toDateTime->copy()->endOfDay() : Carbon::now();

        // حصر الأيام في الفترة المختارة
        $diffDays = $rangeStart->diffInDays($rangeEnd);
        if ($diffDays > 60) {
            // تجميع شهري في الفترات الكبيرة جداً
            $periodMonths = CarbonPeriod::create($rangeStart->startOfMonth(), '1 month', $rangeEnd->endOfMonth());
            foreach ($periodMonths as $date) {
                $mKey = $date->format('Y-m');
                $mLabel = $date->translatedFormat('M Y');
                $rev = $allInvoices->filter(fn($i) => $i->created_at->format('Y-m') === $mKey)->sum('total');
                $exp = $allExpenses->filter(fn($e) => $e->created_at->format('Y-m') === $mKey)->sum('amount');
                $prof = $rev - $exp;

                $timelineDates[] = $mLabel;
                $timelineRevenue[] = round((float) $rev, 2);
                $timelineExpenses[] = round((float) $exp, 2);
                $timelineProfit[] = round((float) $prof, 2);
            }
        } else {
            // تجميع يومي
            $periodDays = CarbonPeriod::create($rangeStart, $rangeEnd);
            foreach ($periodDays as $date) {
                $dKey = $date->toDateString();
                $dLabel = $date->translatedFormat('d M');
                $rev = $allInvoices->filter(fn($i) => $i->created_at->toDateString() === $dKey)->sum('total');
                $exp = $allExpenses->filter(fn($e) => $e->created_at->toDateString() === $dKey)->sum('amount');
                $prof = $rev - $exp;

                $timelineDates[] = $dLabel;
                $timelineRevenue[] = round((float) $rev, 2);
                $timelineExpenses[] = round((float) $exp, 2);
                $timelineProfit[] = round((float) $prof, 2);
            }
        }

        return view('analytics.comprehensive', compact(
            'period',
            'from',
            'to',
            'totalRevenue',
            'invoicesCount',
            'avgInvoiceValue',
            'totalDiscounts',
            'totalTaxes',
            'cashSales',
            'cardSales',
            'instapaySales',
            'otherSales',
            'totalExpenses',
            'expensesCount',
            'expensesByCategory',
            'cogs',
            'grossProfit',
            'netProfit',
            'grossProfitMargin',
            'netProfitMargin',
            'totalPurchases',
            'purchasesPaid',
            'purchasesRemaining',
            'purchasesCount',
            'topProducts',
            'categoriesAggregated',
            'itemsAggregated',
            'peakHoursData',
            'peakHoursRevenue',
            'totalOrdersCount',
            'dineInCount',
            'takeawayCount',
            'deliveryCount',
            'tableOrdersStats',
            'totalTables',
            'busyTablesNow',
            'shiftsCount',
            'openShiftsCount',
            'closedShiftsCount',
            'shiftsTotalSales',
            'shiftsTotalExpenses',
            'allShifts',
            'allInvoices',
            'timelineDates',
            'timelineRevenue',
            'timelineExpenses',
            'timelineProfit'
        ));
    }
}
