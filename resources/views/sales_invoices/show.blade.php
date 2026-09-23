@extends('layouts.app')

@section('page_title', 'فاتورة مبيعات #' . $invoice->invoice_number)

@push('styles')
<style>
    @media print {
        /* إخفاء كل عناصر النظام أثناء الطباعة */
        aside#sidebar,
        main > header,
        .no-print,
        #mobileOverlay {
            display: none !important;
        }

        body, main, .print-container {
            margin: 0 !important;
            padding: 0 !important;
            background: white !important;
            width: 100% !important;
        }

        .receipt-card {
            border: none !important;
            box-shadow: none !important;
            max-width: 80mm !important;
            margin: 0 auto !important;
            font-size: 12px !important;
        }
    }
</style>
@endpush

@section('content')
<div class="container mx-auto max-w-4xl space-y-4 pb-12 print-container" dir="rtl">

    {{-- ==================== شريط الإجراءات والرجوع (لا يطبع) ==================== --}}
    <div class="no-print flex items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales-invoices.index') }}"
                class="w-10 h-10 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع لسجل الفواتير">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <h2 class="text-base font-black text-gray-800 flex items-center gap-2">
                    <span>فاتورة مبيعات</span>
                    <span class="font-mono text-blue-600 bg-blue-50 px-2 py-0.5 rounded-lg border border-blue-100 text-xs">
                        #{{ $invoice->invoice_number }}
                    </span>
                </h2>
                <p class="text-xs text-gray-400">تاريخ الإصدار: {{ $invoice->created_at->format('Y-m-d - h:i A') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-print"></i>
                <span>طباعة الفاتورة</span>
            </button>
            <a href="{{ route('pos.index') }}"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-plus"></i>
                <span class="hidden sm:inline">طلب جديد</span>
            </a>
        </div>
    </div>

    {{-- ==================== كارت الفاتورة المتكامل ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-200/80 shadow-md p-6 sm:p-8 receipt-card relative overflow-hidden">
        
        {{-- شريط زينة علوي --}}
        <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-blue-500 via-indigo-500 to-emerald-500"></div>

        {{-- رأس الفاتورة والمعلومات الأساسية --}}
        <div class="flex flex-col sm:flex-row items-center justify-between pb-6 border-b border-gray-100 gap-4 text-center sm:text-right">
            <div class="flex items-center gap-3.5">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center text-2xl shadow-md shadow-blue-500/30 shrink-0">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <div>
                    <h1 class="text-xl font-black text-gray-900 leading-tight">نظام الكافيه</h1>
                    <p class="text-xs text-gray-500 mt-0.5">فاتورة بيع ضريبية مبسطة</p>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3 text-center sm:text-left min-w-[200px]">
                <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">رقم الفاتورة:</div>
                <div class="text-base font-black text-gray-900 font-mono">{{ $invoice->invoice_number }}</div>
                <div class="text-[11px] text-gray-500 mt-1 font-mono">
                    <i class="fa-regular fa-calendar-check text-gray-400 ml-1"></i>
                    {{ $invoice->created_at->format('Y-m-d - h:i A') }}
                </div>
            </div>
        </div>

        {{-- تفاصيل العملية والكاشير والعميل --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 py-5 border-b border-gray-100 text-xs">
            {{-- الكاشير --}}
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1 text-[11px]">الكاشير المسؤول:</span>
                <span class="font-bold text-gray-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-user-tie text-blue-500"></i>
                    {{ $invoice->creator->name ?? 'غير محدد' }}
                </span>
            </div>

            {{-- طريقة الدفع --}}
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1 text-[11px]">طريقة السداد:</span>
                <span class="font-black text-gray-800 flex items-center gap-1.5">
                    @if($invoice->payment_method === 'cash')
                        <span class="text-emerald-700">💵 كاش (نقداً)</span>
                    @elseif($invoice->payment_method === 'InstaPay')
                        <span class="text-purple-700">📱 إنستا باي</span>
                    @elseif($invoice->payment_method === 'card')
                        <span class="text-blue-700">💳 بطاقة ائتمان / فيزا</span>
                    @else
                        <span>{{ $invoice->payment_method ?? 'كاش' }}</span>
                    @endif
                </span>
            </div>

            {{-- نوع الطلب أو الطاولة --}}
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1 text-[11px]">نوع الطلب:</span>
                @if($invoice->order)
                    @if($invoice->order->type === 'dine_in')
                        <span class="font-black text-amber-700 flex items-center gap-1">
                            <i class="fa-solid fa-chair text-amber-600"></i>
                            صالة ({{ $invoice->order->table->name ?? 'طاولة' }})
                        </span>
                    @elseif($invoice->order->type === 'delivery')
                        <span class="font-black text-purple-700 flex items-center gap-1">
                            <i class="fa-solid fa-motorcycle text-purple-600"></i> توصيل ديليفري
                        </span>
                    @else
                        <span class="font-black text-blue-700 flex items-center gap-1">
                            <i class="fa-solid fa-mug-saucer text-blue-600"></i> تيك أواي
                        </span>
                    @endif
                @else
                    <span class="font-bold text-gray-600">بيع مباشر</span>
                @endif
            </div>

            {{-- العميل إن وجد --}}
            <div class="bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1 text-[11px]">العميل:</span>
                <span class="font-bold text-gray-800 truncate block">
                    {{ $invoice->client->name ?? $invoice->order->phone ?? 'عميل نقدي' }}
                </span>
            </div>
        </div>

        {{-- الملاحظات إن وجدت --}}
        @if($invoice->note)
            <div class="py-3 px-4 bg-amber-50/50 border border-amber-200/60 rounded-xl my-4 text-xs flex items-start gap-2">
                <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                <div>
                    <span class="font-bold text-amber-900">ملاحظة الفاتورة:</span>
                    <span class="text-amber-800 mr-1">{{ $invoice->note }}</span>
                </div>
            </div>
        @endif

        {{-- جدول الأصناف المحاسبية --}}
        <div class="mt-4 border border-gray-200 rounded-2xl overflow-hidden">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                        <th class="p-3 w-12 text-center">#</th>
                        <th class="p-3">الصنف</th>
                        <th class="p-3 text-center w-24">سعر الوحدة</th>
                        <th class="p-3 text-center w-20">الكمية</th>
                        <th class="p-3 text-left w-28">الإجمالي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-800">
                    @forelse($invoice->items as $index => $item)
                        <tr class="hover:bg-gray-50/50">
                            <td class="p-3 text-center text-gray-400 font-mono">{{ $index + 1 }}</td>
                            <td class="p-3">
                                <div class="font-bold text-gray-900">{{ $item->menu->name ?? 'صنف محذوف' }}</div>
                                @if($item->menu && $item->menu->category)
                                    <div class="text-[10px] text-gray-400">{{ $item->menu->category->name }}</div>
                                @endif
                            </td>
                            <td class="p-3 text-center font-mono text-gray-600">{{ number_format($item->item_price, 2) }} ج</td>
                            <td class="p-3 text-center font-mono font-black text-blue-600">x{{ $item->quantity }}</td>
                            <td class="p-3 text-left font-mono font-bold text-gray-900">{{ number_format($item->total, 2) }} ج</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-gray-400 font-medium">لا توجد أصناف في هذه الفاتورة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ملخص الحساب والإجماليات --}}
        <div class="mt-6 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-6 pt-4 border-t border-gray-100">
            <div class="space-y-1 text-xs text-gray-500 text-center sm:text-right">
                <p class="font-bold text-gray-700">شروط الفاتورة:</p>
                <p>• تعتبر هذه الفاتورة إيصال استلام رسمي مسجل بالنظام.</p>
                <p>• شكراً لاختياركم كافيهنا ونتمنى لكم يوماً سعيداً!</p>
            </div>

            <div class="w-full sm:w-72 bg-gray-50 border border-gray-200/80 rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between items-center text-gray-600">
                    <span>المجموع الفرعي:</span>
                    <span class="font-mono font-bold">{{ number_format($invoice->total + $invoice->discount, 2) }} ج</span>
                </div>

                @if($invoice->discount > 0)
                    <div class="flex justify-between items-center text-red-600 font-bold">
                        <span>الخصم الممنوح:</span>
                        <span class="font-mono">- {{ number_format($invoice->discount, 2) }} ج</span>
                    </div>
                @endif

                <div class="border-t border-gray-200 pt-2 flex justify-between items-center text-sm font-black text-gray-900">
                    <span>الإجمالي الصافي:</span>
                    <span class="text-xl text-emerald-600 font-mono">{{ number_format($invoice->total, 2) }} <span class="text-xs">ج.م</span></span>
                </div>

                <div class="bg-emerald-50 border border-emerald-200 rounded-xl py-1 px-2.5 text-center text-emerald-700 font-black text-[11px] mt-1">
                    <i class="fa-solid fa-circle-check ml-1"></i> تم الدفع بالكامل
                </div>
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
    // طباعة تلقائية في حال كان الرابط يحتوي على print=true
    if (new URLSearchParams(window.location.search).get('print') === 'true') {
        window.addEventListener('load', () => {
            setTimeout(() => { window.print(); }, 400);
        });
    }
</script>
@endpush
@endsection
