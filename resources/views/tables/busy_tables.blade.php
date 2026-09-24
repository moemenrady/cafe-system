@extends('layouts.app')

@section('title', 'الترابيزات المشغولة والصالة')
@section('page_title', 'الترابيزات المشغولة والصالة')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12" dir="rtl">

    {{-- ==================== ترويسة الصفحة والإجراءات السريعة ==================== --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-amber-500 to-orange-500 text-white flex items-center justify-center text-xl shadow-md shadow-orange-500/20 shrink-0">
                <i class="fa-solid fa-bell-concierge"></i>
            </div>
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-lg font-black text-gray-900">الترابيزات المشغولة وحالة الصالة</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-orange-100 text-orange-700 border border-orange-200 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-orange-500 animate-ping"></span>
                        <span>{{ $busyCount }} مشغولة حالياً</span>
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">متابعة فورية ومباشرة لحالة جميع طاولات الصالة والطلبات المفتوحة وسداد الفواتير</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <button type="button" onclick="window.location.reload()"
                class="px-3.5 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-rotate text-gray-500"></i>
                <span>تحديث</span>
            </button>
            <a href="{{ route('pos.index') }}"
                class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center justify-center gap-2 shadow-md shadow-blue-600/20">
                <i class="fa-solid fa-cash-register"></i>
                <span>فتح نقطة البيع (POS)</span>
            </a>
        </div>
    </div>

    {{-- ==================== بطاقات الإحصائيات الحية (KPI Cards) ==================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- 1. الطاولات المشغولة --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-orange-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500">طاولات مشغولة</span>
                <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 flex items-center justify-center text-sm font-black">
                    <i class="fa-solid fa-chair"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-orange-600 font-mono">{{ $busyCount }}</span>
                <span class="text-xs font-bold text-gray-400">/ {{ $totalCount }} طاولة</span>
            </div>
            <div class="mt-2 text-[11px] font-bold text-gray-500 flex items-center gap-1 truncate">
                <span>المبالغ المفتوحة:</span>
                <span class="text-orange-700 font-mono font-black">{{ number_format($totalUnpaidAmount, 2) }} ج.م</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-orange-400 to-amber-500"></div>
        </div>

        {{-- 2. الطاولات الشاغرة --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-emerald-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500">طاولات شاغرة ومتاحة</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-black">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-600 font-mono">{{ $vacantCount }}</span>
                <span class="text-xs font-bold text-gray-400">جاهزة للزبائن</span>
            </div>
            <div class="mt-2 text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                <i class="fa-solid fa-sparkles text-[10px]"></i>
                <span>مستعدة لفتح طلبات جديدة</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-teal-500"></div>
        </div>

        {{-- 3. نسبة إشغال الصالة --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-blue-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500">نسبة إشغال الصالة</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-black">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-blue-600 font-mono">{{ $occupancyRate }}%</span>
                <span class="text-xs font-bold text-gray-400">{{ $occupancyRate > 75 ? 'إشغال مرتفع' : ($occupancyRate > 40 ? 'إشغال متوسط' : 'إشغال هادئ') }}</span>
            </div>
            <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden mt-3">
                <div class="bg-blue-600 h-full rounded-full transition-all duration-500" style="width: {{ $occupancyRate }}%"></div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-400 to-indigo-500"></div>
        </div>

        {{-- 4. إجمالي سعة الصالة --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-gray-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-gray-500">إجمالي طاولات الصالة</span>
                <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center text-sm font-black">
                    <i class="fa-solid fa-table-cells-large"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="text-2xl font-black text-gray-800 font-mono">{{ $totalCount }}</span>
                <span class="text-xs font-bold text-gray-400">طاولة مسجلة</span>
            </div>
            <div class="mt-2 text-[11px] font-bold text-gray-400 flex items-center gap-1">
                <span>المناطق المسجلة: {{ $areas->count() ?: 1 }} مناطق</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-300"></div>
        </div>
    </div>

    {{-- ==================== شريط الفلاتر والتبويبات والبحث ==================== --}}
    <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
        {{-- تبويبات الحالة --}}
        <div class="flex items-center p-1 bg-gray-100 rounded-2xl w-full md:w-auto">
            <button type="button" onclick="setFilterTab('busy')" id="tab_busy"
                class="filter-tab-btn flex-1 md:flex-none px-4 py-2 rounded-xl text-xs font-black transition flex items-center justify-center gap-2 bg-white text-orange-600 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <span>المشغولة حالياً</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-orange-100 text-orange-700 font-mono">{{ $busyCount }}</span>
            </button>
            <button type="button" onclick="setFilterTab('vacant')" id="tab_vacant"
                class="filter-tab-btn flex-1 md:flex-none px-4 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-gray-600 hover:text-gray-900">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>الشاغرة</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-gray-200 text-gray-700 font-mono">{{ $vacantCount }}</span>
            </button>
            <button type="button" onclick="setFilterTab('all')" id="tab_all"
                class="filter-tab-btn flex-1 md:flex-none px-4 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-gray-600 hover:text-gray-900">
                <i class="fa-solid fa-list text-[10px]"></i>
                <span>جميع الترابيزات</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-gray-200 text-gray-700 font-mono">{{ $totalCount }}</span>
            </button>
        </div>

        {{-- حقل البحث وفلتر المنطقة --}}
        <div class="flex items-center gap-3 w-full md:w-auto">
            {{-- فلتر المنطقة --}}
            <select id="areaFilter" onchange="applyFilters()"
                class="bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                <option value="">جميع المناطق والصلات</option>
                @foreach($areas as $area)
                    <option value="{{ $area }}">{{ $area }}</option>
                @endforeach
            </select>

            {{-- حقل البحث --}}
            <div class="relative flex-1 md:w-64">
                <i class="fa-solid fa-magnifying-glass absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="searchInput" oninput="applyFilters()"
                    placeholder="بحث برقم الطاولة أو الأوردر..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl pr-9 pl-3 py-2.5 text-xs text-gray-800 placeholder-gray-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
            </div>
        </div>
    </div>

    {{-- ==================== شبكة كروت الطاولات (Tables Grid) ==================== --}}
    <div id="tablesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($allTables as $table)
            @php
                $isOccupied = (bool) $table->is_occupied;
                $activeOrder = $isOccupied ? $table->activeOrders->first() : null;
                $tableSearchData = strtolower($table->name . ' ' . ($table->area ?: 'الصالة العامة') . ' ' . ($activeOrder ? $activeOrder->order_number . ' ' . ($activeOrder->creator->name ?? '') : ''));
            @endphp

            <div class="table-card bg-white rounded-3xl border-2 transition-all duration-200 flex flex-col justify-between overflow-hidden {{ $isOccupied ? 'border-orange-300 shadow-md hover:border-orange-400' : 'border-gray-200 shadow-xs hover:border-emerald-300 hover:shadow-md' }}"
                data-status="{{ $isOccupied ? 'busy' : 'vacant' }}"
                data-area="{{ $table->area ?: 'الصالة العامة' }}"
                data-search="{{ $tableSearchData }}">

                {{-- رأس الكارت --}}
                <div class="p-4 sm:p-5 {{ $isOccupied ? 'bg-gradient-to-r from-orange-50/70 via-amber-50/40 to-white border-b border-orange-100' : 'bg-gradient-to-r from-emerald-50/50 via-teal-50/30 to-white border-b border-gray-100' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg font-black shadow-xs shrink-0 {{ $isOccupied ? 'bg-orange-500 text-white shadow-orange-500/20' : 'bg-emerald-500 text-white shadow-emerald-500/20' }}">
                                <i class="fa-solid fa-chair"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-black text-gray-900 flex items-center gap-2">
                                    <span>{{ $table->name }}</span>
                                </h3>
                                <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-2">
                                    <span><i class="fa-solid fa-location-dot text-gray-300 ml-1"></i>{{ $table->area ?: 'الصالة العامة' }}</span>
                                    <span>•</span>
                                    <span><i class="fa-solid fa-users text-gray-300 ml-1"></i>{{ $table->capacity ?: 4 }} أفراد</span>
                                </p>
                            </div>
                        </div>

                        {{-- شارة الحالة --}}
                        <div>
                            @if($isOccupied)
                                <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-orange-100 text-orange-700 border border-orange-200 flex items-center gap-1.5 shadow-xs">
                                    <span class="w-2 h-2 rounded-full bg-orange-500 animate-ping"></span>
                                    <span>مشغولة</span>
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    <span>شاغرة ومتاحة</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- محتوى الكارت --}}
                <div class="p-4 sm:p-5 flex-1 flex flex-col justify-between">
                    @if($isOccupied && $activeOrder)
                        <div class="space-y-3 mb-4">
                            {{-- رقم الطلب وتوقيت الفتح --}}
                            <div class="flex items-center justify-between p-2.5 bg-orange-50/70 border border-orange-200/60 rounded-xl text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-gray-500 font-bold">طلب رقم:</span>
                                    <span class="font-mono font-black text-orange-800">#{{ $activeOrder->order_number }}</span>
                                </div>
                                <div class="text-[11px] text-gray-500 flex items-center gap-1 font-medium">
                                    <i class="fa-regular fa-clock text-orange-500"></i>
                                    <span>{{ $activeOrder->created_at->diffForHumans(null, true) }} ({{ $activeOrder->created_at->format('h:i A') }})</span>
                                </div>
                            </div>

                            {{-- الكاشير المسؤول والأصناف --}}
                            <div class="flex items-center justify-between text-xs text-gray-500 px-1">
                                <span><i class="fa-solid fa-user-tie text-gray-400 ml-1"></i> الكاشير: <strong class="text-gray-700">{{ $activeOrder->creator->name ?? 'الكاشير' }}</strong></span>
                                <span><i class="fa-solid fa-utensils text-gray-400 ml-1"></i> {{ $activeOrder->items->count() }} أصناف</span>
                            </div>

                            {{-- قائمة مختصرة بالأصناف --}}
                            <div class="p-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs space-y-1 max-h-24 overflow-y-auto">
                                @foreach($activeOrder->items->take(3) as $item)
                                    <div class="flex justify-between items-center text-gray-700 text-[11px]">
                                        <span class="truncate max-w-[160px]">{{ $item->menu->name ?? 'صنف' }}</span>
                                        <span class="font-mono font-bold text-gray-500">x{{ $item->quantity }} • {{ number_format($item->total, 2) }} ج</span>
                                    </div>
                                @endforeach
                                @if($activeOrder->items->count() > 3)
                                    <div class="text-[10px] text-gray-400 text-center font-bold pt-0.5">
                                        + {{ $activeOrder->items->count() - 3 }} أصناف أخرى
                                    </div>
                                @endif
                            </div>

                            {{-- إجمالي الحساب المفتوح --}}
                            <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                                <span class="text-xs font-bold text-gray-500">المبلغ المطلوب:</span>
                                <span class="text-lg font-black text-emerald-600 font-mono">
                                    {{ number_format($activeOrder->total, 2) }} <span class="text-xs">ج.م</span>
                                </span>
                            </div>
                        </div>
                    @else
                        {{-- حالة الطاولة الشاغرة --}}
                        <div class="py-6 text-center space-y-2">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-xl shadow-xs">
                                <i class="fa-solid fa-sparkles"></i>
                            </div>
                            <p class="text-xs font-bold text-gray-600">الطاولة شاغرة ومتاحة الآن</p>
                            <p class="text-[11px] text-gray-400">يمكنك فتح طلب جديد للعملاء في شاشة البيع وتخصيص الطاولة</p>
                        </div>
                    @endif

                    {{-- ==================== أزرار الإجراءات على الطاولة ==================== --}}
                    <div class="pt-3 border-t border-gray-100 flex items-center gap-2">
                        {{-- زر فتح صفحة الطاولة (المطلوب في الأمر: ومنها يقدر يفتح صفحة اي ترابيزه) --}}
                        <a href="{{ route('tables.show', $table->id) }}"
                            class="flex-1 py-2.5 px-3 rounded-xl border border-gray-200 hover:border-blue-400 hover:bg-blue-50 text-gray-700 hover:text-blue-700 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs text-blue-500"></i>
                            <span>فتح صفحة الطاولة</span>
                        </a>

                        @if($isOccupied && $activeOrder)
                            {{-- زر المحاسبة السريعة وإغلاق الطاولة --}}
                            <button type="button"
                                onclick="openQuickCheckout('{{ $activeOrder->id }}', '{{ $activeOrder->order_number }}', '{{ number_format($activeOrder->total, 2) }}', '{{ $table->name }}')"
                                class="py-2.5 px-3.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs transition flex items-center justify-center gap-1.5 shadow-md shadow-emerald-600/20 cursor-pointer">
                                <i class="fa-solid fa-cash-register"></i>
                                <span>محاسبة</span>
                            </button>
                        @else
                            {{-- زر فتح طلب بالـ POS --}}
                            <a href="{{ route('pos.index') }}"
                                class="py-2.5 px-3.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                                <i class="fa-solid fa-plus"></i>
                                <span>طلب بالـ POS</span>
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        @empty
            <div class="col-span-full bg-white p-12 rounded-3xl border border-gray-200 text-center space-y-3">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center mx-auto text-2xl">
                    <i class="fa-solid fa-table-cells-large"></i>
                </div>
                <h3 class="text-base font-black text-gray-700">لا توجد طاولات مضافة في النظام حالياً</h3>
                <p class="text-xs text-gray-400">يمكن للإدارة إضافة الطاولات من شاشة إدارة الطاولات.</p>
            </div>
        @endforelse
    </div>

    {{-- رسالة عند عدم وجود نتائج للفلتر المحدد --}}
    <div id="noResultsState" class="bg-white p-12 rounded-3xl border border-gray-200 text-center space-y-3 hidden">
        <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mx-auto text-xl shadow-xs">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h3 class="text-base font-black text-gray-800">لا توجد طاولات تطابق البحث أو التصفية الحالية</h3>
        <p class="text-xs text-gray-400 max-w-sm mx-auto">جرب تغيير كلمة البحث أو التبديل إلى تبويب آخر لاستعراض الطاولات.</p>
        <button type="button" onclick="resetFilters()"
            class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition inline-flex items-center gap-2">
            <i class="fa-solid fa-rotate-left"></i>
            <span>إعادة تعيين الفلاتر</span>
        </button>
    </div>

</div>

{{-- ==================== نافذة منبثقة للمحاسبة السريعة للطاولة (Quick Checkout Modal) ==================== --}}
<div id="quickCheckoutModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center hidden" onclick="closeQuickCheckout()">
    <div class="bg-white rounded-3xl p-6 max-w-sm w-full mx-4 shadow-2xl text-right animate-slide-in border border-gray-100" onclick="event.stopPropagation()" dir="rtl">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm font-black shadow-xs">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
                <div>
                    <h4 class="text-sm font-black text-gray-900" id="modalTableTitle">محاسبة الطاولة</h4>
                    <span class="text-[11px] text-gray-400" id="modalOrderSubtitle">طلب رقم #--</span>
                </div>
            </div>
            <button type="button" onclick="closeQuickCheckout()" class="w-8 h-8 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div class="space-y-4">
            {{-- كارت المبلغ المطلوب --}}
            <div class="bg-gradient-to-br from-emerald-50 via-teal-50 to-white border border-emerald-200 rounded-2xl p-4 text-center">
                <span class="text-gray-500 text-xs block mb-1 font-bold">المجموع المطلوب سداده:</span>
                <span class="text-3xl font-black text-emerald-600 font-mono" id="modalOrderTotal">0.00 ج.م</span>
            </div>

            {{-- خيارات طريقة الدفع --}}
            <div>
                <label class="text-xs font-bold text-gray-700 block mb-2">طريقة الدفع والتحصيل:</label>
                <div class="grid grid-cols-3 gap-2" id="checkoutMethods">
                    <button type="button" onclick="selectPayMethod('cash')" id="btn_pay_cash"
                        class="px-2 py-2.5 rounded-xl border text-xs font-black text-center transition bg-emerald-600 text-white border-emerald-600 shadow-xs cursor-pointer">
                        💵 كاش
                    </button>
                    <button type="button" onclick="selectPayMethod('InstaPay')" id="btn_pay_InstaPay"
                        class="px-2 py-2.5 rounded-xl border text-xs font-bold text-center transition bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 cursor-pointer">
                        📱 إنستا باي
                    </button>
                    <button type="button" onclick="selectPayMethod('card')" id="btn_pay_card"
                        class="px-2 py-2.5 rounded-xl border text-xs font-bold text-center transition bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 cursor-pointer">
                        💳 فيزا / كارد
                    </button>
                </div>
            </div>

            {{-- قسم بيانات العميل (اختياري لربط الزيارة والولاء) --}}
            <div class="border border-gray-100 bg-gray-50/70 rounded-2xl p-3 space-y-2.5">
                <div class="flex items-center justify-between">
                    <label class="text-[11px] font-black text-gray-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-user-check text-blue-600"></i>
                        <span>بيانات العميل (اختياري)</span>
                    </label>
                    <span class="text-[10px] text-gray-400">لتسجيل الزيارة والولاء</span>
                </div>

                {{-- حقل رقم الهاتف مع بحث تلقائي حي --}}
                <div class="relative">
                    <input type="tel" id="modalCustomerPhone" placeholder="رقم الهاتف (مثال: 01xxxxxxxxx)..."
                        autocomplete="off"
                        oninput="lookupCustomerPhone(this.value)"
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-mono font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- حقل اسم العميل --}}
                <div class="relative">
                    <input type="text" id="modalCustomerName" placeholder="اسم العميل (اختياري)..."
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                <input type="hidden" id="modalCustomerId">

                {{-- شارة التنبيه التلقائي بحالة العميل --}}
                <div id="customerLookupStatus" class="hidden text-[10px] p-2 rounded-xl border font-bold"></div>
            </div>

            {{-- زر التأكيد والسداد --}}
            <button type="button" id="confirmQuickPayBtn" onclick="submitQuickCheckout()"
                class="w-full bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black py-3 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 cursor-pointer">
                <i class="fa-solid fa-check"></i>
                <span>تأكيد السداد وتفريغ الطاولة</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentFilterTab = 'busy'; // الافتراضي عرض المشغولة أولاً، أو all إن لم تكن هناك مشغولة
    let activeOrderId = null;
    let selectedPayMethod = 'cash';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // إذا لم تكن هناك طاولات مشغولة، نعرض تلقائياً الكل
    @if($busyCount === 0)
        currentFilterTab = 'all';
    @endif

    function setFilterTab(tab) {
        currentFilterTab = tab;
        ['busy', 'vacant', 'all'].forEach(t => {
            const btn = document.getElementById('tab_' + t);
            if (t === tab) {
                btn.className = 'filter-tab-btn flex-1 md:flex-none px-4 py-2 rounded-xl text-xs font-black transition flex items-center justify-center gap-2 bg-white text-orange-600 shadow-xs';
            } else {
                btn.className = 'filter-tab-btn flex-1 md:flex-none px-4 py-2 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 text-gray-600 hover:text-gray-900';
            }
        });
        applyFilters();
    }

    function applyFilters() {
        const query = (document.getElementById('searchInput').value || '').trim().toLowerCase();
        const selectedArea = document.getElementById('areaFilter').value;
        const cards = document.querySelectorAll('#tablesGrid .table-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const status = card.dataset.status;
            const area = card.dataset.area;
            const searchData = card.dataset.search || '';

            let matchesTab = (currentFilterTab === 'all') || (currentFilterTab === status);
            let matchesArea = !selectedArea || (area === selectedArea);
            let matchesQuery = !query || searchData.includes(query);

            if (matchesTab && matchesArea && matchesQuery) {
                card.classList.remove('hidden');
                visibleCount++;
            } else {
                card.classList.add('hidden');
            }
        });

        const noResults = document.getElementById('noResultsState');
        if (noResults) {
            if (visibleCount === 0) {
                noResults.classList.remove('hidden');
            } else {
                noResults.classList.add('hidden');
            }
        }
    }

    function resetFilters() {
        document.getElementById('searchInput').value = '';
        document.getElementById('areaFilter').value = '';
        setFilterTab('all');
    }

    // ========== إدارة مودال المحاسبة السريعة ==========
    let customerLookupTimer = null;

    function lookupCustomerPhone(phone) {
        clearTimeout(customerLookupTimer);
        const statusBox = document.getElementById('customerLookupStatus');
        const nameInput = document.getElementById('modalCustomerName');
        const idInput = document.getElementById('modalCustomerId');

        const cleanPhone = (phone || '').trim();
        if (cleanPhone.length < 3) {
            statusBox.classList.add('hidden');
            idInput.value = '';
            return;
        }

        customerLookupTimer = setTimeout(() => {
            fetch(`/customers/ajax-search?phone=${encodeURIComponent(cleanPhone)}`)
                .then(res => res.json())
                .then(matches => {
                    if (matches && matches.length > 0) {
                        const match = matches[0];
                        idInput.value = match.id;
                        nameInput.value = match.name;
                        statusBox.className = 'text-[10px] p-2 rounded-xl border font-bold bg-emerald-50 text-emerald-800 border-emerald-200 flex items-center gap-1.5';
                        statusBox.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-600"></i> <span>عميل مسجل: <strong>${match.name}</strong> (${match.visits_count} زيارة سابقة • إجمالي ${match.total_spent} ج)</span>`;
                        statusBox.classList.remove('hidden');
                    } else {
                        idInput.value = '';
                        statusBox.className = 'text-[10px] p-2 rounded-xl border font-bold bg-purple-50 text-purple-800 border-purple-200 flex items-center gap-1.5';
                        statusBox.innerHTML = `<i class="fa-solid fa-sparkles text-purple-600"></i> <span>عميل جديد: سيتم حفظ بياناته وربط الفاتورة باسمه</span>`;
                        statusBox.classList.remove('hidden');
                    }
                })
                .catch(() => {});
        }, 250);
    }

    function openQuickCheckout(orderId, orderNumber, total, tableName) {
        activeOrderId = orderId;
        document.getElementById('modalTableTitle').textContent = 'محاسبة ' + tableName;
        document.getElementById('modalOrderSubtitle').textContent = 'طلب رقم #' + orderNumber;
        document.getElementById('modalOrderTotal').textContent = total + ' ج.م';
        selectPayMethod('cash');

        // إعادة ضبط حقول العميل
        document.getElementById('modalCustomerPhone').value = '';
        document.getElementById('modalCustomerName').value = '';
        document.getElementById('modalCustomerId').value = '';
        document.getElementById('customerLookupStatus').classList.add('hidden');

        document.getElementById('quickCheckoutModal').classList.remove('hidden');
    }

    function closeQuickCheckout() {
        document.getElementById('quickCheckoutModal').classList.add('hidden');
    }

    function selectPayMethod(method) {
        selectedPayMethod = method;
        ['cash', 'InstaPay', 'card'].forEach(m => {
            const btn = document.getElementById('btn_pay_' + m);
            if (m === method) {
                btn.className = 'px-2 py-2.5 rounded-xl border text-xs font-black text-center transition bg-emerald-600 text-white border-emerald-600 shadow-xs cursor-pointer';
            } else {
                btn.className = 'px-2 py-2.5 rounded-xl border text-xs font-bold text-center transition bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 cursor-pointer';
            }
        });
    }

    function submitQuickCheckout() {
        if (!activeOrderId) return;
        const btn = document.getElementById('confirmQuickPayBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري السداد...';

        const customerId = document.getElementById('modalCustomerId').value || null;
        const customerPhone = document.getElementById('modalCustomerPhone').value.trim() || null;
        const customerName = document.getElementById('modalCustomerName').value.trim() || null;

        fetch(`/orders/${activeOrderId}/checkout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                payment_method: selectedPayMethod,
                force: true,
                customer_id: customerId,
                customer_phone: customerPhone,
                customer_name: customerName,
            })
        })
        .then(async res => {
            const data = await res.json();
            if (!res.ok) throw data;
            return data;
        })
        .then(data => {
            closeQuickCheckout();
            window.location.reload();
        })
        .catch(err => {
            alert(err.message || 'حدث خطأ أثناء المحاسبة.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> تأكيد السداد وتفريغ الطاولة';
        });
    }

    // تفعيل الفلترة المبدئية عند التحميل
    document.addEventListener('DOMContentLoaded', function() {
        setFilterTab(currentFilterTab);
    });
</script>
@endpush
@endsection
