@extends('layouts.app')

@section('page_title', 'التحليل الشامل والذكاء التشغيلي')

@push('styles')
<style>
    .analytics-card {
        background: #ffffff;
        border: 1px solid rgba(229, 231, 235, 0.8);
        border-radius: 1.5rem;
        box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .analytics-card:hover {
        box-shadow: 0 10px 25px -3px rgba(0, 0, 0, 0.08);
    }
    .kpi-stat-card {
        position: relative;
        overflow: hidden;
        border-radius: 1.5rem;
        padding: 1.25rem 1.5rem;
        transition: all 0.25s ease;
    }
    .kpi-stat-card:hover {
        transform: translateY(-3px);
    }
    .filter-btn-active {
        background-color: #0284c7 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3) !important;
    }
    @media print {
        #sidebar, header, .no-print, #mobileOverlay {
            display: none !important;
        }
        main, body {
            background: #ffffff !important;
            overflow: visible !important;
            height: auto !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .analytics-card {
            box-shadow: none !important;
            border: 1px solid #d1d5db !important;
            break-inside: avoid;
        }
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" dir="rtl">

    {{-- 1. رأس الصفحة وشريط الفلاتر الزمنية --}}
    <div class="analytics-card p-5 sm:p-6 bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-white border-0 shadow-xl relative overflow-hidden">
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-sky-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute right-10 -top-10 w-48 h-48 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <div>
                <div class="flex items-center gap-2.5 mb-2">
                    <span class="px-3 py-1 rounded-full bg-sky-500/20 text-sky-300 text-xs font-bold border border-sky-500/30">
                        <i class="fa-solid fa-chart-pie ml-1"></i> الذكاء التشغيلي | UNO Analytics
                    </span>
                    <span class="text-xs text-gray-400">تحديث لحظي</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-wide">التحليل الشامل والأداء المالي والتشغيلي</h2>
                <p class="text-xs sm:text-sm text-gray-300 mt-1">
                    مؤشرات الأرباح الصافية، ساعات الذروة، مبيعات الأصناف، وكفاءة الصالة والخزينة
                </p>
            </div>

            {{-- أزرار الإجراءات السريعة (طباعة وتحديث) --}}
            <div class="flex items-center gap-2.5 self-start lg:self-center no-print">
                <button type="button" onclick="window.print()" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold transition flex items-center gap-2 border border-white/15 shadow-sm active:scale-95">
                    <i class="fa-solid fa-print text-amber-400"></i>
                    <span>طباعة التقرير</span>
                </button>
                <a href="{{ route('analytics.comprehensive') }}" class="px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-sky-600/30 active:scale-95">
                    <i class="fa-solid fa-rotate text-xs"></i>
                    <span>تحديث البيانات</span>
                </a>
            </div>
        </div>

        {{-- أزرار الفلترة الزمنية السريعة --}}
        <div class="mt-6 pt-5 border-t border-gray-700/80 flex flex-wrap items-center justify-between gap-3 no-print">
            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                <a href="{{ route('analytics.comprehensive', ['period' => 'today']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'today' ? 'filter-btn-active' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700' }}">
                   اليوم
                </a>
                <a href="{{ route('analytics.comprehensive', ['period' => 'yesterday']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'yesterday' ? 'filter-btn-active' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700' }}">
                   أمس
                </a>
                <a href="{{ route('analytics.comprehensive', ['period' => 'this_week']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'this_week' ? 'filter-btn-active' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700' }}">
                   هذا الأسبوع
                </a>
                <a href="{{ route('analytics.comprehensive', ['period' => 'this_month']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'this_month' ? 'filter-btn-active' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700' }}">
                   هذا الشهر
                </a>
                <a href="{{ route('analytics.comprehensive', ['period' => 'last_30_days']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'last_30_days' ? 'filter-btn-active' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700' }}">
                   آخر 30 يوم
                </a>
                <a href="{{ route('analytics.comprehensive', ['period' => 'this_year']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'this_year' ? 'filter-btn-active' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700' }}">
                   هذا العام
                </a>
                <a href="{{ route('analytics.comprehensive', ['period' => 'all']) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $period === 'all' ? 'filter-btn-active' : 'bg-gray-800 text-gray-300 hover:bg-gray-700 border border-gray-700' }}">
                   كل السجلات
                </a>
            </div>

            {{-- نموذج تحديد فترة مخصصة (Custom Range) --}}
            <form action="{{ route('analytics.comprehensive') }}" method="GET" class="flex items-center gap-2 text-xs">
                <input type="hidden" name="period" value="custom">
                <div class="flex items-center gap-1.5 bg-gray-800 px-3 py-1.5 rounded-xl border border-gray-700">
                    <span class="text-gray-400 font-bold">من:</span>
                    <input type="date" name="from" value="{{ $from ?? '' }}" class="bg-transparent text-white border-0 focus:ring-0 p-0 text-xs font-mono outline-none">
                </div>
                <div class="flex items-center gap-1.5 bg-gray-800 px-3 py-1.5 rounded-xl border border-gray-700">
                    <span class="text-gray-400 font-bold">إلى:</span>
                    <input type="date" name="to" value="{{ $to ?? '' }}" class="bg-transparent text-white border-0 focus:ring-0 p-0 text-xs font-mono outline-none">
                </div>
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold transition">
                    تطبيق
                </button>
            </form>
        </div>

        {{-- تفاصيل الفترة الحالية المختارة --}}
        <div class="mt-3 text-[11px] text-gray-400 flex items-center gap-2">
            <i class="fa-solid fa-calendar-check text-sky-400"></i>
            <span>الفترة المعروضة:</span>
            <strong class="text-gray-200">
                @if($from && $to)
                    من {{ \Carbon\Carbon::parse($from)->translatedFormat('d M Y') }} إلى {{ \Carbon\Carbon::parse($to)->translatedFormat('d M Y') }}
                @else
                    جميع السجلات المسجلة بالنظام
                @endif
            </strong>
        </div>
    </div>

    {{-- 2. بطاقات المؤشرات الرئيسية (Executive KPI Cards) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- كارت 1: إجمالي الإيرادات والمبيعات --}}
        <div class="kpi-stat-card bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-800">إجمالي المبيعات والإيرادات</span>
                <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-sm shadow-sm shadow-emerald-500/30">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl sm:text-3xl font-black text-emerald-950 font-mono tracking-tight">
                    {{ number_format($totalRevenue, 2) }} <span class="text-xs font-sans font-bold text-emerald-700">ج.م</span>
                </h3>
            </div>
            <div class="mt-2.5 pt-2 border-t border-emerald-200/60 flex items-center justify-between text-[11px] text-emerald-800">
                <span>{{ $invoicesCount }} فاتورة بيع</span>
                <span>متوسط الفاتورة: <strong>{{ number_format($avgInvoiceValue, 1) }} ج</strong></span>
            </div>
        </div>

        {{-- كارت 2: صافي الأرباح التشغيلية --}}
        <div class="kpi-stat-card bg-gradient-to-br from-sky-50 to-blue-50 border border-sky-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-sky-800">صافي الأرباح التقديري</span>
                <div class="w-10 h-10 rounded-2xl bg-sky-600 text-white flex items-center justify-center text-sm shadow-sm shadow-sky-600/30">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl sm:text-3xl font-black font-mono tracking-tight {{ $netProfit >= 0 ? 'text-sky-950' : 'text-rose-700' }}">
                    {{ number_format($netProfit, 2) }} <span class="text-xs font-sans font-bold text-sky-700">ج.م</span>
                </h3>
            </div>
            <div class="mt-2.5 pt-2 border-t border-sky-200/60 flex items-center justify-between text-[11px] text-sky-800">
                <span>هامش الربح الصافي:</span>
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black {{ $netProfitMargin >= 20 ? 'bg-emerald-200 text-emerald-900' : 'bg-sky-200 text-sky-900' }}">
                    %{{ $netProfitMargin }}
                </span>
            </div>
        </div>

        {{-- كارت 3: المصروفات وتكلفة البضاعة المباعة --}}
        <div class="kpi-stat-card bg-gradient-to-br from-rose-50 to-orange-50 border border-rose-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-rose-800">التكاليف والمصروفات</span>
                <div class="w-10 h-10 rounded-2xl bg-rose-500 text-white flex items-center justify-center text-sm shadow-sm shadow-rose-500/30">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl sm:text-3xl font-black text-rose-950 font-mono tracking-tight">
                    {{ number_format($totalExpenses + $cogs, 2) }} <span class="text-xs font-sans font-bold text-rose-700">ج.م</span>
                </h3>
            </div>
            <div class="mt-2.5 pt-2 border-t border-rose-200/60 flex items-center justify-between text-[11px] text-rose-800">
                <span>مصاريف: <strong>{{ number_format($totalExpenses, 0) }}ج</strong></span>
                <span>تكلفة خامات: <strong>{{ number_format($cogs, 0) }}ج</strong></span>
            </div>
        </div>

        {{-- كارت 4: فواتير الشراء والتوريدات --}}
        <div class="kpi-stat-card bg-gradient-to-br from-amber-50 to-yellow-50 border border-amber-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-amber-900">مشتريات وتوريدات المخزن</span>
                <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-sm shadow-sm shadow-amber-500/30">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
            </div>
            <div class="mt-3">
                <h3 class="text-2xl sm:text-3xl font-black text-amber-950 font-mono tracking-tight">
                    {{ number_format($totalPurchases, 2) }} <span class="text-xs font-sans font-bold text-amber-800">ج.م</span>
                </h3>
            </div>
            <div class="mt-2.5 pt-2 border-t border-amber-200/60 flex items-center justify-between text-[11px] text-amber-900">
                <span>{{ $purchasesCount }} فاتورة شراء</span>
                <span>مستحق للموردين: <strong>{{ number_format($purchasesRemaining, 0) }}ج</strong></span>
            </div>
        </div>

    </div>

    {{-- 3. قنوات الدفع وإحصائيات العمليات السريعة --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="analytics-card p-4 flex items-center gap-4 bg-emerald-500/5 border-emerald-200">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-xl shrink-0 shadow-sm">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-xs text-gray-500 font-bold block">مبيعات الكاش (نقدي)</span>
                <strong class="text-lg text-emerald-900 font-mono font-black">{{ number_format($cashSales, 2) }} ج.م</strong>
                <span class="text-[10px] text-emerald-600 block mt-0.5">
                    %{{ $totalRevenue > 0 ? round(($cashSales / $totalRevenue) * 100, 1) : 0 }} من الإجمالي
                </span>
            </div>
        </div>

        <div class="analytics-card p-4 flex items-center gap-4 bg-sky-500/5 border-sky-200">
            <div class="w-12 h-12 rounded-2xl bg-sky-600 text-white flex items-center justify-center text-xl shrink-0 shadow-sm">
                <i class="fa-solid fa-credit-card"></i>
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-xs text-gray-500 font-bold block">مبيعات البطاقات (فيزا / ماستركارد)</span>
                <strong class="text-lg text-sky-900 font-mono font-black">{{ number_format($cardSales, 2) }} ج.م</strong>
                <span class="text-[10px] text-sky-600 block mt-0.5">
                    %{{ $totalRevenue > 0 ? round(($cardSales / $totalRevenue) * 100, 1) : 0 }} من الإجمالي
                </span>
            </div>
        </div>

        <div class="analytics-card p-4 flex items-center gap-4 bg-purple-500/5 border-purple-200">
            <div class="w-12 h-12 rounded-2xl bg-purple-600 text-white flex items-center justify-center text-xl shrink-0 shadow-sm">
                <i class="fa-solid fa-mobile-screen-button"></i>
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-xs text-gray-500 font-bold block">مبيعات إنستاباي (InstaPay)</span>
                <strong class="text-lg text-purple-900 font-mono font-black">{{ number_format($instapaySales, 2) }} ج.م</strong>
                <span class="text-[10px] text-purple-600 block mt-0.5">
                    %{{ $totalRevenue > 0 ? round(($instapaySales / $totalRevenue) * 100, 1) : 0 }} من الإجمالي
                </span>
            </div>
        </div>
    </div>

    {{-- 4. الرسوم البيانية التفاعلية (Interactive Charts) --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- الرسم البياني 1: المسار الزمني للإيرادات وصافي الأرباح (2 أعمدة) --}}
        <div class="analytics-card p-5 sm:p-6 lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-sm sm:text-base text-gray-800">مسار الإيرادات وصافي الأرباح</h4>
                        <p class="text-[11px] text-gray-400">تتبع يومي/شهري للإيراد، المصروفات، وصافي الربح في الفترة</p>
                    </div>
                </div>
            </div>
            <div class="h-72 w-full">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>

        {{-- الرسم البياني 2: قنوات الدفع (Doughnut Chart) --}}
        <div class="analytics-card p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-sm sm:text-base text-gray-800">توزيع طرق الدفع</h4>
                        <p class="text-[11px] text-gray-400">نسبة النقد مقابل البطاقات وإنستاباي</p>
                    </div>
                </div>
            </div>
            <div class="h-72 w-full flex items-center justify-center">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>

    </div>

    {{-- 5. الرسوم البيانية: ساعات الذروة وتصنيفات المنيو --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- الرسم البياني 3: ساعات الذروة (Peak Hours 0-23h) --}}
        <div class="analytics-card p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-sm sm:text-base text-gray-800">ساعات الذروة والنشاط على مدار اليوم</h4>
                        <p class="text-[11px] text-gray-400">عدد الفواتير الصادرة حسب ساعات اليوم (00:00 - 23:00)</p>
                    </div>
                </div>
            </div>
            <div class="h-64 w-full">
                <canvas id="peakHoursChart"></canvas>
            </div>
        </div>

        {{-- الرسم البياني 4: توزيع مبيعات أقسام المنيو --}}
        <div class="analytics-card p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-layer-group"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-sm sm:text-base text-gray-800">مبيعات أقسام المنيو</h4>
                        <p class="text-[11px] text-gray-400">إيرادات كل قسم مقارنة بإجمالي مبيعات الكافيه</p>
                    </div>
                </div>
            </div>
            <div class="h-64 w-full flex items-center justify-center">
                <canvas id="categoriesChart"></canvas>
            </div>
        </div>

    </div>

    {{-- 6. جدول الأصناف الأكثر مبيعاً والأعلى ربحية --}}
    <div class="analytics-card p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-base font-bold shadow-xs">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <h3 class="font-black text-base sm:text-lg text-gray-800">الأصناف الأكثر طلباً وربحية (Top 10 Menu Items)</h3>
                    <p class="text-xs text-gray-400 mt-0.5">ترتيب الأصناف بحسب الإيرادات المحققة، الكميات المباعة، ومساهمتها في الأرباح</p>
                </div>
            </div>
            <span class="text-xs font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-xl font-mono">
                {{ count($topProducts) }} أصناف متصدرة
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="border-b border-gray-100 text-gray-400 font-bold bg-gray-50/60">
                        <th class="py-3 px-3.5 rounded-r-xl">#</th>
                        <th class="py-3 px-3.5">اسم الصنف</th>
                        <th class="py-3 px-3.5">القسم</th>
                        <th class="py-3 px-3.5 text-center">الكمية المباعة</th>
                        <th class="py-3 px-3.5">إجمالي الإيراد</th>
                        <th class="py-3 px-3.5">التكلفة التقديرية</th>
                        <th class="py-3 px-3.5">صافي الربح</th>
                        <th class="py-3 px-3.5 rounded-l-xl text-center">الهامش الربحي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($topProducts as $item)
                        @php
                            $margin = $item['revenue'] > 0 ? round(($item['profit'] / $item['revenue']) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-3.5 font-bold font-mono text-gray-400">{{ $loop->iteration }}</td>
                            <td class="py-3 px-3.5 font-black text-gray-800 text-sm flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                {{ $item['name'] }}
                            </td>
                            <td class="py-3 px-3.5 text-gray-500 font-medium">{{ $item['category'] }}</td>
                            <td class="py-3 px-3.5 text-center font-bold font-mono text-sky-700 bg-sky-50/50 rounded-lg">{{ $item['quantity'] }}</td>
                            <td class="py-3 px-3.5 font-bold font-mono text-emerald-700">{{ number_format($item['revenue'], 2) }} ج</td>
                            <td class="py-3 px-3.5 font-mono text-gray-400">{{ number_format($item['cost'], 2) }} ج</td>
                            <td class="py-3 px-3.5 font-bold font-mono text-sky-800">{{ number_format($item['profit'], 2) }} ج</td>
                            <td class="py-3 px-3.5 text-center">
                                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black {{ $margin >= 50 ? 'bg-emerald-100 text-emerald-800' : ($margin >= 25 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800') }}">
                                    %{{ $margin }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-400 font-bold">
                                لا توجد مبيعات مسجلة في هذه الفترة المحددة
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- 7. أداء الورديات (Shifts) وتحليلات الصالة --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- تفاصيل كفاءة الورديات --}}
        <div class="analytics-card p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-user-clock"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-sm sm:text-base text-gray-800">إحصائيات ورديات العمل</h4>
                        <p class="text-[11px] text-gray-400">إجمالي الورديات ومبيعات كل وردية في الفترة</p>
                    </div>
                </div>
                <span class="text-xs font-bold text-gray-500 font-mono">{{ $shiftsCount }} وردية</span>
            </div>

            <div class="overflow-y-auto max-h-72">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 font-bold bg-gray-50/60">
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">الكاشير / الموظف</th>
                            <th class="py-2.5 px-3">البداية</th>
                            <th class="py-2.5 px-3">مبيعات الوردية</th>
                            <th class="py-2.5 px-3 text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse($allShifts->take(8) as $shift)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-2.5 px-3 font-mono text-gray-400">#{{ $shift->id }}</td>
                                <td class="py-2.5 px-3 font-bold text-gray-800">{{ $shift->user ? $shift->user->name : 'غير محدد' }}</td>
                                <td class="py-2.5 px-3 text-gray-500 font-mono text-[11px]">{{ $shift->created_at ? $shift->created_at->format('d/m H:i') : '' }}</td>
                                <td class="py-2.5 px-3 font-bold font-mono text-emerald-700">{{ number_format($shift->totalSales(), 2) }} ج</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold {{ $shift->status === 'open' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $shift->status === 'open' ? 'مفتوحة' : 'مغلقة' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400 font-medium">لا توجد ورديات في الفترة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- تفاصيل الصالة والترابيزات --}}
        <div class="analytics-card p-5 sm:p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-chair"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-sm sm:text-base text-gray-800">إشغال الصالة والترابيزات الأكثر نشاطاً</h4>
                        <p class="text-[11px] text-gray-400">تحليل طلبات الصالة مقابل التيك أواي وتوزيع الترابيزات</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 text-center text-xs">
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px]">طلبات صالة</span>
                    <strong class="text-base text-gray-800 font-mono font-black">{{ $dineInCount }}</strong>
                </div>
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px]">تيك أواي سفري</span>
                    <strong class="text-base text-gray-800 font-mono font-black">{{ $takeawayCount }}</strong>
                </div>
                <div class="bg-gray-50 p-2.5 rounded-xl border border-gray-100">
                    <span class="text-gray-400 block text-[10px]">دليفري وتوصيل</span>
                    <strong class="text-base text-gray-800 font-mono font-black">{{ $deliveryCount }}</strong>
                </div>
            </div>

            <div class="overflow-y-auto max-h-56">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="border-b border-gray-100 text-gray-400 font-bold bg-gray-50/60">
                            <th class="py-2.5 px-3">الترابيزة</th>
                            <th class="py-2.5 px-3 text-center">عدد الطلبات</th>
                            <th class="py-2.5 px-3">إجمالي الإيراد</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        @forelse(array_slice($tableOrdersStats, 0, 6, true) as $tName => $tStat)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="py-2.5 px-3 font-bold text-gray-800 flex items-center gap-2">
                                    <i class="fa-solid fa-mug-saucer text-amber-500 text-xs"></i>
                                    {{ $tName }}
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono font-bold">{{ $tStat['count'] }}</td>
                                <td class="py-2.5 px-3 font-bold font-mono text-emerald-700">{{ number_format($tStat['revenue'], 2) }} ج</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center py-6 text-gray-400 font-medium">لا توجد طلبات ترابيزات في هذه الفترة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

{{-- سكريبت تهيئة الرسوم البيانية التفاعلية عبر Chart.js --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1️⃣ رسم المسار الزمني (Timeline Chart)
    const timelineCtx = document.getElementById('timelineChart');
    if (timelineCtx) {
        new Chart(timelineCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode($timelineDates) !!},
                datasets: [
                    {
                        label: 'الإيرادات (المبيعات)',
                        data: {!! json_encode($timelineRevenue) !!},
                        borderColor: '#0284c7',
                        backgroundColor: 'rgba(2, 132, 199, 0.08)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 3,
                    },
                    {
                        label: 'المصروفات',
                        data: {!! json_encode($timelineExpenses) !!},
                        borderColor: '#f43f5e',
                        backgroundColor: 'transparent',
                        borderDash: [5, 5],
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2,
                    },
                    {
                        label: 'صافي الربح',
                        data: {!! json_encode($timelineProfit) !!},
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        tension: 0.35,
                        pointRadius: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', rtl: true, labels: { font: { family: 'inherit', size: 11, weight: 'bold' } } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: { font: { family: 'inherit', size: 10 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'inherit', size: 10 } }
                    }
                }
            }
        });
    }

    // 2️⃣ رسم قنوات الدفع (Payment Methods Chart)
    const paymentCtx = document.getElementById('paymentMethodsChart');
    if (paymentCtx) {
        const cash = {{ (float) $cashSales }};
        const card = {{ (float) $cardSales }};
        const instapay = {{ (float) $instapaySales }};
        const other = {{ (float) $otherSales }};

        new Chart(paymentCtx, {
            type: 'doughnut',
            data: {
                labels: ['نقدي (كاش)', 'بطاقة / فيزا', 'إنستاباي (InstaPay)', 'أخرى'],
                datasets: [{
                    data: [cash, card, instapay, other],
                    backgroundColor: ['#10b981', '#0284c7', '#a855f7', '#94a3b8'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', rtl: true, labels: { font: { family: 'inherit', size: 11, weight: 'bold' } } }
                },
                cutout: '65%'
            }
        });
    }

    // 3️⃣ رسم ساعات الذروة (Peak Hours Chart)
    const peakCtx = document.getElementById('peakHoursChart');
    if (peakCtx) {
        const hoursLabels = Array.from({length: 24}, (_, i) => `${i}:00`);
        const peakCounts = {!! json_encode(array_values($peakHoursData)) !!};

        new Chart(peakCtx, {
            type: 'bar',
            data: {
                labels: hoursLabels,
                datasets: [{
                    label: 'عدد الفواتير والطلبات',
                    data: peakCounts,
                    backgroundColor: '#f59e0b',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { family: 'inherit', size: 10 } },
                        grid: { color: 'rgba(0, 0, 0, 0.05)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'inherit', size: 9 } }
                    }
                }
            }
        });
    }

    // 4️⃣ رسم تصنيفات المنيو (Categories Chart)
    const catCtx = document.getElementById('categoriesChart');
    if (catCtx) {
        const catLabels = {!! json_encode(array_keys($categoriesAggregated)) !!};
        const catRevenues = {!! json_encode(array_column($categoriesAggregated, 'revenue')) !!};
        const colors = ['#f59e0b', '#0284c7', '#10b981', '#ec4899', '#8b5cf6', '#14b8a6', '#f97316', '#64748b'];

        new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels.length ? catLabels : ['لا توجد مبيعات'],
                datasets: [{
                    data: catRevenues.length ? catRevenues : [1],
                    backgroundColor: colors.slice(0, Math.max(catLabels.length, 1)),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', rtl: true, labels: { font: { family: 'inherit', size: 10, weight: 'bold' } } }
                },
                cutout: '60%'
            }
        });
    }
});
</script>
@endsection
