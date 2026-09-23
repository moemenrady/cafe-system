@extends('layouts.app')

@section('page_title', 'تفاصيل الشيفت #' . $shift->id)

@section('content')
<div class="container mx-auto space-y-6 pb-16" dir="rtl">

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- 1. رأس الصفحة والرجوع وزر الإغلاق إذا كان شغالاً --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('shifts.index') }}" class="w-10 h-10 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <div class="flex items-center gap-2.5">
                    <h2 class="text-lg sm:text-xl font-black text-gray-900">تفاصيل الشيفت #{{ $shift->id }}</h2>
                    @if($shift->isOpen())
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>شغال حالياً</span>
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 px-3 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                            <i class="fa-solid fa-lock text-[10px]"></i>
                            <span>مغلق</span>
                        </span>
                    @endif
                </div>
                <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                    <span>الموظف: <strong class="text-gray-800">{{ $shift->user->name ?? 'غير معروف' }}</strong> ({{ $shift->user->role ?? '' }})</span>
                    <span>•</span>
                    <span>بدأ: <strong class="text-gray-800 font-mono">{{ $shift->start_time->format('Y-m-d h:i A') }}</strong></span>
                    @if($shift->end_time)
                        <span>•</span>
                        <span>انتهى: <strong class="text-gray-800 font-mono">{{ $shift->end_time->format('Y-m-d h:i A') }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        {{-- إذا كان الشيفت لا يزال مفتوحاً، يظهر زر للمدير لإغلاقه --}}
        @if($shift->isOpen() && auth()->user()->isManager())
            <button type="button" onclick="openManagerCloseModal({{ $shift->id }}, '{{ $shift->user->name ?? '' }}', {{ $summary['expected_cash'] }})"
                class="px-5 py-2.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs flex items-center gap-2 shadow-sm shadow-rose-600/30 transition active:scale-95">
                <i class="fa-solid fa-lock"></i>
                <span>إغلاق الشيفت للموظف</span>
            </button>
        @endif
    </div>

    {{-- 2. بطاقات الحسابات المالية للشيفت --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
            <span class="text-gray-400 text-xs font-bold block mb-1">العهدة الافتتاحية</span>
            <p class="text-lg font-black text-gray-800 font-mono">{{ number_format($summary['opening_float'], 2) }} <span class="text-xs text-gray-400 font-sans">ج.م</span></p>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-emerald-100 bg-emerald-50/20 shadow-xs">
            <span class="text-emerald-700 text-xs font-bold block mb-1">إجمالي المبيعات (الدخل)</span>
            <p class="text-lg font-black text-emerald-600 font-mono">{{ number_format($summary['total_sales'], 2) }} <span class="text-xs text-emerald-500 font-sans">ج.م</span></p>
            <div class="text-[10px] text-gray-500 font-mono mt-1 flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span>كاش: {{ number_format($summary['cash_sales'], 0) }}</span>
                <span class="text-gray-300">|</span>
                <span>فيزا: {{ number_format($summary['card_sales'], 0) }}</span>
                <span class="text-gray-300">|</span>
                <span class="text-blue-600 font-bold">إنستا باي: {{ number_format($summary['instapay_sales'], 0) }}</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-rose-100 bg-rose-50/20 shadow-xs">
            <span class="text-rose-700 text-xs font-bold block mb-1">المصروفات</span>
            <p class="text-lg font-black text-rose-600 font-mono">{{ number_format($summary['expenses_total'], 2) }} <span class="text-xs text-rose-500 font-sans">ج.م</span></p>
            <span class="text-[10px] text-gray-400 mt-1 block">عدد السجلات: {{ $summary['expenses_count'] }}</span>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-indigo-100 bg-indigo-50/20 shadow-xs">
            <span class="text-indigo-700 text-xs font-bold block mb-1">المطلوب توريده (الصافي)</span>
            <p class="text-lg font-black text-indigo-700 font-mono">{{ number_format($summary['expected_cash'], 2) }} <span class="text-xs text-indigo-500 font-sans">ج.م</span></p>
            <span class="text-[10px] text-gray-400 mt-1 block">العهدة + الكاش - المصاريف</span>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
            <span class="text-gray-400 text-xs font-bold block mb-1">الفعلي والفارق</span>
            @if($shift->actual_cash !== null)
                <p class="text-lg font-black font-mono {{ $shift->difference < 0 ? 'text-rose-600' : ($shift->difference > 0 ? 'text-blue-600' : 'text-emerald-600') }}">
                    {{ number_format($shift->actual_cash, 2) }} ج.م
                </p>
                <span class="text-[10px] font-bold font-mono {{ $shift->difference < 0 ? 'text-rose-600' : ($shift->difference > 0 ? 'text-blue-600' : 'text-emerald-600') }}">
                    الفارق: {{ ($shift->difference > 0 ? '+' : '') . number_format($shift->difference, 2) }} ج.م
                </span>
            @else
                <p class="text-gray-400 font-bold text-sm">قيد العمل (لم يغلق)</p>
            @endif
        </div>
    </div>

    @if($shift->closing_notes)
        <div class="bg-amber-50/60 border border-amber-200 rounded-2xl p-4 text-xs text-amber-900">
            <strong class="block font-bold mb-1"><i class="fa-solid fa-note-sticky text-amber-600"></i> ملاحظات الإغلاق:</strong>
            <p class="whitespace-pre-line">{{ $shift->closing_notes }}</p>
        </div>
    @endif

    {{-- ==================== 3. سجل كافة الـ Actions في الشيفت ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm sm:text-base font-black text-gray-800">سجل حركات وعمليات الشيفت</h3>
                <p class="text-xs text-gray-400 mt-0.5">كل عملية بيع، فاتورة، مصروف، أو تعديل قام به الموظف خلال هذا الشيفت</p>
            </div>

            {{-- فلاتر النوع --}}
            <div class="flex items-center gap-1.5 flex-wrap text-xs">
                <a href="{{ route('shifts.show', $shift->id) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition {{ !request('type') || request('type') == 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                    الكل ({{ $summary['actions_count'] }})
                </a>
                <a href="{{ route('shifts.show', [$shift->id, 'type' => 'order_created']) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition {{ request('type') == 'order_created' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                    طلبات البيع
                </a>
                <a href="{{ route('shifts.show', [$shift->id, 'type' => 'invoice_created']) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition {{ request('type') == 'invoice_created' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                    الفواتير
                </a>
                <a href="{{ route('shifts.show', [$shift->id, 'type' => 'expense_created']) }}"
                    class="px-3 py-1.5 rounded-xl font-bold transition {{ request('type') == 'expense_created' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    المصروفات
                </a>
            </div>
        </div>

        @if($actions->isEmpty())
            <div class="p-12 text-center text-gray-400">
                <i class="fa-regular fa-folder-open text-4xl text-gray-300 mb-2"></i>
                <p class="font-bold text-gray-600">لا توجد عمليات مسجلة في هذا الشيفت</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] font-black border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4">الوقت</th>
                            <th class="py-3 px-4">نوع العملية</th>
                            <th class="py-3 px-4">تفاصيل الحركة</th>
                            <th class="py-3 px-4">طريقة الدفع</th>
                            <th class="py-3 px-4">المبلغ</th>
                            <th class="py-3 px-4 text-center">فتح السجل المباشر</th>
                            <th class="py-3 px-4 text-center">المودال الشامل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($actions as $act)
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="py-3.5 px-4 font-mono text-gray-500 font-bold whitespace-nowrap">
                                    {{ $act->created_at->format('h:i:s A') }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @php
                                        $badgeClass = match($act->action_type) {
                                            'order_created'   => 'bg-blue-50 text-blue-700 border-blue-200',
                                            'invoice_created' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'expense_created' => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'invoice_updated', 'expense_updated', 'order_updated' => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'cash_drop'       => 'bg-purple-50 text-purple-700 border-purple-200',
                                            default           => 'bg-gray-50 text-gray-700 border-gray-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[11px] font-bold border {{ $badgeClass }}">
                                        @if(str_contains($act->action_type, 'order')) طلب بيع
                                        @elseif(str_contains($act->action_type, 'invoice')) فاتورة مبيعات
                                        @elseif(str_contains($act->action_type, 'expense')) مصروف
                                        @elseif($act->action_type === 'cash_drop') مسحوب نقدي
                                        @else حركة
                                        @endif
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-gray-800">
                                    {{ $act->action_title }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @php
                                        $pm = strtolower($act->payment_method ?? '');
                                    @endphp
                                    @if($pm === 'instapay')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                                            <i class="fa-solid fa-mobile-screen-button text-[9px]"></i> إنستا باي
                                        </span>
                                    @elseif($pm === 'card' || $pm === 'visa')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200 font-mono">
                                            <i class="fa-solid fa-credit-card text-[9px]"></i> فيزا
                                        </span>
                                    @elseif($pm === 'cash')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                            <i class="fa-solid fa-money-bill-wave text-[9px]"></i> كاش
                                        </span>
                                    @elseif(!empty($act->payment_method))
                                        <span class="font-mono text-gray-600 font-bold uppercase text-[11px]">
                                            {{ $act->payment_method }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">--</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 font-black font-mono text-sm whitespace-nowrap">
                                    @if($act->amount > 0)
                                        <span class="{{ str_contains($act->action_type, 'expense') || $act->action_type === 'cash_drop' ? 'text-rose-600' : 'text-emerald-600' }}">
                                            {{ str_contains($act->action_type, 'expense') || $act->action_type === 'cash_drop' ? '-' : '+' }}
                                            {{ number_format($act->amount, 2) }} ج.م
                                        </span>
                                    @else
                                        <span class="text-gray-400">0.00 ج.م</span>
                                    @endif
                                </td>
                                {{-- زر فتح الفاتورة أو المصروف مباشرة --}}
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @if(str_contains($act->action_type, 'invoice') && $act->model_id)
                                        <a href="{{ route('sales-invoices.show', $act->model_id) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-[11px] transition">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            <span>فتح الفاتورة</span>
                                        </a>
                                    @elseif(str_contains($act->action_type, 'expense') && $act->model_id)
                                        <a href="{{ route('expenses.show', $act->model_id) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-[11px] transition">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            <span>فتح المصروف</span>
                                        </a>
                                    @elseif(str_contains($act->action_type, 'order') && $act->model_id)
                                        <a href="{{ route('orders.show', $act->model_id) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-bold text-[11px] transition">
                                            <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                            <span>فتح الطلب</span>
                                        </a>
                                    @else
                                        <span class="text-gray-300">--</span>
                                    @endif
                                </td>
                                {{-- زر فتح المودال الشامل --}}
                                <td class="py-3.5 px-4 text-center">
                                    <button type="button" onclick="fetchActionDetails({{ $act->id }})"
                                        class="px-2.5 py-1.5 rounded-xl bg-gray-100 hover:bg-sky-500 hover:text-white text-gray-600 text-[11px] font-bold inline-flex items-center gap-1 transition">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                        <span>عرض التفاصيل</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($actions->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $actions->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

{{-- مودال التفاصيل الشاملة --}}
<div id="actionDetailsModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-slide-in text-right" dir="rtl">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between shrink-0">
            <div>
                <h3 id="actionModalTitle" class="text-sm sm:text-base font-black text-gray-800 leading-tight">تفاصيل الحركة</h3>
                <p id="actionModalTime" class="text-[11px] text-gray-400 font-mono mt-0.5">--</p>
            </div>
            <button type="button" onclick="closeActionModal()" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <div id="actionModalBody" class="p-5 overflow-y-auto space-y-4 text-xs">
            <div class="flex items-center justify-center py-10 text-gray-400">
                <i class="fa-solid fa-spinner animate-spin text-xl"></i>
            </div>
        </div>

        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-end shrink-0">
            <button type="button" onclick="closeActionModal()" class="px-5 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs transition">
                إغلاق
            </button>
        </div>
    </div>
</div>

{{-- مودال إغلاق الشيفت بواسطة المدير --}}
<div id="managerCloseShiftModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-2xl max-w-md w-full overflow-hidden animate-slide-in text-right" dir="rtl">
        <div class="p-5 bg-gradient-to-br from-rose-600 to-red-800 text-white flex items-center justify-between">
            <h3 class="text-base font-black">إغلاق شيفت الموظف بواسطة الإدارة</h3>
            <button type="button" onclick="closeManagerCloseModal()" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form id="managerCloseForm" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <p class="text-gray-600">
                أنت على وشك إغلاق شيفت الموظف: <strong id="mCloseEmpName" class="text-gray-900">--</strong>
            </p>

            <div class="bg-gray-50 p-3 rounded-xl border border-gray-200 space-y-1.5">
                <div class="flex justify-between items-center text-gray-500">
                    <span>إجمالي المبيعات:</span>
                    <strong class="font-mono text-gray-800">{{ number_format($summary['total_sales'], 2) }} ج.م</strong>
                </div>
                <div class="flex justify-between items-center text-[10px] text-gray-500 pr-2">
                    <span>كاش: {{ number_format($summary['cash_sales'], 2) }} | فيزا: {{ number_format($summary['card_sales'], 2) }} | إنستا: {{ number_format($summary['instapay_sales'], 2) }}</span>
                </div>
                <div class="border-t border-gray-200 pt-1 flex justify-between items-center text-indigo-900 font-bold">
                    <span>المبلغ المطلوب توريده في الدرج:</span>
                    <strong id="mCloseExpected" class="text-base font-black font-mono text-indigo-700">0.00 ج.م</strong>
                </div>
            </div>

            <div>
                <label for="m_actual_cash" class="block font-bold text-gray-700 mb-1">المبلغ الفعلي المستلم (عد النقدية) *</label>
                <input type="number" step="0.5" min="0" name="actual_cash" id="m_actual_cash" required
                    class="w-full p-3 rounded-xl border border-gray-200 focus:border-rose-500 font-mono font-black text-lg outline-none transition">
            </div>

            <div>
                <label for="m_notes" class="block font-bold text-gray-700 mb-1">ملاحظات الإدارة</label>
                <textarea name="notes" id="m_notes" rows="2"
                    class="w-full p-2.5 rounded-xl border border-gray-200 focus:border-rose-500 outline-none resize-none transition"
                    placeholder="ملاحظات سبب الإغلاق الإداري..."></textarea>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit" class="flex-1 py-3 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 transition">
                    <i class="fa-solid fa-check"></i>
                    <span>تأكيد إغلاق الشيفت للموظف</span>
                </button>
                <button type="button" onclick="closeManagerCloseModal()" class="px-4 py-3 rounded-xl bg-gray-100 text-gray-600 font-bold text-xs">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openManagerCloseModal(shiftId, empName, expected) {
        document.getElementById('mCloseEmpName').textContent = empName;
        document.getElementById('mCloseExpected').textContent = parseFloat(expected).toFixed(2) + ' ج.م';
        document.getElementById('m_actual_cash').value = parseFloat(expected).toFixed(2);
        document.getElementById('managerCloseForm').action = `/shifts/${shiftId}/close`;
        document.getElementById('managerCloseShiftModal').classList.remove('hidden');
    }
    function closeManagerCloseModal() {
        document.getElementById('managerCloseShiftModal').classList.add('hidden');
    }

    function openActionModal() {
        document.getElementById('actionDetailsModal').classList.remove('hidden');
    }
    function closeActionModal() {
        document.getElementById('actionDetailsModal').classList.add('hidden');
    }

    async function fetchActionDetails(actionId) {
        openActionModal();
        const body = document.getElementById('actionModalBody');
        body.innerHTML = `
            <div class="flex items-center justify-center py-12 text-gray-400">
                <i class="fa-solid fa-spinner animate-spin text-2xl"></i>
            </div>
        `;

        try {
            const res = await fetch(`/shifts/actions/${actionId}`, {
                headers: { 'Accept': 'application/json' }
            });
            const json = await res.json();

            if (!json.success || !json.data) {
                body.innerHTML = '<p class="text-rose-500 font-bold text-center py-6">تعذر تحميل بيانات الحركة.</p>';
                return;
            }

            const data = json.data;
            document.getElementById('actionModalTitle').textContent = data.action_title;
            document.getElementById('actionModalTime').textContent = data.created_at + ' (' + data.created_at_human + ')';

            const details = data.details || {};
            let html = '';

            let paymentMethodText = data.payment_method || 'كاش';
            const pmLower = (data.payment_method || '').toLowerCase();
            if (pmLower === 'instapay') {
                paymentMethodText = '📱 إنستا باي (InstaPay)';
            } else if (pmLower === 'card' || pmLower === 'visa') {
                paymentMethodText = '💳 فيزا (Card)';
            } else if (pmLower === 'cash') {
                paymentMethodText = '💵 كاش';
            }

            html += `
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                    <div>
                        <span class="text-gray-400 block text-[10px] font-bold">الموظف المنفذ</span>
                        <span class="font-bold text-gray-800">${data.user_name}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px] font-bold">طريقة الدفع</span>
                        <span class="font-bold text-gray-800 font-mono">${paymentMethodText}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block text-[10px] font-bold">إجمالي المبلغ</span>
                        <span class="font-black text-emerald-600 font-mono text-sm">${parseFloat(data.amount).toFixed(2)} ج.م</span>
                    </div>
                </div>
            `;

            // إذا كانت الحركة إغلاق الشيفت
            if (data.action_type === 'shift_closed') {
                html += `
                    <div class="bg-gray-50 border border-gray-200 rounded-2xl p-4 space-y-2.5 text-xs">
                        <h4 class="font-black text-gray-800 text-sm mb-2 border-b border-gray-200 pb-2">
                            <i class="fa-solid fa-calculator text-indigo-600"></i> تفاصيل تسوية إغلاق الشيفت
                        </h4>
                        <div class="flex justify-between items-center text-gray-600">
                            <span>العهدة الافتتاحية:</span>
                            <span class="font-mono font-bold">${parseFloat(details.opening_float || 0).toFixed(2)} ج.م</span>
                        </div>
                        <div class="flex justify-between items-center text-emerald-700 font-bold">
                            <span>إجمالي المبيعات (الدخل):</span>
                            <span class="font-mono text-sm">+ ${parseFloat(details.total_sales || 0).toFixed(2)} ج.م</span>
                        </div>
                        <div class="bg-white/80 rounded-xl p-2.5 border border-gray-200/60 space-y-1 text-[11px]">
                            <div class="flex justify-between text-emerald-800">
                                <span>- مبيعات كاش (في الدرج):</span>
                                <span class="font-mono font-bold">${parseFloat(details.cash_sales || 0).toFixed(2)} ج.م</span>
                            </div>
                            <div class="flex justify-between text-purple-800">
                                <span>- مبيعات فيزا (إلكتروني):</span>
                                <span class="font-mono font-bold">${parseFloat(details.card_sales || 0).toFixed(2)} ج.م</span>
                            </div>
                            <div class="flex justify-between text-blue-800 font-bold">
                                <span>- مبيعات إنستا باي (InstaPay إلكتروني):</span>
                                <span class="font-mono font-bold">${parseFloat(details.instapay_sales || 0).toFixed(2)} ج.م</span>
                            </div>
                        </div>
                        <div class="flex justify-between items-center text-rose-700">
                            <span>إجمالي المصروفات:</span>
                            <span class="font-mono font-bold">- ${parseFloat(details.expenses_total || 0).toFixed(2)} ج.م</span>
                        </div>
                        ${details.cash_drops > 0 ? `
                        <div class="flex justify-between items-center text-amber-700">
                            <span>المسحوبات النقدية:</span>
                            <span class="font-mono font-bold">- ${parseFloat(details.cash_drops || 0).toFixed(2)} ج.م</span>
                        </div>` : ''}
                        <div class="pt-2 border-t border-gray-200 flex justify-between items-center text-indigo-900 font-bold">
                            <span>المبلغ المطلوب توريده في الدرج:</span>
                            <span class="font-mono text-sm">${parseFloat(details.expected_cash || 0).toFixed(2)} ج.م</span>
                        </div>
                        <div class="flex justify-between items-center text-gray-900 font-bold">
                            <span>المبلغ الفعلي المستلم:</span>
                            <span class="font-mono text-sm">${parseFloat(details.actual_cash || 0).toFixed(2)} ج.م</span>
                        </div>
                        <div class="flex justify-between items-center ${details.difference < 0 ? 'text-rose-600' : (details.difference > 0 ? 'text-blue-600' : 'text-emerald-600')} font-bold">
                            <span>الفارق (عجز / زيادة):</span>
                            <span class="font-mono text-sm">${details.difference > 0 ? '+' : ''}${parseFloat(details.difference || 0).toFixed(2)} ج.م</span>
                        </div>
                    </div>
                `;
            }

            if (details.items && details.items.length > 0) {
                html += `
                    <div>
                        <h4 class="font-black text-gray-700 text-xs mb-2">الأصناف المسجلة (${details.items.length})</h4>
                        <div class="border border-gray-100 rounded-2xl overflow-hidden">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-gray-50 text-gray-400 text-[10px] font-black">
                                    <tr>
                                        <th class="py-2 px-3">الصنف</th>
                                        <th class="py-2 px-3 text-center">الكمية</th>
                                        <th class="py-2 px-3 text-left">السعر</th>
                                        <th class="py-2 px-3 text-left">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 font-mono">
                                    ${details.items.map(item => `
                                        <tr>
                                            <td class="py-2 px-3 font-sans font-bold text-gray-800">${item.name}</td>
                                            <td class="py-2 px-3 text-center font-bold">${item.quantity}</td>
                                            <td class="py-2 px-3 text-left">${parseFloat(item.price).toFixed(2)}</td>
                                            <td class="py-2 px-3 text-left font-black text-gray-900">${parseFloat(item.total).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            if (details.customer_name || details.customer_phone || details.table || details.delivery_address) {
                html += `
                    <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-3.5 space-y-1.5 text-xs text-blue-900">
                        ${details.table ? `<div><strong>الطاولة:</strong> ${details.table}</div>` : ''}
                        ${details.customer_name ? `<div><strong>العميل:</strong> ${details.customer_name} ${details.customer_phone ? '(' + details.customer_phone + ')' : ''}</div>` : ''}
                        ${details.delivery_address ? `<div><strong>عنوان التوصيل:</strong> ${details.delivery_address}</div>` : ''}
                        ${details.delivery_person ? `<div><strong>الطيار / الموصّل:</strong> ${details.delivery_person}</div>` : ''}
                    </div>
                `;
            }

            if (details.category_name) {
                html += `
                    <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-3.5 space-y-1.5 text-xs text-rose-900">
                        <div><strong>نوع المصروف:</strong> ${details.category_name}</div>
                        ${details.expense_date ? `<div><strong>تاريخ المصروف:</strong> ${details.expense_date}</div>` : ''}
                    </div>
                `;
            }

            if (details.notes || details.note) {
                html += `
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3 text-gray-600">
                        <strong class="text-gray-800 block text-[11px] mb-1">الملاحظات:</strong>
                        <p>${details.notes || details.note}</p>
                    </div>
                `;
            }

            body.innerHTML = html;
        } catch (e) {
            body.innerHTML = '<p class="text-rose-500 font-bold text-center py-6">حدث خطأ أثناء جلب التفاصيل.</p>';
        }
    }
</script>
@endpush
@endsection
