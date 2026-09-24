@extends('layouts.app')

@section('title', 'كشف حساب وبروفايل العميل: ' . $customer->name)
@section('page_title', 'تفاصيل وسجل العميل')

@section('content')
<div class="space-y-6 animate-slide-in pb-16" dir="rtl">

    {{-- ==================== 1. الترويسة وأزرار الإجراءات السريعة ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs print:hidden">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('customers.index') }}"
                class="w-10 h-10 rounded-2xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع لدليل العملاء">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>{{ $customer->name }}</span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-black border {{ $vipBadge['class'] }}">
                        <i class="{{ $vipBadge['icon'] }} text-[9px]"></i>
                        <span>{{ $vipBadge['label'] }}</span>
                    </span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5 flex items-center gap-2">
                    <span>كود العميل: <b class="font-mono text-gray-600">#{{ $customer->id }}</b></span>
                    <span>•</span>
                    <span>تاريخ التسجيل: {{ $customer->created_at->translatedFormat('d F Y') }} (منذ {{ $customer->created_at->diffForHumans(null, true) }})</span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 self-end sm:self-auto">
            {{-- زر تصدير سجل هذا العميل كشيت إكسيل --}}
            <a href="{{ route('customers.exportSingle', $customer->id) }}"
                class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-1.5 shadow-sm cursor-pointer"
                title="تصدير جميع زيارات وفواتير العميل كملف Excel/CSV">
                <i class="fa-solid fa-file-excel text-xs"></i>
                <span>تصدير فواتير العميل (Excel)</span>
            </a>

            {{-- زر طباعة كشف الحساب --}}
            <button type="button" onclick="window.print()"
                class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 active:scale-95 text-gray-700 font-bold text-xs transition flex items-center gap-1.5 cursor-pointer">
                <i class="fa-solid fa-print text-xs"></i>
                <span>طباعة كشف الحساب</span>
            </button>

            {{-- زر تعديل العميل --}}
            <button type="button" onclick="openEditCustomerModal()"
                class="px-3.5 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                <i class="fa-solid fa-pen-to-square text-xs"></i>
                <span>تعديل</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 font-bold flex items-center gap-2 print:hidden">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ==================== 2. بروفايل العميل والمؤشرات المالية ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        
        {{-- كارت البيانات الشخصية والتواصل --}}
        <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-3">
                <i class="fa-solid fa-id-card text-indigo-600"></i>
                <span>بيانات التواصل والملف الشخصي</span>
            </h3>

            <div class="space-y-3 text-xs">
                {{-- الهاتف --}}
                <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="text-gray-500 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-phone text-indigo-500"></i> رقم الهاتف:
                    </span>
                    @if($customer->phone)
                        <div class="flex items-center gap-2 font-mono font-bold text-gray-900">
                            <span>{{ $customer->phone }}</span>
                            <a href="https://wa.me/2{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank"
                                class="w-6 h-6 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-600 flex items-center justify-center text-xs transition"
                                title="مراسلة عبر واتساب">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                        </div>
                    @else
                        <span class="text-gray-400">غير مسجل</span>
                    @endif
                </div>

                {{-- العنوان --}}
                <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="text-gray-500 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-location-dot text-rose-500"></i> العنوان:
                    </span>
                    <span class="font-bold text-gray-800">{{ $customer->address ?: 'غير مسجل' }}</span>
                </div>

                {{-- البريد الإلكتروني --}}
                <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="text-gray-500 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-envelope text-sky-500"></i> البريد الإلكتروني:
                    </span>
                    <span class="font-mono text-gray-800">{{ $customer->email ?: 'غير مسجل' }}</span>
                </div>

                {{-- تاريخ أول زيارة --}}
                <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="text-gray-500 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-calendar-plus text-emerald-500"></i> أول زيارة للكافيه:
                    </span>
                    <span class="font-bold text-gray-800">
                        {{ $firstVisit ? $firstVisit->format('Y-m-d') : 'اليوم' }}
                    </span>
                </div>

                {{-- تاريخ آخر زيارة --}}
                <div class="flex items-center justify-between p-2.5 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="text-gray-500 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-clock-rotate-left text-amber-500"></i> آخر زيارة مسجلة:
                    </span>
                    <span class="font-bold text-gray-800">
                        {{ $lastVisit ? $lastVisit->diffForHumans() : 'لا يوجد' }}
                    </span>
                </div>

                {{-- ملاحظات وتفضيلات العميل --}}
                @if($customer->notes)
                    <div class="p-3 bg-amber-50/70 border border-amber-200/80 rounded-2xl space-y-1">
                        <span class="text-[11px] font-black text-amber-900 block flex items-center gap-1">
                            <i class="fa-regular fa-star text-amber-600"></i> ملاحظات وتفضيلات العميل:
                        </span>
                        <p class="text-xs text-amber-800 leading-relaxed">{{ $customer->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- كروت إحصائيات النشاط والإنفاق (4 KPIs) --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                
                {{-- إجمالي المدفوعات --}}
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50/40 p-4 rounded-3xl border border-emerald-200 shadow-xs">
                    <span class="text-[11px] font-bold text-emerald-700 block mb-1">إجمالي إنفاق العميل</span>
                    <p class="text-xl sm:text-2xl font-black text-emerald-700 font-mono">
                        {{ number_format($totalSpent, 2) }}
                    </p>
                    <span class="text-[10px] text-emerald-600 mt-1 block">جنيه مصري</span>
                </div>

                {{-- عدد الزيارات --}}
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50/40 p-4 rounded-3xl border border-indigo-200 shadow-xs">
                    <span class="text-[11px] font-bold text-indigo-700 block mb-1">عدد الزيارات والطلبات</span>
                    <p class="text-xl sm:text-2xl font-black text-indigo-700 font-mono">
                        {{ $visitsCount }}
                    </p>
                    <span class="text-[10px] text-indigo-600 mt-1 block">زيارة مسجلة</span>
                </div>

                {{-- متوسط الفاتورة --}}
                <div class="bg-gradient-to-br from-blue-50 to-sky-50/40 p-4 rounded-3xl border border-blue-200 shadow-xs">
                    <span class="text-[11px] font-bold text-blue-700 block mb-1">متوسط قيمة الفاتورة</span>
                    <p class="text-xl sm:text-2xl font-black text-blue-700 font-mono">
                        {{ number_format($avgTicket, 2) }}
                    </p>
                    <span class="text-[10px] text-blue-600 mt-1 block">ج.م لكل زيارة</span>
                </div>

                {{-- وسيلة الدفع المفضلة --}}
                <div class="bg-gradient-to-br from-amber-50 to-orange-50/40 p-4 rounded-3xl border border-amber-200 shadow-xs">
                    <span class="text-[11px] font-bold text-amber-700 block mb-1">طريقة الدفع المفضلة</span>
                    <p class="text-sm font-black text-amber-900 mt-2">
                        {{ $favPayment }}
                    </p>
                    <span class="text-[10px] text-amber-600 mt-1 block">الأكثر استخداماً</span>
                </div>
            </div>

            {{-- الأصناف المفضلة والأكثر طلباً لهذا العميل --}}
            <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-xs space-y-3">
                <h4 class="text-xs font-black text-gray-800 uppercase tracking-wider flex items-center justify-between border-b border-gray-100 pb-2.5">
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-mug-hot text-amber-500"></i>
                        <span>الأصناف والمشروبات المفضلة للعميل</span>
                    </span>
                    <span class="text-[11px] text-gray-400">بناءً على تكرار الطلب</span>
                </h4>

                @if($favoriteItems->isEmpty())
                    <p class="text-xs text-gray-400 py-3 text-center">لم تُسجل أي أصناف في فواتير هذا العميل حتى الآن.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5 pt-1">
                        @foreach($favoriteItems as $fav)
                            <div class="flex items-center justify-between p-2.5 bg-gray-50 border border-gray-100 rounded-2xl text-xs">
                                <div>
                                    <strong class="text-gray-900 block">{{ $fav->name }}</strong>
                                    <span class="text-[10px] text-gray-400">إجمالي مدفوعاتها: {{ number_format($fav->total_amount, 2) }} ج</span>
                                </div>
                                <span class="px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800 font-mono font-black text-[11px]">
                                    {{ (int) $fav->total_qty }} طلب
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>

    {{-- ==================== 3. سجل وتاريخ زيارات وفواتير العميل ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm sm:text-base font-black text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-blue-600"></i>
                    <span>سجل فواتير وزيارات العميل ({{ $invoices->total() }} فاتورة)</span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">تفاصيل جميع طلبات الصالة والسفري المرتبطة بهذا العميل وقيمتها والأصناف المطلوبة</p>
            </div>

            <div class="flex items-center gap-2 print:hidden">
                <a href="{{ route('customers.exportSingle', $customer->id) }}"
                    class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-emerald-600 hover:text-white text-gray-700 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-file-arrow-down"></i>
                    <span>تصدير السجل كملف إكسيل</span>
                </a>
            </div>
        </div>

        @if($invoices->isEmpty())
            <div class="p-16 text-center text-gray-400 space-y-2">
                <i class="fa-regular fa-folder-open text-4xl text-gray-300"></i>
                <p class="font-bold text-gray-600 text-xs">لا توجد فواتير مبيعات مسجلة باسم هذا العميل حتى الآن</p>
                <p class="text-[11px] text-gray-400">عند إغلاق أي طاولة بالصالة وتدوين رقم هذا العميل، ستظهر الفاتورة هنا فورياً.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] font-black border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4">رقم الفاتورة</th>
                            <th class="py-3 px-4">التاريخ والوقت</th>
                            <th class="py-3 px-4">نوع الطلب / الطاولة</th>
                            <th class="py-3 px-4">الأصناف المطلوبة</th>
                            <th class="py-3 px-4">طريقة الدفع</th>
                            <th class="py-3 px-4">المبلغ الإجمالي</th>
                            <th class="py-3 px-4">الكاشير</th>
                            <th class="py-3 px-4 text-center print:hidden">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($invoices as $inv)
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                {{-- رقم الفاتورة --}}
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-900 whitespace-nowrap">
                                    #{{ $inv->invoice_number }}
                                </td>

                                {{-- التاريخ والوقت --}}
                                <td class="py-3.5 px-4 font-mono text-gray-600 whitespace-nowrap">
                                    <div>{{ $inv->created_at->format('Y-m-d') }}</div>
                                    <div class="text-[10px] text-gray-400">{{ $inv->created_at->format('h:i A') }}</div>
                                </td>

                                {{-- نوع الطلب والطاولة --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($inv->order && $inv->order->table)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-orange-50 text-orange-700 border border-orange-200">
                                            <i class="fa-solid fa-chair text-[9px]"></i>
                                            <span>صالة - {{ $inv->order->table->name ?? ('طاولة ' . $inv->order->table->table_number) }}</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-gray-100 text-gray-700">
                                            <i class="fa-solid fa-bag-shopping text-[9px]"></i> سفري / خارجي
                                        </span>
                                    @endif
                                </td>

                                {{-- الأصناف المطلوبة --}}
                                <td class="py-3.5 px-4 max-w-xs">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($inv->items as $it)
                                            <span class="inline-block bg-gray-100 text-gray-800 px-2 py-0.5 rounded-md text-[10px] font-medium">
                                                {{ $it->menu->name ?? 'صنف' }} <b class="font-mono text-blue-600">x{{ $it->quantity }}</b>
                                            </span>
                                        @endforeach
                                    </div>
                                </td>

                                {{-- طريقة الدفع --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @php $pm = strtolower($inv->payment_method ?? ''); @endphp
                                    @if($pm === 'instapay')
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                                            📱 إنستا باي
                                        </span>
                                    @elseif($pm === 'card' || $pm === 'visa')
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 font-mono">
                                            💳 فيزا
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                            💵 كاش
                                        </span>
                                    @endif
                                </td>

                                {{-- المبلغ الإجمالي --}}
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono font-black text-emerald-600 text-sm">
                                    {{ number_format($inv->total, 2) }} ج.م
                                </td>

                                {{-- الكاشير --}}
                                <td class="py-3.5 px-4 whitespace-nowrap font-bold text-gray-700 text-[11px]">
                                    {{ $inv->creator->name ?? 'غير معروف' }}
                                </td>

                                {{-- الإجراءات --}}
                                <td class="py-3.5 px-4 text-center whitespace-nowrap print:hidden">
                                    <a href="{{ route('sales-invoices.show', $inv->id) }}" target="_blank"
                                        class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-blue-600 hover:text-white text-gray-700 font-bold text-[11px] inline-flex items-center gap-1 transition">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                        <span>عرض الفاتورة</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-100 print:hidden">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>

{{-- نافذة تعديل العميل المدمجة --}}
<div id="editCustomerModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4" onclick="closeEditCustomerModal()">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl text-right animate-scale-in space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <h3 class="text-sm font-black text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-amber-600"></i>
                <span>تعديل بيانات العميل</span>
            </h3>
            <button type="button" onclick="closeEditCustomerModal()" class="text-gray-400 hover:text-gray-600 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('customers.update', $customer->id) }}" method="POST" class="space-y-3.5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">اسم العميل <span class="text-rose-500">*</span></label>
                <input type="text" name="name" value="{{ $customer->name }}" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">رقم الهاتف</label>
                <input type="tel" name="phone" value="{{ $customer->phone }}"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold font-mono focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">العنوان / المنطقة</label>
                <input type="text" name="address" value="{{ $customer->address }}"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني</label>
                <input type="email" name="email" value="{{ $customer->email }}"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظات خاصة</label>
                <textarea name="notes" rows="2"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden">{{ $customer->notes }}</textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                <button type="button" onclick="closeEditCustomerModal()"
                    class="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-bold transition">
                    إلغاء
                </button>
                <button type="submit"
                    class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-black transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span>تحديث البيانات</span>
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openEditCustomerModal() {
        document.getElementById('editCustomerModal').classList.remove('hidden');
    }
    function closeEditCustomerModal() {
        document.getElementById('editCustomerModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
