@extends('layouts.app')

@section('page_title', 'إدارة ومتابعة الشيفتات')

@section('content')
<div class="container mx-auto space-y-6 pb-16" dir="rtl">

    {{-- رسائل الفلاش --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs animate-fade-in">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ==================== 1. بطاقات الإحصائيات العامة ==================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        {{-- الشيفتات الشغالة حالياً --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-emerald-100 bg-emerald-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-emerald-700 text-xs font-bold mb-1">
                <span>الشيفتات النشطة (شغالين حالياً)</span>
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
            </div>
            <p class="text-2xl sm:text-3xl font-black text-emerald-600 font-mono leading-tight">
                {{ $stats['open_count'] }}
            </p>
            <span class="text-[11px] text-emerald-700/80 mt-1">موظفين يعملون في الوقت الفعلي</span>
        </div>

        {{-- الشيفتات المقفولة اليوم --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>الشيفتات المقفولة اليوم</span>
                <i class="fa-solid fa-lock text-gray-400"></i>
            </div>
            <p class="text-2xl sm:text-3xl font-black text-gray-800 font-mono leading-tight">
                {{ $stats['closed_today_count'] }}
            </p>
            <span class="text-[11px] text-gray-400 mt-1">تم تسويتها وإغلاقها</span>
        </div>

        {{-- مبيعات شيفتات اليوم --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-sky-100 bg-sky-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-sky-700 text-xs font-bold mb-1">
                <span>إجمالي مبيعات اليوم</span>
                <i class="fa-solid fa-arrow-trend-up text-sky-500"></i>
            </div>
            <p class="text-2xl sm:text-3xl font-black text-sky-600 font-mono leading-tight">
                {{ number_format($stats['today_sales'], 2) }} <span class="text-xs font-sans text-sky-400">ج.م</span>
            </p>
            <span class="text-[11px] text-sky-600/80 mt-1">المسجلة في ورديات اليوم</span>
        </div>

        {{-- الفروقات والعجز/الزيادة --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>صافي الفروقات (عجز / زيادة)</span>
                <i class="fa-solid fa-scale-balanced text-indigo-500"></i>
            </div>
            <p class="text-2xl sm:text-3xl font-black font-mono leading-tight {{ $stats['differences_sum'] < 0 ? 'text-rose-600' : ($stats['differences_sum'] > 0 ? 'text-blue-600' : 'text-gray-800') }}">
                {{ ($stats['differences_sum'] > 0 ? '+' : '') . number_format($stats['differences_sum'], 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span>
            </p>
            <span class="text-[11px] text-gray-400 mt-1">إجمالي الفروقات المحسوبة</span>
        </div>
    </div>

    {{-- ==================== 2. قسم إدارة صلاحيات بدء الشيفت للموظفين ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-gray-50/50">
            <div>
                <h3 class="text-sm sm:text-base font-black text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-user-shield text-sky-500"></i>
                    <span>صلاحيات بدء الشيفت للموظفين</span>
                </h3>
                <p class="text-xs text-gray-500 mt-0.5">يمكنك هنا منع أي موظف من فتح شيفت جديد أو إعادة السماح له فوراً بنقرة واحدة</p>
            </div>
            <span class="px-3 py-1 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold">
                {{ $employees->count() }} موظف
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead class="bg-gray-50/70 text-gray-400 text-[11px] font-black border-b border-gray-100">
                    <tr>
                        <th class="py-3 px-4">الموظف</th>
                        <th class="py-3 px-4">الدور الوظيفي</th>
                        <th class="py-3 px-4">حالة الشيفت الحالي</th>
                        <th class="py-3 px-4">حالة الصلاحية</th>
                        <th class="py-3 px-4 text-center">التحكم في المنع / السماح</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($employees as $emp)
                        @php
                            $activeEmpShift = $emp->shifts->first();
                        @endphp
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 font-bold text-gray-800">
                                {{ $emp->name }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold bg-gray-100 text-gray-700">
                                    {{ $emp->role }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($activeEmpShift)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>شيفت مفتوح #{{ $activeEmpShift->id }}</span>
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">لا يوجد شيفت نشط</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                @if($emp->canStartShift())
                                    <span class="inline-flex items-center gap-1 text-emerald-700 font-bold">
                                        <i class="fa-solid fa-circle-check text-emerald-500"></i>
                                        <span>مسموح له ببدء الشيفت</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-rose-700 font-bold bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                        <i class="fa-solid fa-ban text-rose-500"></i>
                                        <span>ممنوع من بدء الشيفت</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">
                                <form action="{{ route('shifts.toggle_block', $emp->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    @if($emp->canStartShift())
                                        <button type="submit" onclick="return confirm('هل أنت متأكد من رغبتك في منع الموظف {{ $emp->name }} من بدء أي شيفت جديد؟');"
                                            class="px-3.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs flex items-center gap-1.5 transition active:scale-95">
                                            <i class="fa-solid fa-ban"></i>
                                            <span>منع من بدء الشيفت</span>
                                        </button>
                                    @else
                                        <button type="submit"
                                            class="px-3.5 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold text-xs flex items-center gap-1.5 transition active:scale-95">
                                            <i class="fa-solid fa-check"></i>
                                            <span>السماح ببدء الشيفت</span>
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== 3. فلاتر البحث والجدول ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-4 sm:p-5">
        <form method="GET" action="{{ route('shifts.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            {{-- فلتر الحالة --}}
            <div>
                <label class="block font-bold text-gray-600 mb-1">حالة الشيفت</label>
                <select name="status" class="w-full py-2.5 px-3 rounded-xl border border-gray-200 outline-none focus:border-sky-500 bg-white font-bold text-gray-700">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>جميع الحالات</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>🟢 شغالين حالياً (مفتوح)</option>
                    <option value="closed" {{ request('status') == 'closed' ? 'selected' : '' }}>🔒 الشيفتات المقفولة</option>
                </select>
            </div>

            {{-- فلتر الموظف --}}
            <div>
                <label class="block font-bold text-gray-600 mb-1">الموظف</label>
                <select name="employee_id" class="w-full py-2.5 px-3 rounded-xl border border-gray-200 outline-none focus:border-sky-500 bg-white font-bold text-gray-700">
                    <option value="all">جميع الموظفين</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ $emp->name }} ({{ $emp->role }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- فلتر التاريخ السريع --}}
            <div>
                <label class="block font-bold text-gray-600 mb-1">فترة التاريخ</label>
                <select name="date_filter" class="w-full py-2.5 px-3 rounded-xl border border-gray-200 outline-none focus:border-sky-500 bg-white font-bold text-gray-700">
                    <option value="">كل التواريخ</option>
                    <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>اليوم</option>
                    <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>أمس</option>
                    <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                    <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                </select>
            </div>

            {{-- زر التطبيق والإلغاء --}}
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2.5 rounded-xl bg-gray-800 hover:bg-gray-900 text-white font-bold transition flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>تصفية</span>
                </button>
                <a href="{{ route('shifts.index') }}" class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold transition flex items-center justify-center">
                    إعادة تعيين
                </a>
            </div>
        </form>
    </div>

    {{-- ==================== 4. جدول جميع الشيفتات ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm sm:text-base font-black text-gray-800">سجل شيفتات الموظفين</h3>
            <span class="text-xs font-bold text-gray-400">إجمالي النتائج: {{ $shifts->total() }}</span>
        </div>

        @if($shifts->isEmpty())
            <div class="p-12 text-center text-gray-400 space-y-2">
                <i class="fa-regular fa-clock text-4xl text-gray-300"></i>
                <p class="font-bold text-gray-600">لا توجد شيفتات مسجلة مطابقة للفلاتر المحددة</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] font-black border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4">رقم الشيفت</th>
                            <th class="py-3 px-4">الموظف</th>
                            <th class="py-3 px-4">الحالة</th>
                            <th class="py-3 px-4">وقت البدء والانتهاء</th>
                            <th class="py-3 px-4">العهدة</th>
                            <th class="py-3 px-4">المبيعات</th>
                            <th class="py-3 px-4">المصروفات</th>
                            <th class="py-3 px-4">المطلوب توريده</th>
                            <th class="py-3 px-4">الفعلي</th>
                            <th class="py-3 px-4">الفرق</th>
                            <th class="py-3 px-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($shifts as $s)
                            <tr class="hover:bg-gray-50/80 transition">
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-800">
                                    #{{ $s->id }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-gray-900">{{ $s->user->name ?? 'غير معروف' }}</div>
                                    <span class="text-[10px] text-gray-400">{{ $s->user->role ?? '' }}</span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($s->isOpen())
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span>شغال حالياً</span>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-gray-100 text-gray-600">
                                            <i class="fa-solid fa-lock text-[10px]"></i>
                                            <span>مغلق</span>
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono text-[11px]">
                                    <div>{{ $s->start_time->format('Y-m-d h:i A') }}</div>
                                    <div class="text-gray-400 text-[10px]">
                                        {{ $s->end_time ? $s->end_time->format('h:i A') : 'ما زال يعمل' }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-gray-600 whitespace-nowrap">
                                    {{ number_format($s->opening_float, 2) }}
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-mono font-bold text-emerald-600 block">
                                        {{ number_format($s->totalSales(), 2) }}
                                    </span>
                                    <div class="text-[9px] text-gray-400 font-mono mt-0.5">
                                        كاش: {{ number_format($s->currentCashSales(), 0) }} | فيزا: {{ number_format($s->currentCardSales(), 0) }} | إنستا: {{ number_format($s->currentInstaPaySales(), 0) }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold text-rose-600 whitespace-nowrap">
                                    {{ number_format($s->expenses_total, 2) }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-black text-indigo-700 whitespace-nowrap">
                                    {{ number_format($s->expected_cash, 2) }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold whitespace-nowrap">
                                    {{ $s->actual_cash !== null ? number_format($s->actual_cash, 2) : '--' }}
                                </td>
                                <td class="py-3.5 px-4 font-mono font-bold whitespace-nowrap">
                                    @if($s->difference !== null)
                                        <span class="px-2 py-0.5 rounded-md text-[11px] {{ $s->difference < 0 ? 'bg-rose-50 text-rose-700' : ($s->difference > 0 ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700') }}">
                                            {{ ($s->difference > 0 ? '+' : '') . number_format($s->difference, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">--</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- زر تفاصيل الشيفت --}}
                                        <a href="{{ route('shifts.show', $s->id) }}" class="px-3 py-1.5 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 font-bold text-xs flex items-center gap-1 transition">
                                            <i class="fa-solid fa-list-check"></i>
                                            <span>التفاصيل</span>
                                        </a>

                                        {{-- إذا كان الشيفت مفتوحاً، يظهر زر للمدير لإغلاقه --}}
                                        @if($s->isOpen())
                                            <button type="button" onclick="openManagerCloseModal({{ $s->id }}, '{{ $s->user->name ?? '' }}', {{ $s->expected_cash }})"
                                                class="px-3 py-1.5 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 font-bold text-xs flex items-center gap-1 transition">
                                                <i class="fa-solid fa-lock"></i>
                                                <span>إغلاق الشيفت</span>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($shifts->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $shifts->links() }}
                </div>
            @endif
        @endif
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

            <div class="bg-gray-50 p-3 rounded-xl border border-gray-200">
                <span class="text-gray-500 block mb-0.5">المبلغ المطلوب توريده في الدرج:</span>
                <strong id="mCloseExpected" class="text-base font-black font-mono text-indigo-700">0.00 ج.م</strong>
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
</script>
@endpush
@endsection
