@extends('layouts.app')

@section('page_title', 'سجل الطاولات المغلقة - Closed Tables History')

@section('content')
<div class="container mx-auto px-4 py-4" dir="rtl">

    {{-- رأس الصفحة --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-cafePrimary"></i>
                سجل الطاولات المغلقة (History)
            </h2>
            <p class="text-xs text-gray-500 mt-1">عرض أرشيف طلبات الصالة المنتهية والفواتير والتحصيلات (للقراءة وإعادة الطباعة فقط)</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}"
               class="bg-cafePrimary hover:bg-cafeSecondary text-white font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all flex items-center gap-2 shadow-md">
                <i class="fa-solid fa-cash-register"></i>
                الذهاب للـ POS
            </a>
            <a href="{{ route('tables.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-4 py-2.5 rounded-xl text-xs sm:text-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-table-cells"></i>
                إدارة الطاولات
            </a>
        </div>
    </div>

    {{-- شريط التصفية والبحث --}}
    <div class="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" action="{{ route('tables.history') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">بحث برقم الطلب أو العميل:</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 focus:border-blue-500 outline-none"
                        placeholder="رقم الأوردر، الاسم، الهاتف...">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">الطاولة:</label>
                <select name="table_id" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 focus:border-blue-500 outline-none">
                    <option value="">كل الطاولات</option>
                    @foreach($tables as $tbl)
                        <option value="{{ $tbl->id }}" {{ request('table_id') == $tbl->id ? 'selected' : '' }}>
                            {{ $tbl->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600 mb-1">التاريخ:</label>
                <input type="date" name="date" value="{{ request('date') }}"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs text-gray-700 focus:border-blue-500 outline-none">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-filter"></i>
                    تصفية
                </button>
                <a href="{{ route('tables.history') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded-xl text-xs transition-colors">
                    إلغاء
                </a>
            </div>
        </form>
    </div>

    {{-- جدول الطلبات المنتهية --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-gray-50 border-b border-gray-100 text-gray-500 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="p-3.5">الطاولة</th>
                        <th class="p-3.5">رقم الطلب</th>
                        <th class="p-3.5">العميل</th>
                        <th class="p-3.5">الموظف</th>
                        <th class="p-3.5">وقت الفتح</th>
                        <th class="p-3.5">وقت الإغلاق</th>
                        <th class="p-3.5">المجموع</th>
                        <th class="p-3.5">الخصم</th>
                        <th class="p-3.5">ضريبة 14%</th>
                        <th class="p-3.5">الإجمالي</th>
                        <th class="p-3.5">الفاتورة / الدفع</th>
                        <th class="p-3.5 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($orders as $ord)
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="p-3.5 font-black text-gray-800">
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-lg border border-blue-200">
                                    {{ $ord->table?->name ?? $ord->table_number ?? 'طاولة' }}
                                </span>
                            </td>
                            <td class="p-3.5 font-mono font-bold text-gray-700">
                                #{{ $ord->order_number }}
                            </td>
                            <td class="p-3.5 text-gray-600">
                                @if($ord->customer)
                                    <div class="font-bold text-gray-800">{{ $ord->customer->name }}</div>
                                    <div class="text-[11px] font-mono text-gray-400">{{ $ord->customer->phone }}</div>
                                @elseif($ord->phone)
                                    <div class="font-mono text-gray-600">{{ $ord->phone }}</div>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-gray-600 font-medium">
                                {{ $ord->creator?->name ?? 'الكاشير' }}
                            </td>
                            <td class="p-3.5 text-gray-500 font-mono text-[11px]">
                                {{ $ord->created_at?->format('Y-m-d H:i') }}
                            </td>
                            <td class="p-3.5 text-gray-500 font-mono text-[11px]">
                                {{ $ord->closed_at ? $ord->closed_at->format('Y-m-d H:i') : ($ord->updated_at?->format('Y-m-d H:i')) }}
                            </td>
                            <td class="p-3.5 font-bold text-gray-700">
                                {{ number_format($ord->subtotal, 2) }} ج
                            </td>
                            <td class="p-3.5 font-bold text-red-500">
                                {{ number_format($ord->discount, 2) }} ج
                            </td>
                            <td class="p-3.5 font-bold text-amber-600">
                                {{ number_format($ord->vat, 2) }} ج
                            </td>
                            <td class="p-3.5 font-black text-emerald-600 text-sm">
                                {{ number_format($ord->total, 2) }} ج
                            </td>
                            <td class="p-3.5 text-gray-600">
                                @if($ord->invoice)
                                    <div class="font-mono text-blue-600 font-bold">#{{ $ord->invoice->invoice_number }}</div>
                                    <span class="text-[10px] px-1.5 py-0.5 bg-gray-100 rounded text-gray-500">
                                        {{ $ord->invoice->payment_method === 'cash' ? 'نقدي' : ($ord->invoice->payment_method === 'card' ? 'بطاقة' : $ord->invoice->payment_method) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="p-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" onclick="viewClosedOrder({{ $ord->id }})"
                                        class="bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold px-2.5 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1">
                                        <i class="fa-solid fa-eye"></i>
                                        عرض
                                    </button>
                                    <button type="button" onclick="reprintClosedOrder({{ $ord->id }})"
                                        id="reprintBtn_{{ $ord->id }}"
                                        class="bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold px-2.5 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1">
                                        <i class="fa-solid fa-print"></i>
                                        طباعة
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="p-8 text-center text-gray-400">
                                <i class="fa-solid fa-receipt text-3xl mb-2"></i>
                                <p class="text-xs font-bold">لا توجد طلبات طاولات مغلقة مطابقة للبحث.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ========== Modal عرض تفاصيل الطلب المغلق (Read-Only) ========== --}}
<div id="closedOrderModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden" onclick="closeClosedOrderModal()">
    <div class="bg-white rounded-3xl max-w-lg w-full mx-4 shadow-2xl border border-gray-100 overflow-hidden text-right" onclick="event.stopPropagation()">
        
        {{-- Header --}}
        <div class="bg-gray-50 p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm">
                    <i class="fa-solid fa-receipt"></i>
                </span>
                <div>
                    <h3 class="font-black text-gray-800 text-base" id="mOrderTitle">تفاصيل الطلب</h3>
                    <p class="text-[11px] text-gray-400 font-mono" id="mOrderSubTitle"></p>
                </div>
            </div>
            <button type="button" onclick="closeClosedOrderModal()" class="text-gray-400 hover:text-gray-600 text-lg p-1">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        {{-- Body --}}
        <div class="p-5 max-h-[70vh] overflow-y-auto space-y-4">
            
            {{-- بيانات أساسية --}}
            <div class="grid grid-cols-2 gap-2 text-xs bg-gray-50 p-3 rounded-2xl border border-gray-100">
                <div>
                    <span class="text-gray-400">الطاولة:</span>
                    <span class="font-bold text-gray-800 mr-1" id="mTableName">-</span>
                </div>
                <div>
                    <span class="text-gray-400">الكاشير:</span>
                    <span class="font-bold text-gray-800 mr-1" id="mCashierName">-</span>
                </div>
                <div>
                    <span class="text-gray-400">العميل:</span>
                    <span class="font-bold text-gray-800 mr-1" id="mCustomerName">عميل صالة</span>
                </div>
                <div>
                    <span class="text-gray-400">الهاتف:</span>
                    <span class="font-mono text-gray-700 mr-1" id="mCustomerPhone">-</span>
                </div>
                <div>
                    <span class="text-gray-400">وقت الفتح:</span>
                    <span class="font-mono text-gray-700 mr-1" id="mOpenedAt">-</span>
                </div>
                <div>
                    <span class="text-gray-400">وقت الإغلاق:</span>
                    <span class="font-mono text-gray-700 mr-1" id="mClosedAt">-</span>
                </div>
            </div>

            {{-- قائمة الأصناف (Read-Only) --}}
            <div>
                <h4 class="text-xs font-bold text-gray-700 mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-list-check text-blue-500"></i>
                    الأصناف المطلوبة:
                </h4>
                <div class="border border-gray-100 rounded-2xl overflow-hidden">
                    <table class="w-full text-xs text-right">
                        <thead class="bg-gray-50 text-gray-400 font-bold border-b border-gray-100">
                            <tr>
                                <th class="p-2.5">الصنف</th>
                                <th class="p-2.5 text-center">الكمية</th>
                                <th class="p-2.5">السعر</th>
                                <th class="p-2.5 text-left">الإجمالي</th>
                            </tr>
                        </thead>
                        <tbody id="mItemsBody" class="divide-y divide-gray-50">
                            {{-- تُملأ ديناميكياً --}}
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ملخص الحساب المالي (Read-Only) --}}
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-1.5 text-xs">
                <div class="flex justify-between items-center text-gray-600">
                    <span>المجموع الفرعي:</span>
                    <span class="font-bold" id="mSubtotal">0.00 ج</span>
                </div>
                <div class="flex justify-between items-center text-gray-600">
                    <span>الخصم:</span>
                    <span class="font-bold text-red-500" id="mDiscount">0.00 ج</span>
                </div>
                <div class="flex justify-between items-center text-gray-600">
                    <span>ضريبة القيمة المضافة (14%):</span>
                    <span class="font-bold text-amber-600" id="mVat">0.00 ج</span>
                </div>
                <div class="flex justify-between items-center border-t border-slate-200 pt-2 mt-1">
                    <span class="text-sm font-black text-gray-900">الإجمالي النهائي:</span>
                    <span class="text-lg font-black text-emerald-600" id="mTotal">0.00 ج</span>
                </div>
                <div class="flex justify-between items-center text-[11px] text-gray-400 pt-1">
                    <span>طريقة الدفع: <span class="font-bold text-gray-700" id="mPaymentMethod">نقدي</span></span>
                    <span>رقم الفاتورة: <span class="font-mono font-bold text-blue-600" id="mInvoiceNo">-</span></span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3">
            <button type="button" id="mReprintBtn" onclick=""
                class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition-colors flex items-center gap-1.5 shadow-md shadow-amber-500/20">
                <i class="fa-solid fa-print"></i>
                إعادة طباعة الفاتورة
            </button>
            <button type="button" onclick="closeClosedOrderModal()"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2.5 px-4 rounded-xl text-xs transition-colors">
                إغلاق
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function viewClosedOrder(orderId) {
        fetch(`/orders/${orderId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            const ord = res.data;
            if (!ord) return;

            document.getElementById('mOrderTitle').textContent = `طلب #${ord.order_number}`;
            document.getElementById('mOrderSubTitle').textContent = `تاريخ: ${ord.created_at ? ord.created_at.substring(0, 16) : ''}`;
            document.getElementById('mTableName').textContent = ord.table ? ord.table.name : (ord.table_number || '-');
            document.getElementById('mCashierName').textContent = ord.creator ? ord.creator.name : 'الكاشير';
            document.getElementById('mCustomerName').textContent = ord.customer ? ord.customer.name : 'عميل صالة';
            document.getElementById('mCustomerPhone').textContent = ord.customer ? (ord.customer.phone || '-') : (ord.phone || '-');
            document.getElementById('mOpenedAt').textContent = ord.created_at ? ord.created_at.substring(0, 16) : '-';
            document.getElementById('mClosedAt').textContent = ord.closed_at ? ord.closed_at.substring(0, 16) : (ord.updated_at ? ord.updated_at.substring(0, 16) : '-');

            const tbody = document.getElementById('mItemsBody');
            tbody.innerHTML = '';
            (ord.items || []).forEach(it => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="p-2.5 font-bold text-gray-800">${it.menu ? it.menu.name : 'صنف'}</td>
                    <td class="p-2.5 text-center font-bold text-blue-600">${it.quantity}</td>
                    <td class="p-2.5 text-gray-600">${Number(it.price).toFixed(2)} ج</td>
                    <td class="p-2.5 text-left font-bold text-gray-800">${Number(it.total).toFixed(2)} ج</td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('mSubtotal').textContent = Number(ord.subtotal).toFixed(2) + ' ج';
            document.getElementById('mDiscount').textContent = Number(ord.discount).toFixed(2) + ' ج';
            document.getElementById('mVat').textContent = Number(ord.vat).toFixed(2) + ' ج';
            document.getElementById('mTotal').textContent = Number(ord.total).toFixed(2) + ' ج';
            document.getElementById('mPaymentMethod').textContent = ord.invoice ? (ord.invoice.payment_method === 'cash' ? 'نقدي' : ord.invoice.payment_method) : 'نقدي';
            document.getElementById('mInvoiceNo').textContent = ord.invoice ? '#' + ord.invoice.invoice_number : '-';

            const reprintBtn = document.getElementById('mReprintBtn');
            reprintBtn.onclick = () => reprintClosedOrder(ord.id);

            document.getElementById('closedOrderModal').classList.remove('hidden');
        })
        .catch(err => {
            alert('حدث خطأ أثناء تحميل تفاصيل الطلب.');
        });
    }

    function closeClosedOrderModal() {
        document.getElementById('closedOrderModal').classList.add('hidden');
    }

    function reprintClosedOrder(orderId) {
        const btn = document.getElementById(`reprintBtn_${orderId}`) || document.getElementById('mReprintBtn');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري الطباعة...';

        fetch(`/orders/${orderId}/reprint`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
                'X-Device-UUID': 'pos-cashier-01'
            },
            body: JSON.stringify({ force: false })
        })
        .then(async r => {
            const data = await r.json();
            if (!r.ok) throw data;
            return data;
        })
        .then(data => {
            alert('تم إرسال أمر إعادة الطباعة إلى الطابعة بنجاح.');
        })
        .catch(err => {
            alert(err?.message || 'تعذر إعادة الطباعة. تأكد من اتصال الطابعة.');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = origText;
        });
    }
</script>
@endpush
@endsection
