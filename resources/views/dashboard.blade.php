@extends('layouts.app')

@section('title', 'لوحة تحكم المدير - إحصائيات اليوم')
@section('page_title', 'لوحة التحكم اليومية (مبيعات وعمليات اليوم)')

@section('content')
<div class="space-y-6 animate-slide-in" dir="rtl">

    {{-- ==================== 1. الترويسة والتنبيه اليومي ==================== --}}
    <div class="bg-gradient-to-r from-gray-900 via-gray-800 to-indigo-950 text-white rounded-3xl p-5 sm:p-6 shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>تقرير اليوم المباشر</span>
                </span>
                <span class="text-xs text-gray-300 font-mono">
                    {{ now()->translatedFormat('l، d F Y') }}
                </span>
            </div>
            <h2 class="text-lg sm:text-2xl font-black text-white">ملخص أداء وحسابات اليوم في النظام</h2>
            <p class="text-xs text-gray-300 mt-1">جميع الأرقام والإحصائيات أدناه مقتصرة وحصرية على مبيعات وعمليات اليوم الحالي فقط.</p>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            <a href="{{ route('shifts.index') }}" class="px-4 py-2.5 rounded-2xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs flex items-center gap-2 transition backdrop-blur-sm border border-white/10">
                <i class="fa-solid fa-clock text-amber-400"></i>
                <span>مراقبة الشيفتات</span>
            </a>
            <a href="{{ route('pos.index') }}" class="px-4 py-2.5 rounded-2xl bg-emerald-500 hover:bg-emerald-600 text-white font-black text-xs flex items-center gap-2 transition shadow-lg shadow-emerald-500/30">
                <i class="fa-solid fa-cart-shopping"></i>
                <span>صفحة البيع POS</span>
            </a>
        </div>
    </div>

    {{-- ==================== 2. كروت الـ KPI الرئيسية اليومية ==================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        {{-- إجمالي مبيعات اليوم --}}
        <div class="bg-white p-5 rounded-3xl border border-emerald-100 bg-emerald-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-emerald-800 text-xs font-bold mb-2">
                <span>إجمالي مبيعات اليوم</span>
                <div class="w-9 h-9 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-wave text-base"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 font-mono leading-tight">
                    {{ number_format($todaySales, 2) }} <span class="text-xs text-emerald-500 font-sans">ج.م</span>
                </p>
                <div class="text-[10px] text-gray-500 font-mono mt-2 pt-2 border-t border-emerald-100/60 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span>كاش: <b>{{ number_format($cashSales, 0) }}</b></span>
                    <span class="text-gray-300">|</span>
                    <span>فيزا: <b>{{ number_format($cardSales, 0) }}</b></span>
                    <span class="text-gray-300">|</span>
                    <span class="text-blue-600 font-bold">إنستا: <b>{{ number_format($instapaySales, 0) }}</b></span>
                </div>
            </div>
        </div>

        {{-- صافي دخل اليوم --}}
        <div class="bg-white p-5 rounded-3xl border border-blue-100 bg-blue-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-blue-800 text-xs font-bold mb-2">
                <span>صافي ربح اليوم</span>
                <div class="w-9 h-9 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-chart-line text-base"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-blue-600 font-mono leading-tight">
                    {{ number_format($todayNetProfit, 2) }} <span class="text-xs text-blue-500 font-sans">ج.م</span>
                </p>
                <p class="text-[10px] text-gray-400 mt-2 pt-2 border-t border-blue-100/60">
                    المبيعات اليومية ({{ number_format($todaySales, 0) }}) - المصروفات ({{ number_format($todayExpenses, 0) }})
                </p>
            </div>
        </div>

        {{-- مصروفات اليوم --}}
        <div class="bg-white p-5 rounded-3xl border border-rose-100 bg-rose-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-rose-800 text-xs font-bold mb-2">
                <span>مصروفات اليوم</span>
                <div class="w-9 h-9 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center">
                    <i class="fa-solid fa-receipt text-base"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-rose-600 font-mono leading-tight">
                    {{ number_format($todayExpenses, 2) }} <span class="text-xs text-rose-500 font-sans">ج.م</span>
                </p>
                <p class="text-[10px] text-gray-400 mt-2 pt-2 border-t border-rose-100/60">
                    عدد سندات المصروفات المسجلة اليوم: <b>{{ $todayExpensesCount }}</b>
                </p>
            </div>
        </div>

        {{-- عدد فواتير اليوم --}}
        <div class="bg-white p-5 rounded-3xl border border-indigo-100 bg-indigo-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-indigo-800 text-xs font-bold mb-2">
                <span>فواتير اليوم</span>
                <div class="w-9 h-9 rounded-2xl bg-indigo-100 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-file-invoice-dollar text-base"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl sm:text-3xl font-black text-indigo-700 font-mono leading-tight">
                    {{ $todayInvoicesCount }} <span class="text-xs text-indigo-400 font-sans">فاتورة</span>
                </p>
                <p class="text-[10px] text-gray-400 mt-2 pt-2 border-t border-indigo-100/60">
                    معدل الفاتورة: <b>{{ $todayInvoicesCount > 0 ? number_format($todaySales / $todayInvoicesCount, 2) : 0 }} ج.م</b>
                </p>
            </div>
        </div>
    </div>

    {{-- ==================== 3. حالة القطاعات اليومية (الشيفتات، الصالة، المخزن) ==================== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        {{-- كارت الشيفتات اليوم --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <span class="text-xs font-black text-gray-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-user-clock text-amber-500"></i>
                    <span>الورديات والشيفتات اليوم</span>
                </span>
                <a href="{{ route('shifts.index') }}" class="text-[11px] font-bold text-amber-600 hover:underline">عرض الكل &larr;</a>
            </div>
            <div class="grid grid-cols-2 gap-2 text-center text-xs">
                <div class="bg-emerald-50 p-3 rounded-2xl border border-emerald-100">
                    <span class="text-emerald-700 font-bold block text-[10px]">شغال حالياً</span>
                    <strong class="text-xl font-black text-emerald-600 font-mono">{{ $openShiftsCount }}</strong>
                </div>
                <div class="bg-gray-50 p-3 rounded-2xl border border-gray-100">
                    <span class="text-gray-500 font-bold block text-[10px]">مغلقة اليوم</span>
                    <strong class="text-xl font-black text-gray-700 font-mono">{{ $closedShiftsTodayCount }}</strong>
                </div>
            </div>
        </div>

        {{-- كارت حالة الترابيزات والصالة --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <span class="text-xs font-black text-gray-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-chair text-sky-500"></i>
                    <span>إشغال الصالة والترابيزات</span>
                </span>
                <a href="{{ route('tables.index') }}" class="text-[11px] font-bold text-sky-600 hover:underline">إدارة الطاولات &larr;</a>
            </div>
            <div class="grid grid-cols-2 gap-2 text-center text-xs">
                <div class="bg-rose-50 p-3 rounded-2xl border border-rose-100">
                    <span class="text-rose-700 font-bold block text-[10px]">مشغولة الآن</span>
                    <strong class="text-xl font-black text-rose-600 font-mono">{{ $occupiedTablesCount }}</strong>
                </div>
                <div class="bg-sky-50 p-3 rounded-2xl border border-sky-100">
                    <span class="text-sky-700 font-bold block text-[10px]">إجمالي الطاولات</span>
                    <strong class="text-xl font-black text-sky-600 font-mono">{{ $totalTablesCount }}</strong>
                </div>
            </div>
        </div>

        {{-- كارت نواقص المخزون --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-3">
            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                <span class="text-xs font-black text-gray-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500"></i>
                    <span>نواقص المخزون اليوم</span>
                </span>
                <a href="{{ route('inventory.daily_tracking') }}" class="text-[11px] font-bold text-rose-600 hover:underline">متابعة المخزون &larr;</a>
            </div>
            <div class="flex items-center justify-between bg-rose-50/60 p-3 rounded-2xl border border-rose-100 text-xs">
                <div>
                    <span class="text-rose-800 font-bold block">أصناف تجاوزت حد الأمان</span>
                    <span class="text-[10px] text-rose-600">بحاجة لإعادة طلب فوري</span>
                </div>
                <strong class="text-2xl font-black text-rose-600 font-mono">{{ $lowStockCount }}</strong>
            </div>
        </div>

    </div>

    {{-- ==================== 4. الأكثر مبيعاً وتوزيع طرق الدفع ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- المنتجات الأكثر مبيعاً اليوم (Top 5) --}}
        <div class="lg:col-span-2 bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-gray-800">المنتجات الأكثر مبيعاً اليوم</h3>
                    <p class="text-xs text-gray-400 mt-0.5">أعلى الأصناف طلباً ومبيعاً خلال ورديات اليوم</p>
                </div>
                <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-200">
                    أعلى 5 أصناف
                </span>
            </div>

            @if($topProductsToday->isEmpty())
                <div class="p-10 text-center text-gray-400 space-y-2">
                    <i class="fa-solid fa-mug-hot text-3xl text-gray-300"></i>
                    <p class="text-xs font-bold text-gray-500">لا توجد مبيعات مسجلة حتى الآن اليوم</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-right text-xs">
                        <thead class="bg-gray-50/70 text-gray-400 text-[11px] font-black border-b border-gray-100">
                            <tr>
                                <th class="py-3 px-4">#</th>
                                <th class="py-3 px-4">اسم الصنف</th>
                                <th class="py-3 px-4 text-center">الكمية المباعة اليوم</th>
                                <th class="py-3 px-4">إجمالي مبيعاته اليوم</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($topProductsToday as $index => $item)
                                <tr class="hover:bg-gray-50/80 transition">
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-400">
                                        #{{ $index + 1 }}
                                    </td>
                                    <td class="py-3.5 px-4 font-black text-gray-800">
                                        {{ $item->menu->name ?? 'صنف محذوف' }}
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <span class="px-2.5 py-1 rounded-xl bg-emerald-50 text-emerald-700 font-mono font-black border border-emerald-200">
                                            {{ (int) $item->total_qty }} قطعة
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-black text-gray-900">
                                        {{ number_format($item->total_revenue, 2) }} ج.م
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- نسبة طرق الدفع اليوم ونواقص المخزون --}}
        <div class="space-y-6">
            
            {{-- توزيع مبيعات طرق الدفع --}}
            <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-4">
                <h3 class="text-xs sm:text-sm font-black text-gray-800 border-b border-gray-100 pb-2 flex items-center justify-between">
                    <span>توزيع مبيعات اليوم حسب طريقة الدفع</span>
                    <i class="fa-solid fa-pie-chart text-indigo-500"></i>
                </h3>

                @php
                    $salesSum = max(0.01, $todaySales);
                    $cashPct = round(($cashSales / $salesSum) * 100, 1);
                    $cardPct = round(($cardSales / $salesSum) * 100, 1);
                    $instaPct = round(($instapaySales / $salesSum) * 100, 1);
                @endphp

                <div class="space-y-3 text-xs">
                    {{-- كاش --}}
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span class="text-emerald-700 flex items-center gap-1"><i class="fa-solid fa-money-bill-wave"></i> كاش:</span>
                            <span class="font-mono">{{ number_format($cashSales, 2) }} ج.م ({{ $cashPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-emerald-500 rounded-full" style="width: {{ $cashPct }}%"></div>
                        </div>
                    </div>

                    {{-- فيزا --}}
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span class="text-purple-700 flex items-center gap-1"><i class="fa-solid fa-credit-card"></i> فيزا / كارت:</span>
                            <span class="font-mono">{{ number_format($cardSales, 2) }} ج.م ({{ $cardPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full" style="width: {{ $cardPct }}%"></div>
                        </div>
                    </div>

                    {{-- إنستا باي --}}
                    <div>
                        <div class="flex justify-between font-bold mb-1">
                            <span class="text-blue-700 flex items-center gap-1"><i class="fa-solid fa-mobile-screen-button"></i> إنستا باي:</span>
                            <span class="font-mono">{{ number_format($instapaySales, 2) }} ج.م ({{ $instaPct }}%)</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full" style="width: {{ $instaPct }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- الأصناف التي تحت حد الأمان --}}
            @if($lowStockItems->isNotEmpty())
                <div class="bg-rose-50/70 p-5 rounded-3xl border border-rose-200 shadow-xs space-y-3 text-xs text-rose-900">
                    <div class="flex items-center justify-between border-b border-rose-200/60 pb-2">
                        <strong class="font-black flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                            <span>تنبيه نواقص المخزون الفورية</span>
                        </strong>
                    </div>
                    <ul class="space-y-1.5">
                        @foreach($lowStockItems as $lItem)
                            <li class="flex items-center justify-between bg-white/80 px-3 py-2 rounded-xl border border-rose-100 font-bold">
                                <span>{{ $lItem->name }}</span>
                                <span class="font-mono text-rose-600 font-black">{{ $lItem->quantity }} {{ $lItem->unit }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

        </div>

    </div>

    {{-- ==================== 5. آخر العمليات والفواتير اليوم ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm sm:text-base font-black text-gray-800">سجل فواتير وعمليات اليوم المباشرة</h3>
                <p class="text-xs text-gray-400 mt-0.5">أحدث 10 فواتير مبيعات تم إصدارها اليوم عبر النظام</p>
            </div>
            <a href="{{ route('sales-invoices.index') }}" class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                عرض كافة الفواتير &larr;
            </a>
        </div>

        @if($latestInvoices->isEmpty())
            <div class="p-12 text-center text-gray-400 space-y-2">
                <i class="fa-regular fa-folder-open text-4xl text-gray-300"></i>
                <p class="font-bold text-gray-600">لا توجد فواتير مبيعات مسجلة اليوم حتى الآن</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] font-black border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4">رقم الفاتورة</th>
                            <th class="py-3 px-4">الوقت</th>
                            <th class="py-3 px-4">العميل / الطاولة</th>
                            <th class="py-3 px-4">طريقة الدفع</th>
                            <th class="py-3 px-4">المبلغ الإجمالي</th>
                            <th class="py-3 px-4">المسؤول / الكاشير</th>
                            <th class="py-3 px-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($latestInvoices as $inv)
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-800">
                                    #{{ $inv->invoice_number }}
                                </td>
                                <td class="py-3.5 px-4 font-mono text-gray-500">
                                    {{ $inv->created_at->format('h:i A') }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-gray-800">
                                    @if($inv->client)
                                        {{ $inv->client->name }}
                                    @elseif($inv->order && $inv->order->table)
                                        {{ $inv->order->table->name ?? ('طاولة ' . $inv->order->table->table_number) }}
                                    @else
                                        عميل نقدي / سفري
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @php
                                        $pm = strtolower($inv->payment_method ?? '');
                                    @endphp
                                    @if($pm === 'instapay')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                                            <i class="fa-solid fa-mobile-screen-button text-[9px]"></i> إنستا باي
                                        </span>
                                    @elseif($pm === 'card' || $pm === 'visa')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 font-mono">
                                            <i class="fa-solid fa-credit-card text-[9px]"></i> فيزا
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                            <i class="fa-solid fa-money-bill-wave text-[9px]"></i> كاش
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-mono font-black text-emerald-600 text-sm">
                                    {{ number_format($inv->total, 2) }} ج.م
                                </td>
                                <td class="py-3.5 px-4 font-bold text-gray-700">
                                    {{ $inv->creator->name ?? 'غير معروف' }}
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <a href="{{ route('sales-invoices.show', $inv->id) }}" target="_blank"
                                        class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-sky-500 hover:text-white text-gray-700 font-bold text-[11px] inline-flex items-center gap-1 transition">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                        <span>عرض</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection