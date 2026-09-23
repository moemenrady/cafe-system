<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Shift;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * عرض لوحة التحكم الرئيسية للمدير (بيانات اليوم الحالي فقط)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // لو المستخدم ليس مديراً (موظف / كاشير / باريستا) -> يتم تحويله لصفحة البيع POS فقط
        if (!$user->isManager()) {
            return redirect()->route('pos.index');
        }

        $today = Carbon::today();

        // 1. إحصائيات المبيعات والفواتير لليوم فقط
        $todayInvoices = Invoice::whereDate('created_at', $today)->get();
        $todaySales = (float) $todayInvoices->sum('total');
        $todayInvoicesCount = $todayInvoices->count();

        $cashSales = 0.0;
        $cardSales = 0.0;
        $instapaySales = 0.0;

        foreach ($todayInvoices as $inv) {
            $pm = strtolower(trim((string) $inv->payment_method));
            $amt = (float) $inv->total;
            if ($pm === 'instapay') {
                $instapaySales += $amt;
            } elseif ($pm === 'card' || $pm === 'visa') {
                $cardSales += $amt;
            } else {
                $cashSales += $amt;
            }
        }

        // 2. مصروفات وصافي ربح اليوم
        $todayExpenses = (float) Expense::whereDate('expense_date', $today)->sum('amount');
        $todayExpensesCount = Expense::whereDate('expense_date', $today)->count();
        $todayNetProfit = round($todaySales - $todayExpenses, 2);

        // 3. الشيفتات والورديات اليوم
        $openShiftsCount = Shift::where('status', 'open')->count();
        $closedShiftsTodayCount = Shift::where('status', 'closed')->whereDate('end_time', $today)->count();

        // 4. حالة الطاولات في الصالة
        $activeTables = Table::active()->get();
        $totalTablesCount = $activeTables->count();
        $occupiedTablesCount = $activeTables->filter(fn($t) => $t->isOccupied())->count();

        // 5. الأصناف الأكثر مبيعاً اليوم (Top 5)
        $topProductsToday = InvoiceItem::whereHas('invoice', function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })
            ->select('menu_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_revenue'))
            ->groupBy('menu_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->with('menu:id,name,price,category_id')
            ->get();

        // 6. نواقص المخزن والتنبيهات اليوم
        $lowStockItems = InventoryItem::where(function ($q) {
                $q->whereColumn('quantity', '<=', 'reorder_level')
                  ->orWhere('quantity', '<=', 0);
            })
            ->orderBy('quantity', 'asc')
            ->take(5)
            ->get();
        $lowStockCount = InventoryItem::where(function ($q) {
                $q->whereColumn('quantity', '<=', 'reorder_level')
                  ->orWhere('quantity', '<=', 0);
            })->count();

        // 7. آخر عمليات وفواتير اليوم (Latest 10)
        $latestInvoices = Invoice::whereDate('created_at', $today)
            ->latest('id')
            ->take(10)
            ->with(['client', 'creator', 'items.menu', 'order.table'])
            ->get();

        return view('dashboard', compact(
            'todaySales',
            'todayInvoicesCount',
            'cashSales',
            'cardSales',
            'instapaySales',
            'todayExpenses',
            'todayExpensesCount',
            'todayNetProfit',
            'openShiftsCount',
            'closedShiftsTodayCount',
            'totalTablesCount',
            'occupiedTablesCount',
            'topProductsToday',
            'lowStockItems',
            'lowStockCount',
            'latestInvoices'
        ));
    }
}
