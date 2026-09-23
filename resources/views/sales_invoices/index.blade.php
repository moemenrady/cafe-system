@extends('layouts.app')

@section('page_title', 'سجل فواتير المبيعات')

@section('content')
<div class="container mx-auto space-y-5 pb-12" dir="rtl">

    {{-- ==================== 1. بطاقات الإحصائيات العامة ==================== --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        {{-- إجمالي المبيعات --}}
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>إجمالي المبيعات</span>
                <i class="fa-solid fa-vault text-blue-500"></i>
            </div>
            <p class="text-lg font-black text-gray-800 leading-tight">
                {{ number_format($stats['total_sales'], 2) }} <span class="text-[10px] text-gray-400">ج</span>
            </p>
        </div>

        {{-- مبيعات اليوم --}}
        <div class="bg-white p-3.5 rounded-2xl border border-emerald-100 shadow-xs flex flex-col justify-between bg-emerald-50/20">
            <div class="flex items-center justify-between text-emerald-600 text-xs font-bold mb-1">
                <span>مبيعات اليوم</span>
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <p class="text-lg font-black text-emerald-600 leading-tight">
                {{ number_format($stats['today_sales'], 2) }} <span class="text-[10px] text-emerald-400">ج</span>
            </p>
            <span class="text-[10px] text-gray-400 font-bold mt-0.5">({{ $stats['today_count'] }} فاتورة اليوم)</span>
        </div>

        {{-- مبيعات الكاش --}}
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>💵 كاش</span>
                <i class="fa-solid fa-money-bill-wave text-emerald-500"></i>
            </div>
            <p class="text-base font-black text-gray-800 leading-tight">
                {{ number_format($stats['cash_total'], 2) }} <span class="text-[10px] text-gray-400">ج</span>
            </p>
        </div>

        {{-- مبيعات إنستا باي --}}
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>📱 إنستا باي</span>
                <i class="fa-solid fa-mobile-screen-button text-purple-500"></i>
            </div>
            <p class="text-base font-black text-gray-800 leading-tight">
                {{ number_format($stats['instapay_total'], 2) }} <span class="text-[10px] text-gray-400">ج</span>
            </p>
        </div>

        {{-- مبيعات الفيزا --}}
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>💳 فيزا / كارد</span>
                <i class="fa-regular fa-credit-card text-blue-500"></i>
            </div>
            <p class="text-base font-black text-gray-800 leading-tight">
                {{ number_format($stats['card_total'], 2) }} <span class="text-[10px] text-gray-400">ج</span>
            </p>
        </div>

        {{-- عدد الفواتير الكلي --}}
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>إجمالي الفواتير</span>
                <i class="fa-solid fa-receipt text-indigo-500"></i>
            </div>
            <p class="text-lg font-black text-gray-800 leading-tight">
                {{ number_format($stats['invoices_count']) }} <span class="text-[10px] text-gray-400">فاتورة</span>
            </p>
        </div>
    </div>

    {{-- ==================== 2. شريط البحث والفلترة ==================== --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('sales-invoices.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- البحث --}}
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full bg-gray-50 text-gray-800 pr-9 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-medium"
                        placeholder="رقم الفاتورة، ملاحظة، أو هاتف...">
                </div>

                {{-- طريقة الدفع --}}
                <div>
                    <select name="payment_method"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-bold">
                        <option value="all">كل طرق الدفع</option>
                        <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>💵 كاش فقط</option>
                        <option value="InstaPay" {{ request('payment_method') === 'InstaPay' ? 'selected' : '' }}>📱 إنستا باي فقط</option>
                        <option value="card" {{ request('payment_method') === 'card' ? 'selected' : '' }}>💳 فيزا / كارد فقط</option>
                    </select>
                </div>

                {{-- الفلترة السريعة بالتاريخ --}}
                <div>
                    <select name="date_filter"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-bold">
                        <option value="">كل التواريخ</option>
                        <option value="today" {{ request('date_filter') === 'today' ? 'selected' : '' }}>اليوم</option>
                        <option value="yesterday" {{ request('date_filter') === 'yesterday' ? 'selected' : '' }}>أمس</option>
                        <option value="this_week" {{ request('date_filter') === 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                        <option value="this_month" {{ request('date_filter') === 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                    </select>
                </div>

                {{-- أزرار التنفيذ والمسح --}}
                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-filter text-xs"></i> تصفية
                    </button>
                    @if(request()->hasAny(['search', 'payment_method', 'date_filter', 'date_from', 'date_to']))
                        <a href="{{ route('sales-invoices.index') }}"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1"
                            title="إلغاء الفلاتر">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                    <a href="{{ route('pos.index') }}"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1 shadow-xs"
                        title="الانتقال لشاشة الكاشير">
                        <i class="fa-solid fa-cash-register"></i>
                        <span class="hidden sm:inline">نقطة البيع</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- ==================== 3. جدول فواتير المبيعات ==================== --}}
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-sm font-black text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i>
                    قائمة فواتير المبيعات
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">عرض أحدث الفواتير المسجلة في النظام مع التفاصيل وطريقة الدفع</p>
            </div>
            <span class="text-xs font-bold text-gray-500 bg-white border border-gray-200 px-3 py-1 rounded-xl">
                العدد المعروض: {{ $invoices->count() }} من {{ $invoices->total() }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-500 text-xs font-bold border-b border-gray-100 uppercase tracking-wider">
                        <th class="p-3.5 w-32">رقم الفاتورة</th>
                        <th class="p-3.5 w-40">التاريخ والوقت</th>
                        <th class="p-3.5 w-32">النوع / الطاولة</th>
                        <th class="p-3.5">الأصناف المباعة</th>
                        <th class="p-3.5 w-36">الكاشير</th>
                        <th class="p-3.5 text-center w-28">طريقة الدفع</th>
                        <th class="p-3.5 text-left w-32">الإجمالي الصافي</th>
                        <th class="p-3.5 text-center w-28">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs font-medium text-gray-700">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-blue-50/20 transition-colors">
                            {{-- رقم الفاتورة --}}
                            <td class="p-3.5">
                                <a href="{{ route('sales-invoices.show', $invoice->id) }}"
                                    class="font-mono font-bold text-blue-600 hover:text-blue-800 hover:underline bg-blue-50/60 px-2 py-1 rounded-lg border border-blue-100 inline-block">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>

                            {{-- التاريخ والوقت --}}
                            <td class="p-3.5 text-gray-500">
                                <div class="flex flex-col gap-0.5 font-mono text-[11px]">
                                    <span class="font-bold text-gray-700">
                                        <i class="fa-regular fa-calendar text-gray-400 ml-1"></i>{{ $invoice->created_at->format('Y-m-d') }}
                                    </span>
                                    <span class="text-gray-400">
                                        <i class="fa-regular fa-clock text-gray-300 ml-1"></i>{{ $invoice->created_at->format('h:i A') }}
                                    </span>
                                </div>
                            </td>

                            {{-- النوع أو الطاولة --}}
                            <td class="p-3.5">
                                @if($invoice->order)
                                    @if($invoice->order->type === 'dine_in')
                                        <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-lg text-[11px] font-bold">
                                            <i class="fa-solid fa-chair text-[10px]"></i>
                                            {{ $invoice->order->table->name ?? 'طاولة' }}
                                        </span>
                                    @elseif($invoice->order->type === 'delivery')
                                        <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 border border-purple-200 px-2 py-0.5 rounded-lg text-[11px] font-bold">
                                            <i class="fa-solid fa-motorcycle text-[10px]"></i> ديليفري
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-lg text-[11px] font-bold">
                                            <i class="fa-solid fa-mug-saucer text-[10px]"></i> تيك أواي
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400 text-[11px]">مباشر</span>
                                @endif
                            </td>

                            {{-- الأصناف والكميات --}}
                            <td class="p-3.5">
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    @if($invoice->items && $invoice->items->count() > 0)
                                        @foreach($invoice->items->take(4) as $item)
                                            <span class="inline-flex items-center bg-gray-50 text-gray-800 border border-gray-200 rounded-lg px-2 py-0.5 text-[11px] gap-1">
                                                <span>{{ $item->menu->name ?? 'صنف محذوف' }}</span>
                                                <span class="font-black text-blue-600 bg-blue-50 px-1 rounded text-[10px]">x{{ $item->quantity }}</span>
                                            </span>
                                        @endforeach
                                        @if($invoice->items->count() > 4)
                                            <span class="text-[10px] text-gray-400 font-bold self-center">
                                                +{{ $invoice->items->count() - 4 }} أصناف أخرى
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-[11px]">لا توجد تفاصيل أصناف</span>
                                    @endif
                                </div>
                                @if($invoice->note)
                                    <p class="text-[10px] text-gray-400 mt-1 truncate max-w-sm flex items-center gap-1">
                                        <i class="fa-regular fa-comment-dots text-gray-300"></i>
                                        {{ $invoice->note }}
                                    </p>
                                @endif
                            </td>

                            {{-- الكاشير --}}
                            <td class="p-3.5">
                                <span class="inline-flex items-center gap-1 text-gray-700 font-bold text-xs">
                                    <i class="fa-solid fa-user-tie text-gray-400 text-[11px]"></i>
                                    {{ $invoice->creator->name ?? 'الكاشير' }}
                                </span>
                            </td>

                            {{-- طريقة الدفع --}}
                            <td class="p-3.5 text-center">
                                @if($invoice->payment_method === 'cash')
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-xl text-[11px] font-black inline-flex items-center gap-1">
                                        <i class="fa-solid fa-money-bill-wave text-[10px]"></i> كاش
                                    </span>
                                @elseif($invoice->payment_method === 'InstaPay')
                                    <span class="bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-1 rounded-xl text-[11px] font-black inline-flex items-center gap-1">
                                        <i class="fa-solid fa-mobile-screen-button text-[10px]"></i> إنستا باي
                                    </span>
                                @elseif($invoice->payment_method === 'card')
                                    <span class="bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-xl text-[11px] font-black inline-flex items-center gap-1">
                                        <i class="fa-regular fa-credit-card text-[10px]"></i> فيزا / كارد
                                    </span>
                                @else
                                    <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-lg text-[11px]">
                                        {{ $invoice->payment_method ?? 'كاش' }}
                                    </span>
                                @endif
                            </td>

                            {{-- الإجمالي الصافي --}}
                            <td class="p-3.5 text-left font-black text-gray-900 text-sm">
                                <div>{{ number_format($invoice->total, 2) }} ج.م</div>
                                @if($invoice->discount > 0)
                                    <div class="text-[10px] text-red-500 font-bold">خصم: {{ number_format($invoice->discount, 2) }} ج</div>
                                @endif
                            </td>

                            {{-- الإجراءات --}}
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('sales-invoices.show', $invoice->id) }}"
                                        class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition"
                                        title="عرض تفاصيل الفاتورة">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('sales-invoices.show', $invoice->id) }}?print=true"
                                        class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 flex items-center justify-center transition"
                                        title="طباعة الفاتورة">
                                        <i class="fa-solid fa-print text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-12 text-center text-gray-400">
                                <div class="w-16 h-16 rounded-2xl bg-gray-100 text-gray-300 flex items-center justify-center mx-auto mb-3 text-2xl">
                                    <i class="fa-solid fa-file-invoice text-3xl"></i>
                                </div>
                                <p class="font-bold text-gray-600 mb-1">لا توجد فواتير مطابقة لبحثك</p>
                                <p class="text-xs text-gray-400">جرّب تغيير خيارات البحث أو الفلترة</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- الترقيم الصفحي (Pagination) --}}
        @if($invoices->hasPages())
            <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>
@endsection