@extends('layouts.app')

@section('page_title', 'تفاصيل ' . $table->name)

@section('content')
<div class="container mx-auto max-w-5xl space-y-5 pb-12" dir="rtl">

    {{-- ==================== رأس الصفحة والتنقل السريع ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3">
            @php
                $backRoute = (auth()->user() && auth()->user()->isManager()) ? route('tables.index') : route('tables.busy_tables');
            @endphp
            <a href="{{ $backRoute }}"
                class="w-10 h-10 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-black text-gray-900">{{ $table->name }}</h2>
                    @if($isOccupied)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-orange-100 text-orange-700 border border-orange-200 animate-pulse flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-orange-500"></span> مشغولة بطلب نشط
                        </span>
                    @else
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-emerald-100 text-emerald-700 border border-emerald-200 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> متاحة وجاهزة
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-0.5">
                    المنطقة: <span class="font-bold text-gray-600">{{ $table->area ?: 'الصالة الرئيسية' }}</span>
                    • السعة: <span class="font-bold text-gray-600">{{ $table->capacity ?: 4 }} أفراد</span>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            @if(auth()->user() && auth()->user()->isManager())
            <a href="{{ route('tables.edit', $table->id) }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2 px-3.5 rounded-xl text-xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-pen text-xs"></i> تعديل بيانات الطاولة
            </a>
            @endif
            @if(!$isOccupied)
                <a href="{{ route('pos.index') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-xl text-xs transition flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-cash-register text-xs"></i> فتح طلب في الـ POS
                </a>
            @endif
        </div>
    </div>

    {{-- ==================== بطاقة حالة الطاولة والمعلومات ==================== --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs text-center">
            <span class="text-gray-400 text-xs block mb-1">اسم الطاولة</span>
            <span class="text-base font-black text-gray-800">{{ $table->name }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs text-center">
            <span class="text-gray-400 text-xs block mb-1">الموقع / المنطقة</span>
            <span class="text-base font-black text-gray-800">{{ $table->area ?: 'الصالة العامة' }}</span>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs text-center">
            <span class="text-gray-400 text-xs block mb-1">السعة الاستيعابية</span>
            <span class="text-base font-black text-blue-600">{{ $table->capacity ?: 4 }} أشخاص</span>
        </div>
        <div class="bg-white p-3.5 rounded-2xl border border-gray-100 shadow-xs text-center">
            <span class="text-gray-400 text-xs block mb-1">حالة الطاولة</span>
            <span class="text-base font-black {{ $table->is_active ? 'text-emerald-600' : 'text-gray-400' }}">
                {{ $table->is_active ? 'نشطة في النظام' : 'معطلة' }}
            </span>
        </div>
    </div>

    {{-- ==================== إذا كانت الطاولة مشغولة: عرض طلبات العميل ==================== --}}
    @if($isOccupied && $activeOrders->count() > 0)
        <div class="space-y-4">
            @foreach($activeOrders as $order)
                <div class="bg-white rounded-3xl border-2 border-orange-200/80 shadow-md overflow-hidden">
                    
                    {{-- رأس كارت الطلب المفتوح --}}
                    <div class="p-4 sm:p-5 bg-gradient-to-r from-orange-50 via-amber-50 to-white border-b border-orange-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-orange-500 text-white flex items-center justify-center text-xl shadow-md shadow-orange-500/20 shrink-0">
                                <i class="fa-solid fa-bell-concierge"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-black text-gray-900">طلب نشط للعميل</h3>
                                    <span class="font-mono text-xs font-black text-orange-700 bg-orange-100 px-2.5 py-0.5 rounded-lg border border-orange-200">
                                        #{{ $order->order_number }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-2">
                                    <span><i class="fa-regular fa-clock text-gray-400 ml-1"></i> فُتح: {{ $order->created_at->format('h:i A') }} (منذ {{ $order->created_at->diffForHumans() }})</span>
                                    <span>•</span>
                                    <span><i class="fa-solid fa-user-tie text-gray-400 ml-1"></i> الكاشير: {{ $order->creator->name ?? 'غير معروف' }}</span>
                                </p>
                            </div>
                        </div>

                        {{-- زر المحاسبة وإغلاق الطاولة --}}
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button type="button" onclick="openCheckoutModal('{{ $order->id }}', '{{ $order->order_number }}', '{{ number_format($order->total, 2) }}')"
                                class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black px-5 py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-md shadow-emerald-600/25">
                                <i class="fa-solid fa-cash-register"></i>
                                <span>محاسبة وإغلاق الطاولة</span>
                            </button>
                        </div>
                    </div>

                    {{-- ملاحظة الطلب إن وجدت --}}
                    @if($order->notes)
                        <div class="mx-4 sm:mx-6 mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-900 flex items-start gap-2">
                            <i class="fa-regular fa-comment-dots text-amber-600 mt-0.5 shrink-0"></i>
                            <div>
                                <span class="font-bold">ملاحظات العميل على الطلب:</span>
                                <span class="mr-1">{{ $order->notes }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- جدول الأصناف التي طلبها العميل في هذه الطاولة --}}
                    <div class="p-4 sm:p-6">
                        <h4 class="text-xs font-black text-gray-700 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-utensils text-orange-500"></i>
                            الأصناف المطلوبة على الطاولة ({{ $order->items->count() }} صنف):
                        </h4>

                        <div class="border border-gray-200 rounded-2xl overflow-hidden">
                            <table class="w-full text-right border-collapse text-xs">
                                <thead>
                                    <tr class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                                        <th class="p-3 w-12 text-center">#</th>
                                        <th class="p-3">اسم الصنف</th>
                                        <th class="p-3 text-center w-24">سعر الوحدة</th>
                                        <th class="p-3 text-center w-20">الكمية</th>
                                        <th class="p-3 text-left w-28">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 font-medium text-gray-800">
                                    @foreach($order->items as $index => $item)
                                        <tr class="hover:bg-orange-50/20 transition-colors">
                                            <td class="p-3 text-center text-gray-400 font-mono">{{ $index + 1 }}</td>
                                            <td class="p-3">
                                                <div class="font-bold text-gray-900 text-xs sm:text-sm">{{ $item->menu->name ?? 'صنف محذوف' }}</div>
                                                @if($item->notes)
                                                    <div class="text-[10px] text-gray-400 mt-0.5">ملاحظة: {{ $item->notes }}</div>
                                                @endif
                                            </td>
                                            <td class="p-3 text-center font-mono text-gray-600">{{ number_format($item->price, 2) }} ج</td>
                                            <td class="p-3 text-center font-mono font-black text-blue-600 text-sm">x{{ $item->quantity }}</td>
                                            <td class="p-3 text-left font-mono font-black text-gray-900 text-sm">{{ number_format($item->total, 2) }} ج</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- شريط الإجماليات --}}
                        <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-2xl flex flex-col sm:flex-row justify-between items-center gap-3">
                            <div class="text-xs text-gray-500">
                                عدد الأصناف: <span class="font-bold text-gray-800">{{ $order->items->sum('quantity') }} قطعة</span>
                            </div>
                            <div class="flex items-center gap-6">
                                @if($order->discount > 0)
                                    <div class="text-xs text-red-500 font-bold">
                                        الخصم: -{{ number_format($order->discount, 2) }} ج
                                    </div>
                                @endif
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-gray-600">المجموع المطلوب سداده:</span>
                                    <span class="text-xl font-black text-emerald-600 font-mono">
                                        {{ number_format($order->total, 2) }} <span class="text-xs">ج.م</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>
            @endforeach
        </div>
    @else
        {{-- حالة الطاولة شاغرة --}}
        <div class="bg-white rounded-3xl border border-gray-200/80 shadow-xs p-10 text-center space-y-3">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto text-2xl shadow-inner">
                <i class="fa-solid fa-chair"></i>
            </div>
            <h3 class="text-base font-black text-gray-800">الطاولة شاغرة ومتاحة الآن</h3>
            <p class="text-xs text-gray-400 max-w-sm mx-auto">لا توجد طلبات جارية على هذه الطاولة حالياً. يمكنك فتح طلب جديد من شاشة الكاشير.</p>
            <a href="{{ route('pos.index') }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition shadow-xs">
                <i class="fa-solid fa-plus"></i> فتح طلب جديد لهذه الطاولة
            </a>
        </div>
    @endif

    {{-- ==================== سجل آخر الطلبات المكتملة على الطاولة ==================== --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
            <h3 class="text-xs font-black text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
                سجل آخر الطلبات المكتملة على هذه الطاولة
            </h3>
            <span class="text-[11px] text-gray-400 font-bold">آخر 10 طلبات</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-400 font-bold border-b border-gray-100">
                        <th class="p-3 w-32">رقم الطلب</th>
                        <th class="p-3 w-36">التاريخ والوقت</th>
                        <th class="p-3">الأصناف</th>
                        <th class="p-3 w-32">الكاشير</th>
                        <th class="p-3 text-center w-24">طريقة الدفع</th>
                        <th class="p-3 text-left w-28">الإجمالي</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                    @forelse($table->orders as $pastOrder)
                        <tr class="hover:bg-gray-50/50">
                            <td class="p-3 font-mono font-bold text-blue-600">
                                #{{ $pastOrder->order_number }}
                            </td>
                            <td class="p-3 text-gray-500 font-mono text-[11px]">
                                {{ $pastOrder->created_at->format('Y-m-d h:i A') }}
                            </td>
                            <td class="p-3">
                                <span class="text-gray-800 font-bold">{{ $pastOrder->items->count() }} أصناف</span>
                                <span class="text-gray-400 text-[10px]">
                                    ({{ $pastOrder->items->pluck('menu.name')->filter()->take(3)->join(', ') }})
                                </span>
                            </td>
                            <td class="p-3 text-gray-600">
                                {{ $pastOrder->creator->name ?? 'الكاشير' }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="bg-gray-100 text-gray-700 px-2 py-0.5 rounded-lg text-[10px] font-bold">
                                    {{ $pastOrder->payment_method ?? 'كاش' }}
                                </span>
                            </td>
                            <td class="p-3 text-left font-mono font-black text-gray-900">
                                {{ number_format($pastOrder->total, 2) }} ج
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-400">لا يوجد سجل طلبات سابقة لهذه الطاولة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ==================== نافذة منبثقة للمحاسبة السريعة للطاولة ==================== --}}
<div id="checkoutModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center hidden" onclick="closeCheckoutModal()">
    <div class="bg-white rounded-3xl p-6 max-w-sm w-full mx-4 shadow-2xl text-right animate-scale-in" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <h4 class="text-sm font-black text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-cash-register text-emerald-600"></i>
                إتمام محاسبة الطلب
            </h4>
            <button type="button" onclick="closeCheckoutModal()" class="text-gray-400 hover:text-gray-600 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-4">
            <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3 text-center">
                <span class="text-gray-400 text-xs block mb-0.5" id="checkoutOrderNumber">طلب رقم #--</span>
                <span class="text-2xl font-black text-emerald-600 font-mono" id="checkoutOrderTotal">0.00 ج.م</span>
            </div>

            <div>
                <label class="text-xs font-bold text-gray-700 block mb-2">اختر طريقة الدفع:</label>
                <div class="grid grid-cols-3 gap-2" id="checkoutPaymentOptions">
                    <button type="button" onclick="selectCheckoutMethod('cash')" id="cpm_cash"
                        class="px-2 py-2.5 rounded-xl border text-xs font-black text-center transition bg-emerald-600 text-white border-emerald-600 shadow-xs">
                        💵 كاش
                    </button>
                    <button type="button" onclick="selectCheckoutMethod('InstaPay')" id="cpm_InstaPay"
                        class="px-2 py-2.5 rounded-xl border text-xs font-bold text-center transition bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100">
                        📱 إنستا باي
                    </button>
                    <button type="button" onclick="selectCheckoutMethod('card')" id="cpm_card"
                        class="px-2 py-2.5 rounded-xl border text-xs font-bold text-center transition bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100">
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
                    <input type="tel" id="showModalCustomerPhone" placeholder="رقم الهاتف (مثال: 01xxxxxxxxx)..."
                        autocomplete="off"
                        oninput="lookupShowCustomerPhone(this.value)"
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-mono font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- حقل اسم العميل --}}
                <div class="relative">
                    <input type="text" id="showModalCustomerName" placeholder="اسم العميل (اختياري)..."
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-1.5 text-xs font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                <input type="hidden" id="showModalCustomerId">

                {{-- شارة التنبيه التلقائي بحالة العميل --}}
                <div id="showCustomerLookupStatus" class="hidden text-[10px] p-2 rounded-xl border font-bold"></div>
            </div>

            <button type="button" id="confirmCheckoutBtn" onclick="executeCheckout()"
                class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-3 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 active:scale-95 cursor-pointer">
                <i class="fa-solid fa-check"></i>
                <span>تأكيد السداد وتفريغ الطاولة</span>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let activeCheckoutOrderId = null;
    let selectedCheckoutMethod = 'cash';
    let showCustomerLookupTimer = null;
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function lookupShowCustomerPhone(phone) {
        clearTimeout(showCustomerLookupTimer);
        const statusBox = document.getElementById('showCustomerLookupStatus');
        const nameInput = document.getElementById('showModalCustomerName');
        const idInput = document.getElementById('showModalCustomerId');

        const cleanPhone = (phone || '').trim();
        if (cleanPhone.length < 3) {
            statusBox.classList.add('hidden');
            idInput.value = '';
            return;
        }

        showCustomerLookupTimer = setTimeout(() => {
            fetch(`/customers/ajax-search?phone=${encodeURIComponent(cleanPhone)}`)
                .then(res => res.json())
                .then(matches => {
                    if (matches && matches.length > 0) {
                        const match = matches[0];
                        idInput.value = match.id;
                        nameInput.value = match.name;
                        statusBox.className = 'text-[10px] p-2 rounded-xl border font-bold bg-emerald-50 text-emerald-800 border-emerald-200 flex items-center gap-1.5';
                        statusBox.innerHTML = `<i class="fa-solid fa-circle-check text-emerald-600"></i> <span>عميل مسجل: <strong>${match.name}</strong> (${match.visits_count} زيارة • ${match.total_spent} ج)</span>`;
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

    function openCheckoutModal(orderId, orderNumber, total) {
        activeCheckoutOrderId = orderId;
        document.getElementById('checkoutOrderNumber').textContent = 'طلب رقم #' + orderNumber;
        document.getElementById('checkoutOrderTotal').textContent = total + ' ج.م';
        selectCheckoutMethod('cash');

        // مسح بيانات العميل
        document.getElementById('showModalCustomerPhone').value = '';
        document.getElementById('showModalCustomerName').value = '';
        document.getElementById('showModalCustomerId').value = '';
        document.getElementById('showCustomerLookupStatus').classList.add('hidden');

        document.getElementById('checkoutModal').classList.remove('hidden');
    }

    function closeCheckoutModal() {
        document.getElementById('checkoutModal').classList.add('hidden');
    }

    function selectCheckoutMethod(method) {
        selectedCheckoutMethod = method;
        ['cash', 'InstaPay', 'card'].forEach(m => {
            const btn = document.getElementById('cpm_' + m);
            if (m === method) {
                btn.className = 'px-2 py-2.5 rounded-xl border text-xs font-black text-center transition bg-emerald-600 text-white border-emerald-600 shadow-xs cursor-pointer';
            } else {
                btn.className = 'px-2 py-2.5 rounded-xl border text-xs font-bold text-center transition bg-gray-50 text-gray-700 border-gray-200 hover:bg-gray-100 cursor-pointer';
            }
        });
    }

    function executeCheckout() {
        if (!activeCheckoutOrderId) return;
        const btn = document.getElementById('confirmCheckoutBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري السداد...';

        const customerId = document.getElementById('showModalCustomerId').value || null;
        const customerPhone = document.getElementById('showModalCustomerPhone').value.trim() || null;
        const customerName = document.getElementById('showModalCustomerName').value.trim() || null;

        fetch(`/orders/${activeCheckoutOrderId}/checkout`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify({
                payment_method: selectedCheckoutMethod,
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
            closeCheckoutModal();
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
</script>
@endpush
@endsection
