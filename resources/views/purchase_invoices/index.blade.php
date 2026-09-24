@extends('layouts.app')

@section('title', 'فواتير الشراء والتوريدات')
@section('page_title', 'فواتير الشراء والتوريدات')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" dir="rtl">

    {{-- ==================== رأس الصفحة وزر إنشاء فاتورة جديدة ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white flex items-center justify-center text-xl shadow-md shadow-blue-500/20 shrink-0">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>فواتير الشراء والتوريدات</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $invoicesCount }} فاتورة مسجلة
                    </span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">إدارة ومتابعة فواتير شراء الخامات والمواد وتوريدات المخزن وحسابات الموردين</p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <a href="{{ route('purchase-invoices.create') }}"
                class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center justify-center gap-2 shadow-md shadow-blue-600/25">
                <i class="fa-solid fa-plus"></i>
                <span>تسجيل فاتورة شراء جديدة</span>
            </a>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 font-bold flex items-center gap-2 animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ==================== بطاقات الإحصائيات السريعة (KPIs) ==================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- 1. مشتريات اليوم --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-blue-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500">مشتريات اليوم</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-black">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-blue-600 font-mono">
                {{ number_format($todayPurchases, 2) }} <span class="text-xs">ج.م</span>
            </div>
            <div class="mt-2 text-[11px] font-bold text-gray-400">
                اليوم: {{ \Carbon\Carbon::today()->format('Y-m-d') }}
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-blue-500 to-indigo-500"></div>
        </div>

        {{-- 2. إجمالي المشتريات --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-gray-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500">إجمالي المشتريات</span>
                <div class="w-8 h-8 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center text-xs font-black">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-gray-900 font-mono">
                {{ number_format($totalPurchases, 2) }} <span class="text-xs">ج.م</span>
            </div>
            <div class="mt-2 text-[11px] font-bold text-gray-400">
                لكافة الفواتير المسجلة
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-300"></div>
        </div>

        {{-- 3. المبالغ الآجلة (المتبقية للموردين) --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-rose-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500">متبقي آجل للموردين</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-black">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-rose-600 font-mono">
                {{ number_format($totalUnpaid, 2) }} <span class="text-xs">ج.م</span>
            </div>
            <div class="mt-2 text-[11px] font-bold text-rose-600 flex items-center gap-1">
                <span>مستحق السداد لاحقاً</span>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-rose-400 to-red-500"></div>
        </div>

        {{-- 4. إجمالي عدد الفواتير --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-emerald-100 shadow-xs relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-gray-500">عدد فواتير الشراء</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-black">
                    <i class="fa-solid fa-boxes-packing"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-600 font-mono">
                {{ $invoicesCount }}
            </div>
            <div class="mt-2 text-[11px] font-bold text-gray-400">
                شحنة وتوريدة مسجلة
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-emerald-400 to-teal-500"></div>
        </div>
    </div>

    {{-- ==================== شريط الفلاتر والبحث المتقدم ==================== --}}
    <div class="bg-white p-4 sm:p-5 rounded-3xl border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('purchase-invoices.index') }}" class="space-y-4">
            {{-- فلاتر التاريخ السريعة --}}
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
                <span class="text-gray-400 font-bold ml-2 shrink-0">التاريخ:</span>
                @php
                    $df = request('date_filter', 'all');
                @endphp
                <a href="{{ route('purchase-invoices.index', array_merge(request()->except(['date_filter', 'from_date', 'to_date']), ['date_filter' => 'all'])) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $df === 'all' && !request('from_date') ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    الكل
                </a>
                <a href="{{ route('purchase-invoices.index', array_merge(request()->except(['date_filter', 'from_date', 'to_date']), ['date_filter' => 'today'])) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $df === 'today' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    اليوم
                </a>
                <a href="{{ route('purchase-invoices.index', array_merge(request()->except(['date_filter', 'from_date', 'to_date']), ['date_filter' => 'yesterday'])) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $df === 'yesterday' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    أمس
                </a>
                <a href="{{ route('purchase-invoices.index', array_merge(request()->except(['date_filter', 'from_date', 'to_date']), ['date_filter' => 'week'])) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $df === 'week' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    هذا الأسبوع
                </a>
                <a href="{{ route('purchase-invoices.index', array_merge(request()->except(['date_filter', 'from_date', 'to_date']), ['date_filter' => 'month'])) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $df === 'month' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    هذا الشهر
                </a>
            </div>

            {{-- حقول البحث والتصفية --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 pt-2 border-t border-gray-100">
                {{-- بحث برقم الفاتورة أو المورد أو الصنف --}}
                <div class="lg:col-span-5 relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="ابحث برقم الفاتورة، اسم المورد، أو اسم صنف معين..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl pr-9 pl-3 py-2 text-xs text-gray-800 placeholder-gray-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500">
                    <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                </div>

                {{-- فلترة حالة السداد --}}
                <div class="lg:col-span-3">
                    <select name="payment_status"
                        class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <option value="all">جميع حالات السداد</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>مدفوعة بالكامل</option>
                        <option value="partial" {{ request('payment_status') === 'partial' ? 'selected' : '' }}>مدفوعة جزئياً</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>آجلة (غير مدفوعة)</option>
                    </select>
                </div>

                {{-- فلترة طريقة الدفع --}}
                <div class="lg:col-span-2">
                    <select name="payment_method"
                        class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-xs font-bold rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <option value="all">جميع طرق الدفع</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>كاش نقدي</option>
                        <option value="instapay" {{ request('payment_method') === 'instapay' ? 'selected' : '' }}>إنستا باي</option>
                        <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>فيزا / شبكة</option>
                        <option value="credit" {{ request('payment_method') === 'credit' ? 'selected' : '' }}>آجل</option>
                    </select>
                </div>

                {{-- أزرار البحث وإعادة التعيين --}}
                <div class="lg:col-span-2 flex items-center gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-filter text-xs"></i>
                        <span>تصفية</span>
                    </button>
                    @if(request()->anyFilled(['search', 'date_filter', 'payment_status', 'payment_method', 'from_date', 'to_date']))
                        <a href="{{ route('purchase-invoices.index') }}"
                            class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-xl text-xs transition" title="إلغاء الفلاتر">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- ==================== قائمة الفواتير مقسمة بذكاء حسب التاريخ ==================== --}}
    @if($groupedInvoices->isEmpty())
        <div class="bg-white p-12 rounded-3xl border border-gray-200 text-center space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-gray-50 text-gray-400 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
            <h3 class="text-base font-black text-gray-700">لا توجد فواتير شراء تطابق البحث</h3>
            <p class="text-xs text-gray-400 max-w-sm mx-auto">لم يتم العثور على فواتير بالمعايير الحالية، أو لم يتم تسجيل فواتير شراء بعد.</p>
            <a href="{{ route('purchase-invoices.create') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs">
                <i class="fa-solid fa-plus"></i>
                <span>تسجيل فاتورة شراء الآن</span>
            </a>
        </div>
    @else
        <div class="space-y-6">
            @foreach($groupedInvoices as $dateKey => $invoicesGroup)
                @php
                    $groupDate = \Carbon\Carbon::parse($dateKey);
                    $groupSum = $invoicesGroup->sum('net_amount');
                    $isToday = $groupDate->isToday();
                    $isYesterday = $groupDate->isYesterday();
                @endphp

                <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
                    {{-- شريط رأس التاريخ (Date Group Header) --}}
                    <div class="p-4 sm:p-5 bg-gradient-to-r from-gray-50 via-gray-50/70 to-white border-b border-gray-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold shadow-xs {{ $isToday ? 'bg-blue-600 text-white shadow-blue-500/20' : ($isYesterday ? 'bg-amber-500 text-white' : 'bg-gray-200 text-gray-700') }}">
                                <i class="fa-regular fa-calendar-check"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-black text-gray-900">
                                        {{ $groupDate->translatedFormat('l، d F Y') }}
                                    </h3>
                                    @if($isToday)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-800 border border-blue-200">
                                            اليوم
                                        </span>
                                    @elseif($isYesterday)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-200">
                                            أمس
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-gray-400">
                                    عدد الفواتير: <strong class="text-gray-700">{{ $invoicesGroup->count() }} فاتورة</strong>
                                </span>
                            </div>
                        </div>

                        {{-- إجمالي مشتريات ذلك اليوم --}}
                        <div class="flex items-center gap-2 text-xs self-end sm:self-auto bg-white px-3 py-1.5 rounded-xl border border-gray-100 shadow-xs">
                            <span class="text-gray-500 font-bold">إجمالي مشتريات اليوم:</span>
                            <span class="text-sm font-black text-blue-600 font-mono">
                                {{ number_format($groupSum, 2) }} ج.م
                            </span>
                        </div>
                    </div>

                    {{-- جدول فواتير التاريخ المحدد --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-right border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50/50 text-gray-400 font-bold border-b border-gray-100 text-[11px]">
                                    <th class="p-3.5 w-36">رقم الفاتورة</th>
                                    <th class="p-3.5">المورد</th>
                                    <th class="p-3.5">الأصناف المشتراة</th>
                                    <th class="p-3.5 w-28 text-center">طريقة الدفع</th>
                                    <th class="p-3.5 w-32 text-center">حالة السداد</th>
                                    <th class="p-3.5 w-32 text-left">الصافي المطلوب</th>
                                    <th class="p-3.5 w-28 text-center">المسؤول</th>
                                    <th class="p-3.5 w-28 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                @foreach($invoicesGroup as $invoice)
                                    @php
                                        $statusInfo = $invoice->payment_status_info;
                                    @endphp
                                    <tr class="hover:bg-blue-50/30 transition-colors">
                                        {{-- رقم الفاتورة --}}
                                        <td class="p-3.5">
                                            <a href="{{ route('purchase-invoices.show', $invoice->id) }}"
                                                class="font-mono font-black text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1.5">
                                                <i class="fa-solid fa-receipt text-[11px] text-gray-400"></i>
                                                <span>{{ $invoice->invoice_number }}</span>
                                            </a>
                                            <span class="text-[10px] text-gray-400 block mt-0.5 font-mono">
                                                {{ $invoice->created_at->format('h:i A') }}
                                            </span>
                                        </td>

                                        {{-- اسم المورد --}}
                                        <td class="p-3.5">
                                            <div class="font-black text-gray-900">{{ $invoice->supplier_name }}</div>
                                            @if($invoice->notes)
                                                <span class="text-[10px] text-gray-400 truncate max-w-[150px] block mt-0.5">
                                                    {{ $invoice->notes }}
                                                </span>
                                            @endif
                                        </td>

                                        {{-- الأصناف المشتراة --}}
                                        <td class="p-3.5">
                                            <div class="flex flex-wrap gap-1 max-w-xs">
                                                @foreach($invoice->items->take(3) as $item)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 text-[10px] font-bold">
                                                        <span>{{ $item->item_name }}</span>
                                                        <span class="text-blue-600 font-mono">({{ (float)$item->quantity }} {{ $item->unit }})</span>
                                                    </span>
                                                @endforeach
                                                @if($invoice->items->count() > 3)
                                                    <span class="px-1.5 py-0.5 rounded-lg bg-blue-50 text-blue-700 text-[10px] font-black">
                                                        +{{ $invoice->items->count() - 3 }} أخرى
                                                    </span>
                                                @endif
                                            </div>
                                        </td>

                                        {{-- طريقة الدفع --}}
                                        <td class="p-3.5 text-center">
                                            <span class="px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 text-[11px] font-bold">
                                                {{ $invoice->payment_method_label }}
                                            </span>
                                        </td>

                                        {{-- حالة السداد --}}
                                        <td class="p-3.5 text-center">
                                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $statusInfo['bg'] }}">
                                                {{ $statusInfo['label'] }}
                                            </span>
                                            @if($invoice->remaining_amount > 0)
                                                <span class="text-[10px] text-rose-600 font-mono font-bold block mt-0.5">
                                                    متبقي: {{ number_format($invoice->remaining_amount, 2) }} ج
                                                </span>
                                            @endif
                                        </td>

                                        {{-- الصافي المطلوب --}}
                                        <td class="p-3.5 text-left font-mono font-black text-gray-900 text-sm">
                                            {{ number_format($invoice->net_amount, 2) }} <span class="text-[10px] text-gray-500">ج.م</span>
                                        </td>

                                        {{-- المستخدم / المسؤول --}}
                                        <td class="p-3.5 text-center text-xs text-gray-500">
                                            {{ $invoice->user->name ?? 'المدير' }}
                                        </td>

                                        {{-- أزرار العمليات RUD --}}
                                        <td class="p-3.5 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                {{-- 1. زر العرض Show --}}
                                                <a href="{{ route('purchase-invoices.show', $invoice->id) }}"
                                                    class="w-7 h-7 rounded-lg bg-gray-50 hover:bg-blue-50 text-gray-600 hover:text-blue-600 border border-gray-200 flex items-center justify-center transition"
                                                    title="عرض تفاصيل الفاتورة">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </a>

                                                {{-- 2. زر التعديل Edit --}}
                                                <a href="{{ route('purchase-invoices.edit', $invoice->id) }}"
                                                    class="w-7 h-7 rounded-lg bg-gray-50 hover:bg-amber-50 text-gray-600 hover:text-amber-600 border border-gray-200 flex items-center justify-center transition"
                                                    title="تعديل الفاتورة">
                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                </a>

                                                {{-- 3. زر الحذف Delete --}}
                                                <form action="{{ route('purchase-invoices.destroy', $invoice->id) }}" method="POST"
                                                    onsubmit="return confirm('هل أنت متأكد من حذف فاتورة الشراء رقم {{ $invoice->invoice_number }}؟ سيتم استرجاع الكميات المضافة للمخزن.')"
                                                    class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-7 h-7 rounded-lg bg-gray-50 hover:bg-rose-50 text-gray-600 hover:text-rose-600 border border-gray-200 flex items-center justify-center transition cursor-pointer"
                                                        title="حذف الفاتورة واسترجاع المخزون">
                                                        <i class="fa-solid fa-trash text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
