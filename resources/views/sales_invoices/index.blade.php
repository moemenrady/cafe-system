@extends('layouts.app')

@section('page_title', 'سجل فواتير المبيعات')

@section('content')
<div class="container mx-auto space-y-5 pb-12" dir="rtl">

    {{-- ==================== 1. بطاقات الإحصائيات العامة ==================== --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
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

        {{-- الفواتير المرتجعة --}}
        <div class="bg-white p-3.5 rounded-2xl border border-red-100 shadow-xs flex flex-col justify-between bg-red-50/20">
            <div class="flex items-center justify-between text-red-600 text-xs font-bold mb-1">
                <span>مرتجعة</span>
                <i class="fa-solid fa-rotate-left"></i>
            </div>
            <p class="text-base font-black text-red-600 leading-tight">
                {{ number_format($stats['refunded_total'] ?? 0, 2) }} <span class="text-[10px] text-red-400">ج</span>
            </p>
            <span class="text-[10px] text-gray-400 font-bold mt-0.5">({{ $stats['refunded_count'] ?? 0 }} مرتجع)</span>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                {{-- البحث --}}
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full bg-gray-50 text-gray-800 pr-9 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-medium"
                        placeholder="رقم الفاتورة، ملاحظة، أو هاتف...">
                </div>

                {{-- حالة الفاتورة --}}
                <div>
                    <select name="status"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-bold">
                        <option value="all">كل الحالات</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>✅ المدفوعة فقط</option>
                        <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>🔄 المرتجعة فقط</option>
                    </select>
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
                    @if(request()->hasAny(['search', 'status', 'payment_method', 'date_filter', 'date_from', 'date_to']))
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

    {{-- رسائل الفلاش --}}
    @if(session('success'))
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs font-bold text-emerald-800 flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 bg-red-50 border border-red-200 rounded-2xl text-xs font-bold text-red-800 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ==================== 3. جدول فواتير المبيعات ==================== --}}
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
            <div>
                <h3 class="text-sm font-black text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i>
                    قائمة فواتير المبيعات
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">عرض أحدث الفواتير المسجلة في النظام مع تفاصيل الدفع وإمكانية التعديل والاسترجاع</p>
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
                        <th class="p-3.5 text-center w-24">الحالة</th>
                        <th class="p-3.5 w-32">النوع / الطاولة</th>
                        <th class="p-3.5">الأصناف المباعة</th>
                        <th class="p-3.5 w-36">الكاشير</th>
                        <th class="p-3.5 text-center w-28">طريقة الدفع</th>
                        <th class="p-3.5 text-left w-32">الإجمالي الصافي</th>
                        <th class="p-3.5 text-center w-36">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs font-medium text-gray-700">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-blue-50/20 transition-colors {{ $invoice->isRefunded() ? 'bg-red-50/20' : '' }}">
                            {{-- رقم الفاتورة --}}
                            <td class="p-3.5">
                                <a href="{{ route('sales-invoices.show', $invoice->id) }}"
                                    class="font-mono font-bold {{ $invoice->isRefunded() ? 'text-red-600 bg-red-50 border-red-200' : 'text-blue-600 bg-blue-50/60 border-blue-100' }} hover:underline px-2 py-1 rounded-lg border inline-block">
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

                            {{-- الحالة (مدفوعة / مرتجعة) --}}
                            <td class="p-3.5 text-center">
                                @if($invoice->isRefunded())
                                    <span class="bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 rounded-xl text-[11px] font-black inline-flex items-center gap-1"
                                        title="{{ $invoice->refund_reason ? 'السبب: ' . $invoice->refund_reason : 'مسترجعة' }}">
                                        <i class="fa-solid fa-rotate-left text-[10px]"></i> مرتجعة
                                    </span>
                                @else
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-xl text-[11px] font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i> مدفوعة
                                    </span>
                                @endif
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
                                @if($invoice->isRefunded() && $invoice->refund_reason)
                                    <p class="text-[10px] text-red-500 font-bold mt-1 truncate max-w-sm flex items-center gap-1">
                                        <i class="fa-solid fa-arrow-rotate-left"></i>
                                        سبب الإرجاع: {{ $invoice->refund_reason }}
                                    </p>
                                @elseif($invoice->note)
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
                                <div class="{{ $invoice->isRefunded() ? 'line-through text-gray-400' : '' }}">{{ number_format($invoice->total, 2) }} ج.م</div>
                                @if($invoice->discount > 0)
                                    <div class="text-[10px] text-red-500 font-bold">خصم: {{ number_format($invoice->discount, 2) }} ج</div>
                                @endif
                                @if($invoice->isRefunded())
                                    <div class="text-[10px] text-red-600 font-bold mt-0.5">مسترجعة</div>
                                @endif
                            </td>

                            {{-- الإجراءات --}}
                            <td class="p-3.5 text-center">
                                @php
                                    $activeShift = auth()->user()->activeShift;
                                    $canEdit = !$invoice->isRefunded() && (
                                        auth()->user()->isManager() || (
                                            $activeShift && (int)$invoice->shift_id === (int)$activeShift->id
                                        )
                                    );
                                    $canRefund = !$invoice->isRefunded() && (
                                        auth()->user()->isManager() || (
                                            $activeShift && (int)$invoice->shift_id === (int)$activeShift->id
                                        )
                                    );
                                @endphp
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
                                    @if($canEdit)
                                    <a href="{{ route('sales-invoices.edit', $invoice->id) }}"
                                        class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition"
                                        title="تعديل الفاتورة">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                    @endif
                                    @if($canRefund)
                                    <button type="button"
                                        onclick="openRefundModal({{ $invoice->id }}, '{{ $invoice->invoice_number }}', '{{ number_format($invoice->total, 2) }}')"
                                        class="w-8 h-8 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition"
                                        title="استرجاع الفاتورة">
                                        <i class="fa-solid fa-rotate-left text-xs"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-12 text-center text-gray-400">
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

