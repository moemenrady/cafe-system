@extends('layouts.app')

@section('page_title', 'فاتورة مبيعات #' . $invoice->invoice_number)

@push('styles')
<style>
    @media print {
        /* إخفاء كل عناصر النظام أثناء الطباعة */
        aside#sidebar,
        main > header,
        .no-print,
        #mobileOverlay,
        #refundModal {
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

    {{-- رسائل التنبيهات --}}
    @if(session('success'))
        <div class="no-print p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs font-bold text-emerald-800 flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="no-print p-3.5 bg-red-50 border border-red-200 rounded-2xl text-xs font-bold text-red-800 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-600"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

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
                    <span class="font-mono {{ $invoice->isRefunded() ? 'text-red-600 bg-red-50 border-red-200' : 'text-blue-600 bg-blue-50 border-blue-100' }} px-2 py-0.5 rounded-lg border text-xs">
                        #{{ $invoice->invoice_number }}
                    </span>
                    @if($invoice->isRefunded())
                        <span class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full">مرتجعة</span>
                    @endif
                </h2>
                <p class="text-xs text-gray-400">تاريخ الإصدار: {{ $invoice->created_at->format('Y-m-d - h:i A') }}</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if(isset($canModify) && $canModify['allowed'])
            <a href="{{ route('sales-invoices.edit', $invoice->id) }}"
                class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-pen"></i>
                <span class="hidden sm:inline">تعديل الفاتورة</span>
            </a>
            @endif

            @if(isset($canRefund) && $canRefund['allowed'])
            <button type="button" onclick="openRefundModal()"
                class="bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-rotate-left"></i>
                <span class="hidden sm:inline">استرجاع الفاتورة</span>
            </button>
            @endif

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
        <div class="absolute top-0 inset-x-0 h-1.5 {{ $invoice->isRefunded() ? 'bg-gradient-to-r from-red-500 via-rose-500 to-amber-500' : 'bg-gradient-to-r from-blue-500 via-indigo-500 to-emerald-500' }}"></div>

        {{-- تنبيه الفاتورة المرتجعة --}}
        @if($invoice->isRefunded())
            <div class="mb-6 p-4 bg-red-50 border-2 border-red-200 rounded-2xl flex items-start gap-3.5 text-red-900">
                <div class="w-10 h-10 rounded-xl bg-red-100 text-red-600 flex items-center justify-center shrink-0 text-lg">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <div class="space-y-1 text-xs flex-1">
                    <div class="font-black text-sm text-red-700 flex items-center justify-between">
                        <span>هذه الفاتورة تم استرجاعها (مرتجعة)</span>
                        <span class="text-[11px] font-mono text-red-500 bg-red-100/60 px-2 py-0.5 rounded-lg border border-red-200">
                            {{ $invoice->refunded_at ? $invoice->refunded_at->format('Y-m-d - h:i A') : '' }}
                        </span>
                    </div>
                    <p class="text-gray-700">
                        • تم الاسترجاع بواسطة: <strong class="text-gray-900">{{ $invoice->refunder->name ?? 'المسؤول' }}</strong>
                    </p>
                    @if($invoice->refund_reason)
                        <p class="text-red-800 bg-white/70 p-2 rounded-xl border border-red-100 mt-1">
                            <strong>سبب الاسترجاع:</strong> {{ $invoice->refund_reason }}
                        </p>
                    @endif
                    <p class="text-[11px] text-red-600 pt-1">
                        • ملاحظة: تم إعادة المكونات والكميات للمخزن آلياً وتحديث الحسابات.
                    </p>
                </div>
            </div>
        @endif

        {{-- رأس الفاتورة والمعلومات الأساسية --}}
        <div class="flex flex-col sm:flex-row items-center justify-between pb-6 border-b border-gray-100 gap-4 text-center sm:text-right">
            <div class="flex items-center gap-3.5">
                <div class="w-14 h-14 rounded-2xl {{ $invoice->isRefunded() ? 'bg-gradient-to-br from-red-600 to-rose-700 shadow-red-500/30' : 'bg-gradient-to-br from-blue-600 to-indigo-700 shadow-blue-500/30' }} text-white flex items-center justify-center text-2xl shadow-md shrink-0">
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
                    <span class="text-xl {{ $invoice->isRefunded() ? 'line-through text-gray-400' : 'text-emerald-600' }} font-mono">
                        {{ number_format($invoice->total, 2) }} <span class="text-xs">ج.م</span>
                    </span>
                </div>

                @if($invoice->isRefunded())
                    <div class="bg-red-50 border border-red-200 rounded-xl py-1 px-2.5 text-center text-red-700 font-black text-[11px] mt-1">
                        <i class="fa-solid fa-rotate-left ml-1"></i> فاتورة مسترجعة بالكامل
                    </div>
                @else
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl py-1 px-2.5 text-center text-emerald-700 font-black text-[11px] mt-1">
                        <i class="fa-solid fa-circle-check ml-1"></i> تم الدفع بالكامل
                    </div>
                @endif
            </div>
        </div>

    </div>

    {{-- ==================== سجل الرقابة والتدقيق (التعديل والاسترجاع) ==================== --}}
    @if($invoice->transactions && $invoice->transactions->isNotEmpty())
        <div class="no-print bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-3">
            <h3 class="text-sm font-black text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-check text-blue-500"></i>
                <span>سجل الرقابة وحركات الفاتورة (Audit Trail)</span>
                <span class="text-xs text-gray-400 font-normal">({{ $invoice->transactions->count() }} حركة مسجلة)</span>
            </h3>

            <div class="divide-y divide-gray-100 border border-gray-100 rounded-xl overflow-hidden text-xs">
                @foreach($invoice->transactions->sortByDesc('id') as $tx)
                    <div class="p-3 bg-gray-50/50 flex flex-col md:flex-row md:items-center justify-between gap-2">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                @if($tx->action === 'refund')
                                    <span class="px-2 py-0.5 rounded-lg bg-red-100 text-red-700 font-black text-[10px]">استرجاع</span>
                                @elseif($tx->action === 'update')
                                    <span class="px-2 py-0.5 rounded-lg bg-amber-100 text-amber-700 font-black text-[10px]">تعديل</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-lg bg-blue-100 text-blue-700 font-black text-[10px]">{{ $tx->action }}</span>
                                @endif
                                <span class="font-bold text-gray-800">{{ $tx->description }}</span>
                            </div>
                            <div class="text-[11px] text-gray-400 flex items-center gap-3">
                                <span><i class="fa-solid fa-user ml-1 text-gray-400"></i> المنفذ: <strong class="text-gray-700">{{ $tx->creator->name ?? 'مستخدم غير معروف' }}</strong></span>
                                <span><i class="fa-solid fa-clock ml-1 text-gray-400"></i> {{ $tx->created_at->format('Y-m-d h:i A') }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

{{-- نافذة استرجاع الفاتورة --}}
<div id="refundModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 relative text-right" dir="rtl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-base font-black text-gray-800 flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>
                <span>استرجاع الفاتورة #{{ $invoice->invoice_number }}</span>
            </h3>
            <button type="button" onclick="closeRefundModal()" class="w-8 h-8 rounded-xl bg-gray-50 text-gray-400 hover:text-gray-700 flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="refundForm" onsubmit="submitRefund(event)" class="mt-4 space-y-4">
            @csrf
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3.5 text-xs text-amber-900 space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                    <span>تأكيد استرجاع الفاتورة:</span>
                </div>
                <div class="flex justify-between items-center pt-1 font-mono">
                    <span class="text-gray-600">رقم الفاتورة:</span>
                    <strong class="text-gray-900">#{{ $invoice->invoice_number }}</strong>
                </div>
                <div class="flex justify-between items-center font-mono">
                    <span class="text-gray-600">المبلغ المسترجع:</span>
                    <strong class="text-red-600 text-sm">{{ number_format($invoice->total, 2) }} ج.م</strong>
                </div>
                <p class="text-[11px] text-amber-700 pt-1 border-t border-amber-200/70">
                    • سيتم إرجاع كافة المكونات للمخزن تلقائياً وتحديث حسابات الوردية وتسجيل حركة الرقابة.
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
    // طباعة تلقائية في حال كان الرابط يحتوي على print=true
    if (new URLSearchParams(window.location.search).get('print') === 'true') {
        window.addEventListener('load', () => {
            setTimeout(() => { window.print(); }, 400);
        });
    }

    function openRefundModal() {
        document.getElementById('refundReason').value = '';
        document.getElementById('refundModal').classList.remove('hidden');
    }

    function closeRefundModal() {
        document.getElementById('refundModal').classList.add('hidden');
    }

    function submitRefund(e) {
        e.preventDefault();
        const reason = document.getElementById('refundReason').value.trim();

        if (!reason || reason.length < 3) {
            alert('يرجى إدخال سبب الاسترجاع (3 أحرف على الأقل).');
            return;
        }

        const btn = document.getElementById('refundSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الاسترجاع...';

        fetch('{{ route("sales-invoices.refund", $invoice->id) }}', {
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