{{-- ==================== نافذة منبثقة لتأكيد استرجاع الفاتورة ==================== --}}
<div id="refundModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 relative text-right" dir="rtl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-base font-black text-gray-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <span>استرجاع فاتورة مبيعات</span>
            </h3>
            <button type="button" onclick="closeRefundModal()" class="w-8 h-8 rounded-xl bg-gray-50 text-gray-400 hover:text-gray-700 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="refundForm" onsubmit="submitRefund(event)" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" id="refundInvoiceId">

            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 text-xs text-amber-900 space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                    <span>تأكيد استرجاع الفاتورة:</span>
                </div>
                <div class="flex justify-between items-center pt-1 font-mono">
                    <span class="text-gray-600">رقم الفاتورة:</span>
                    <strong id="modalInvoiceNumber" class="text-gray-900"></strong>
                </div>
                <div class="flex justify-between items-center font-mono">
                    <span class="text-gray-600">المبلغ المسترجع:</span>
                    <strong id="modalInvoiceTotal" class="text-red-600 text-sm"></strong>
                </div>
                <p class="text-[11px] text-amber-700 pt-1 border-t border-amber-200/70">
                    • سيتم إرجاع مكونات الأصناف للمخزن تلقائياً وتحديث حسابات الوردية وتسجيل حركة الرقابة.
                </p>
            </div>

            <div>
                <label for="refundReason" class="block text-xs font-bold text-gray-700 mb-1.5">
                    سبب الاسترجاع <span class="text-red-500">*</span>
                </label>
                <textarea id="refundReason" name="reason" rows="3" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-3 text-xs focus:outline-none focus:ring-2 focus:ring-red-400 font-medium"
                    placeholder="اكتب سبب استرجاع الفاتورة (مثال: إلغاء الطلب من العميل، خطأ في الحساب)..."></textarea>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" id="refundSubmitBtn"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white font-black py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-xs">
                    <i class="fa-solid fa-check"></i>
                    <span>تأكيد استرجاع الفاتورة</span>
                </button>
                <button type="button" onclick="closeRefundModal()"
                    class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let currentRefundInvoiceId = null;

    function openRefundModal(invoiceId, invoiceNumber, invoiceTotal) {
        currentRefundInvoiceId = invoiceId;
        document.getElementById('refundInvoiceId').value = invoiceId;
        document.getElementById('modalInvoiceNumber').textContent = '#' + invoiceNumber;
        document.getElementById('modalInvoiceTotal').textContent = invoiceTotal + ' ج.م';
        document.getElementById('refundReason').value = '';
        document.getElementById('refundModal').classList.remove('hidden');
    }

    function closeRefundModal() {
        document.getElementById('refundModal').classList.add('hidden');
        currentRefundInvoiceId = null;
    }

    function submitRefund(e) {
        e.preventDefault();
        const invoiceId = document.getElementById('refundInvoiceId').value;
        const reason = document.getElementById('refundReason').value.trim();

        if (!reason || reason.length < 3) {
            alert('يرجى إدخال سبب الاسترجاع (3 أحرف على الأقل).');
            return;
        }

        const btn = document.getElementById('refundSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الاسترجاع...';

        fetch(`/sales-invoices/${invoiceId}/refund`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                window.location.reload();
            } else {
                alert('❌ ' + (data.error || 'حدث خطأ أثناء استرجاع الفاتورة.'));
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check"></i> تأكيد استرجاع الفاتورة';
            }
        })
        .catch(err => {
            alert('❌ فشل الاتصال بالخادم.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check"></i> تأكيد استرجاع الفاتورة';
        });
    }
</script>
@endpush
@endsection