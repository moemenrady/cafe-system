@extends('layouts.app')

@section('page_title', 'صفحة الشيفت - الموظف')

@section('content')
<div class="container mx-auto space-y-5 pb-16" dir="rtl">

    {{-- رسائل التنبيه والنجاح --}}
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

    {{-- تنبيه التحويل التلقائي عند محاولة إجراء بدون شيفت --}}
    @if(session('shift_required_alert'))
        <div class="bg-amber-50 border border-amber-300 text-amber-900 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-sm animate-fade-in">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 text-lg">
                <i class="fa-solid fa-bell-concierge"></i>
            </div>
            <div>
                <p class="font-black text-amber-900 leading-tight">تنبيه هام</p>
                <p class="text-xs text-amber-700 mt-0.5">{{ session('shift_required_alert') }}</p>
            </div>
        </div>
    @endif

    {{-- ========================================================================= --}}
    {{-- الحالة الأولى: الموظف لا يملك شيفت مفتوح حالياً                             --}}
    {{-- ========================================================================= --}}
    @if(!$activeShift)
        <div class="max-w-2xl mx-auto space-y-6 pt-4">

            {{-- فحص هل الموظف ممنوع من بدء الشيفت --}}
            @if(!$user->canStartShift())
                <div class="bg-red-50 border-2 border-red-300 rounded-3xl p-6 sm:p-8 text-center space-y-3 shadow-sm">
                    <div class="w-16 h-16 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center mx-auto text-2xl shadow-inner">
                        <i class="fa-solid fa-ban"></i>
                    </div>
                    <h3 class="text-xl font-black text-red-900">تم منعك من بدء الشيفت</h3>
                    <p class="text-xs sm:text-sm text-red-700 max-w-md mx-auto leading-relaxed">
                        لقد تم تقييد صلاحيتك لبدء أي شيفت جديد بواسطة إدارة الكافيه. يرجى التواصل مع المشرف أو المدير العام لإعادة تفعيل الصلاحية.
                    </p>
                </div>
            @else
                {{-- كارت بدء الشيفت --}}
                <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 text-white flex items-center justify-center text-2xl shadow-md shadow-sky-500/20">
                            <i class="fa-solid fa-clock"></i>
                        </div>
                        <div>
                            <h2 class="text-lg sm:text-xl font-black text-gray-800">بدء شيفت جديد</h2>
                            <p class="text-xs text-gray-500 mt-0.5">مرحباً {{ $user->name }}، ابدأ ورديتك لتتمكن من استخدام نقطة البيع وتسجيل الحركات.</p>
                        </div>
                    </div>

                    <form action="{{ route('shifts.store') }}" method="POST" class="space-y-5">
                        @csrf
                        <div>
                            <label for="opening_float" class="block text-xs font-black text-gray-700 mb-2">
                                العهدة النقدية الافتتاحية (المبلغ في الدرج حالياً)
                            </label>
                            <div class="relative">
                                <input type="number" step="0.5" min="0" name="opening_float" id="opening_float"
                                    value="0" required
                                    class="w-full pl-14 pr-4 py-3.5 rounded-2xl border border-gray-200 focus:border-sky-500 focus:ring-2 focus:ring-sky-200 outline-none text-xl font-black text-gray-800 font-mono transition">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">ج.م</span>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <span class="text-[11px] text-gray-400 font-bold">خيارات سريعة:</span>
                                <button type="button" onclick="document.getElementById('opening_float').value = 0" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-bold hover:bg-gray-200 transition">0</button>
                                <button type="button" onclick="document.getElementById('opening_float').value = 100" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-bold hover:bg-gray-200 transition">100</button>
                                <button type="button" onclick="document.getElementById('opening_float').value = 200" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-bold hover:bg-gray-200 transition">200</button>
                                <button type="button" onclick="document.getElementById('opening_float').value = 500" class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 text-xs font-bold hover:bg-gray-200 transition">500</button>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-4 rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 hover:from-sky-600 hover:to-blue-700 text-white font-black text-sm sm:text-base flex items-center justify-center gap-2 shadow-lg shadow-sky-500/25 transition active:scale-[0.99]">
                            <i class="fa-solid fa-play"></i>
                            <span>تأكيد وبدء الشيفت الآن</span>
                        </button>
                    </form>
                </div>
            @endif

            {{-- سجل آخر الشيفتات المغلقة للموظف --}}
            @if(isset($recentShifts) && $recentShifts->count() > 0)
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 space-y-3">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-wider">سجل آخر شيفتاتك السابقة</h3>
                    <div class="divide-y divide-gray-100">
                        @foreach($recentShifts as $prev)
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-bold text-gray-800">شيفت #{{ $prev->id }} - {{ $prev->start_time->format('Y-m-d') }}</p>
                                    <p class="text-gray-400 text-[11px]">
                                        من {{ $prev->start_time->format('h:i A') }} إلى {{ $prev->end_time ? $prev->end_time->format('h:i A') : '--' }}
                                    </p>
                                </div>
                                <div class="text-left">
                                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-700 font-mono font-bold">
                                        المبيعات: {{ number_format($prev->totalSales(), 2) }} ج.م
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>

    {{-- ========================================================================= --}}
    {{-- الحالة الثانية: الموظف لديه شيفت مفتوح وشغال حالياً                       --}}
    {{-- ========================================================================= --}}
    @else
        {{-- 1. رأس الشيفت والمؤقت الحي وزر الإغلاق --}}
        <div class="bg-white rounded-3xl border border-gray-200/80 shadow-xs p-4 sm:p-6 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="relative">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center text-xl shadow-md shadow-emerald-500/30">
                        <i class="fa-solid fa-business-time"></i>
                    </div>
                    <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500"></span>
                    </span>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-black text-gray-800 leading-tight">الشيفت الحالي #{{ $activeShift->id }}</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-black border border-emerald-200">
                            شغال حالياً
                        </span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500 mt-1">
                        <span><i class="fa-regular fa-clock text-gray-400 ml-1"></i> بدأ: <strong class="text-gray-700">{{ $activeShift->start_time->format('h:i A') }}</strong></span>
                        <span>•</span>
                        <span>المدة: <strong class="text-emerald-700 font-mono" id="shiftLiveDuration">--:--:--</strong></span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2.5 w-full md:w-auto">
                <a href="{{ route('pos.index') }}" class="flex-1 md:flex-none px-4 py-2.5 rounded-xl bg-sky-50 text-sky-700 hover:bg-sky-100 font-bold text-xs flex items-center justify-center gap-2 border border-sky-200 transition">
                    <i class="fa-solid fa-cash-register"></i>
                    <span>شاشة البيع (POS)</span>
                </a>

                <button type="button" onclick="openCloseShiftModal()"
                    class="flex-1 md:flex-none px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-black text-xs flex items-center justify-center gap-2 shadow-sm shadow-rose-600/30 transition active:scale-95">
                    <i class="fa-solid fa-lock"></i>
                    <span>إغلاق الشيفت</span>
                </button>
            </div>
        </div>

        {{-- 2. بطاقات المؤشرات المالية للشيفت الحالية --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            {{-- العهدة الافتتاحية --}}
            <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                    <span>العهدة الافتتاحية</span>
                    <i class="fa-solid fa-vault text-gray-400"></i>
                </div>
                <p class="text-lg sm:text-xl font-black text-gray-800 font-mono leading-tight">
                    {{ number_format($summary['opening_float'], 2) }} <span class="text-xs text-gray-400 font-sans">ج.م</span>
                </p>
            </div>

            {{-- إجمالي الدخل / المبيعات --}}
            <div class="bg-white p-4 rounded-2xl border border-emerald-100 bg-emerald-50/20 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-emerald-700 text-xs font-bold mb-1">
                    <span>إجمالي المبيعات (الدخل)</span>
                    <i class="fa-solid fa-arrow-trend-up text-emerald-500"></i>
                </div>
                <p class="text-lg sm:text-xl font-black text-emerald-600 font-mono leading-tight">
                    {{ number_format($summary['total_sales'], 2) }} <span class="text-xs text-emerald-500 font-sans">ج.م</span>
                </p>
                <div class="text-[10px] text-gray-500 font-mono mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <span>كاش: {{ number_format($summary['cash_sales'], 0) }}</span>
                    <span class="text-gray-300">|</span>
                    <span>فيزا: {{ number_format($summary['card_sales'], 0) }}</span>
                    <span class="text-gray-300">|</span>
                    <span class="text-blue-600 font-bold">إنستا باي: {{ number_format($summary['instapay_sales'], 0) }}</span>
                </div>
            </div>

            {{-- إجمالي المصروفات --}}
            <div class="bg-white p-4 rounded-2xl border border-rose-100 bg-rose-50/20 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-rose-700 text-xs font-bold mb-1">
                    <span>المصروفات في الشيفت</span>
                    <i class="fa-solid fa-receipt text-rose-500"></i>
                </div>
                <p class="text-lg sm:text-xl font-black text-rose-600 font-mono leading-tight">
                    {{ number_format($summary['expenses_total'], 2) }} <span class="text-xs text-rose-500 font-sans">ج.م</span>
                </p>
                <div class="text-[10px] text-gray-400 mt-1">
                    عدد السجلات: {{ $summary['expenses_count'] }}
                </div>
            </div>

            {{-- المسحوبات النقدية --}}
            <div class="bg-white p-4 rounded-2xl border border-amber-100 bg-amber-50/20 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between text-amber-700 text-xs font-bold mb-1">
                    <span>المسحوبات (Cash Drops)</span>
                    <i class="fa-solid fa-hand-holding-dollar text-amber-500"></i>
                </div>
                <p class="text-lg sm:text-xl font-black text-amber-600 font-mono leading-tight">
                    {{ number_format($summary['cash_drops'], 2) }} <span class="text-xs text-amber-500 font-sans">ج.م</span>
                </p>
            </div>

            {{-- المبلغ المطلوب توريده (الصافي المتوقع) --}}
            <div class="col-span-2 lg:col-span-1 bg-gradient-to-br from-indigo-500 to-blue-700 text-white p-4 rounded-2xl shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between text-indigo-100 text-xs font-bold mb-1">
                    <span>المطلوب توريده (الصافي)</span>
                    <i class="fa-solid fa-money-bill-wave text-indigo-200"></i>
                </div>
                <p class="text-xl sm:text-2xl font-black font-mono leading-tight">
                    {{ number_format($summary['expected_cash'], 2) }} <span class="text-xs font-sans opacity-80">ج.م</span>
                </p>
                <span class="text-[10px] text-indigo-100/80 mt-1">العهدة + الكاش - المصاريف - المسحوبات</span>
            </div>
        </div>

        {{-- 3. جدول وحركات الشيفت (Shift Actions List) --}}
        <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm sm:text-base font-black text-gray-800">سجل حركات وعمليات الشيفت</h3>
                    <p class="text-xs text-gray-400 mt-0.5">جميع عمليات البيع والفواتير والمصروفات وتعديلاتها (اضغط على أي عملية لعرض تفاصيلها الشاملة)</p>
                </div>

                {{-- فلاتر سريعة للنوع --}}
                <div class="flex items-center gap-1.5 flex-wrap text-xs">
                    <a href="{{ route('shifts.my_shift') }}"
                        class="px-3 py-1.5 rounded-xl font-bold transition {{ !request('type') || request('type') == 'all' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                        الكل ({{ $summary['actions_count'] }})
                    </a>
                    <a href="{{ route('shifts.my_shift', ['type' => 'order_created']) }}"
                        class="px-3 py-1.5 rounded-xl font-bold transition {{ request('type') == 'order_created' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' }}">
                        طلبات البيع
                    </a>
                    <a href="{{ route('shifts.my_shift', ['type' => 'invoice_created']) }}"
                        class="px-3 py-1.5 rounded-xl font-bold transition {{ request('type') == 'invoice_created' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                        الفواتير
                    </a>
                    <a href="{{ route('shifts.my_shift', ['type' => 'expense_created']) }}"
                        class="px-3 py-1.5 rounded-xl font-bold transition {{ request('type') == 'expense_created' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                        المصروفات
                    </a>
                </div>
            </div>

            @if($actions->isEmpty())
                <div class="p-12 text-center text-gray-400 space-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto text-xl text-gray-300">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <p class="text-sm font-bold text-gray-600">لا توجد عمليات مسجلة في هذا الشيفت حتى الآن</p>
                    <p class="text-xs text-gray-400">ستظهر هنا أي عملية بيع، فاتورة، أو مصروف تقوم به مباشرة.</p>
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
                                <th class="py-3 px-4 text-center">التفاصيل الكاملة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($actions as $action)
                                <tr onclick="fetchActionDetails({{ $action->id }})" class="hover:bg-sky-50/40 transition cursor-pointer group">
                                    <td class="py-3.5 px-4 font-mono text-gray-500 font-bold whitespace-nowrap">
                                        {{ $action->created_at->format('h:i:s A') }}
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @php
                                            $badgeClass = match($action->action_type) {
                                                'order_created'   => 'bg-blue-50 text-blue-700 border-blue-200',
                                                'invoice_created' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                'expense_created' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                'invoice_updated', 'expense_updated', 'order_updated' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'cash_drop'       => 'bg-purple-50 text-purple-700 border-purple-200',
                                                default           => 'bg-gray-50 text-gray-700 border-gray-200',
                                            };
                                            $icon = match($action->action_type) {
                                                'order_created'   => 'fa-cart-shopping',
                                                'invoice_created' => 'fa-file-invoice-dollar',
                                                'expense_created' => 'fa-receipt',
                                                'invoice_updated', 'expense_updated', 'order_updated' => 'fa-pen-to-square',
                                                'cash_drop'       => 'fa-hand-holding-dollar',
                                                default           => 'fa-circle-info',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $badgeClass }}">
                                            <i class="fa-solid {{ $icon }}"></i>
                                            <span>
                                                @if(str_contains($action->action_type, 'order')) طلب بيع
                                                @elseif(str_contains($action->action_type, 'invoice')) فاتورة مبيعات
                                                @elseif(str_contains($action->action_type, 'expense')) مصروف
                                                @elseif($action->action_type === 'cash_drop') مسحوب نقدي
                                                @else حركة
                                                @endif
                                            </span>
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-800">
                                        {{ $action->action_title }}
                                    </td>
                                    <td class="py-3.5 px-4 whitespace-nowrap">
                                        @php
                                            $pm = strtolower($action->payment_method ?? '');
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
                                        @elseif(!empty($action->payment_method))
                                            <span class="font-mono text-gray-600 font-bold uppercase text-[11px]">
                                                {{ $action->payment_method }}
                                            </span>
                                        @else
                                            <span class="text-gray-300">--</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-black font-mono text-sm whitespace-nowrap">
                                        @if($action->amount > 0)
                                            <span class="{{ str_contains($action->action_type, 'expense') || $action->action_type === 'cash_drop' ? 'text-rose-600' : 'text-emerald-600' }}">
                                                {{ str_contains($action->action_type, 'expense') || $action->action_type === 'cash_drop' ? '-' : '+' }}
                                                {{ number_format($action->amount, 2) }} ج.م
                                            </span>
                                        @else
                                            <span class="text-gray-400">0.00 ج.م</span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-center">
                                        <button type="button" class="w-8 h-8 rounded-xl bg-gray-100 group-hover:bg-sky-500 group-hover:text-white text-gray-500 flex items-center justify-center transition">
                                            <i class="fa-solid fa-eye text-xs"></i>
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
    @endif

</div>

{{-- ========================================================================= --}}
{{-- 4. مودال تفاصيل الأكشن الشامل (Read-Only Action Details Modal)            --}}
{{-- ========================================================================= --}}
<div id="actionDetailsModal" class="fixed inset-0 z-50 bg-black/50 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-2xl max-w-xl w-full max-h-[90vh] flex flex-col overflow-hidden animate-slide-in text-right" dir="rtl">
        {{-- رأس المودال --}}
        <div class="p-5 border-b border-gray-100 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div id="actionModalIcon" class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div>
                    <h3 id="actionModalTitle" class="text-sm sm:text-base font-black text-gray-800 leading-tight">تفاصيل الحركة</h3>
                    <p id="actionModalTime" class="text-[11px] text-gray-400 font-mono mt-0.5">--</p>
                </div>
            </div>
            <button type="button" onclick="closeActionModal()" class="w-8 h-8 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        {{-- جسم المودال القابل للتمرير (Read-Only Content) --}}
        <div id="actionModalBody" class="p-5 overflow-y-auto space-y-4 text-xs">
            <div class="flex items-center justify-center py-10 text-gray-400">
                <i class="fa-solid fa-spinner animate-spin text-xl"></i>
            </div>
        </div>

        {{-- أسفل المودال --}}
        <div class="p-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between shrink-0">
            <span class="text-[11px] text-gray-400 font-bold flex items-center gap-1">
                <i class="fa-solid fa-shield-halved"></i>
                مرجع العمليات (Read Only)
            </span>
            <button type="button" onclick="closeActionModal()" class="px-5 py-2 rounded-xl bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold text-xs transition">
                إغلاق
            </button>
        </div>
    </div>
</div>

{{-- ========================================================================= --}}
{{-- 5. مودال إغلاق الشيفت الحسابي وتسوية الحسابات (Settlement & Close Modal)   --}}
{{-- ========================================================================= --}}
@if($activeShift)
<div id="closeShiftModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-2xl max-w-lg w-full overflow-hidden animate-slide-in text-right" dir="rtl">
        <div class="p-5 bg-gradient-to-br from-rose-500 to-red-700 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-lg">
                    <i class="fa-solid fa-lock"></i>
                </div>
                <div>
                    <h3 class="text-base font-black">إغلاق الشيفت وتسوية الحساب</h3>
                    <p class="text-xs text-rose-100">شيفت #{{ $activeShift->id }} - {{ $user->name }}</p>
                </div>
            </div>
            <button type="button" onclick="closeCloseShiftModal()" class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>

        <form action="{{ route('shifts.close', $activeShift->id) }}" method="POST" class="p-6 space-y-5" onsubmit="return confirm('هل أنت متأكد من رغبتك في إغلاق الشيفت الآن وتسوية الصندوق؟');">
            @csrf

            {{-- ملخص الحسابات المطلوبة --}}
            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-200/80 space-y-2 text-xs">
                <div class="flex justify-between items-center text-gray-600">
                    <span>العهدة الافتتاحية:</span>
                    <strong class="font-mono">{{ number_format($summary['opening_float'], 2) }} ج.م</strong>
                </div>
                <div class="flex justify-between items-center text-emerald-700 font-bold">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-arrow-trend-up text-emerald-500"></i> إجمالي الدخل (المبيعات):</span>
                    <strong class="font-mono text-sm">+ {{ number_format($summary['total_sales'], 2) }} ج.م</strong>
                </div>
                {{-- تفصيل طرق الدفع للمبيعات --}}
                <div class="bg-white/80 rounded-xl p-2.5 border border-gray-200/60 space-y-1.5 text-[11px]">
                    <div class="flex justify-between items-center text-emerald-800">
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-money-bill-wave text-emerald-500"></i> مبيعات كاش (في الدرج):</span>
                        <strong class="font-mono">{{ number_format($summary['cash_sales'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex justify-between items-center text-purple-800">
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-credit-card text-purple-500"></i> مبيعات فيزا (إلكتروني):</span>
                        <strong class="font-mono">{{ number_format($summary['card_sales'], 2) }} ج.م</strong>
                    </div>
                    <div class="flex justify-between items-center text-blue-800">
                        <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-mobile-screen-button text-blue-500"></i> مبيعات إنستا باي (InstaPay إلكتروني):</span>
                        <strong class="font-mono">{{ number_format($summary['instapay_sales'], 2) }} ج.م</strong>
                    </div>
                    <div class="text-[10px] text-gray-400 border-t border-gray-100 pt-1">
                        * ملحوظة: مبيعات الفيزا وإنستا باي تحويلات إلكترونية لا تدخل ضمن النقدية الفعلية بالدرج.
                    </div>
                </div>
                <div class="flex justify-between items-center text-rose-700">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-receipt text-rose-500"></i> إجمالي المصروفات المسجلة:</span>
                    <strong class="font-mono">- {{ number_format($summary['expenses_total'], 2) }} ج.م</strong>
                </div>
                @if($summary['cash_drops'] > 0)
                <div class="flex justify-between items-center text-amber-700">
                    <span class="inline-flex items-center gap-1.5"><i class="fa-solid fa-hand-holding-dollar text-amber-500"></i> المسحوبات النقدية:</span>
                    <strong class="font-mono">- {{ number_format($summary['cash_drops'], 2) }} ج.م</strong>
                </div>
                @endif
                <div class="pt-2 border-t border-gray-200 flex justify-between items-center text-indigo-900 font-bold text-sm">
                    <span>المبلغ المطلوب توريده (النقدية المتوقعة في الدرج):</span>
                    <strong class="font-mono text-base text-indigo-700" id="expectedCashVal" data-val="{{ $summary['expected_cash'] }}">
                        {{ number_format($summary['expected_cash'], 2) }} ج.م
                    </strong>
                </div>
            </div>

            {{-- إدخال المبلغ الفعلي في الدرج --}}
            <div>
                <label for="actual_cash" class="block text-xs font-black text-gray-800 mb-1.5">
                    المبلغ الفعلي الموجود في الدرج (عد النقدية) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <input type="number" step="0.5" min="0" name="actual_cash" id="actual_cash" required
                        oninput="calculateDifference(this.value)"
                        class="w-full pl-14 pr-4 py-3.5 rounded-2xl border border-gray-200 focus:border-rose-500 focus:ring-2 focus:ring-rose-200 outline-none text-xl font-black text-gray-800 font-mono transition"
                        placeholder="0.00">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-400">ج.م</span>
                </div>
                {{-- مؤشر الفارق المباشر --}}
                <div id="diffBadge" class="hidden mt-2 p-2.5 rounded-xl text-xs font-bold flex items-center justify-between">
                    <span id="diffLabel">الفارق:</span>
                    <span id="diffAmount" class="font-mono">0.00 ج.م</span>
                </div>
            </div>

            {{-- ملاحظات الإغلاق --}}
            <div>
                <label for="close_notes" class="block text-xs font-black text-gray-700 mb-1.5">ملاحظات الإغلاق (اختياري)</label>
                <textarea name="notes" id="close_notes" rows="2"
                    class="w-full px-4 py-2.5 rounded-2xl border border-gray-200 focus:border-rose-500 outline-none text-xs text-gray-700 resize-none transition"
                    placeholder="اكتب أي ملاحظة عن سبب العجز أو الزيادة أو تفاصيل التوريد..."></textarea>
            </div>

            {{-- أزرار الإجراءات --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="flex-1 py-3.5 rounded-2xl bg-rose-600 hover:bg-rose-700 text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-rose-600/30 transition active:scale-[0.99]">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>تأكيد إغلاق الشيفت</span>
                </button>
                <button type="button" onclick="closeCloseShiftModal()" class="px-5 py-3.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
    // 1. حساب مؤقت الشيفت المباشر
    @if($activeShift)
    const shiftStartTime = new Date("{{ $activeShift->start_time->toISOString() }}").getTime();

    function updateShiftTimer() {
        const now = new Date().getTime();
        const diff = Math.max(0, now - shiftStartTime);

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        const pad = (n) => n.toString().padStart(2, '0');
        const timerEl = document.getElementById('shiftLiveDuration');
        if (timerEl) {
            timerEl.textContent = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        }
    }

    updateShiftTimer();
    setInterval(updateShiftTimer, 1000);
    @endif

    // 2. إدارة مودال إغلاق الشيفت
    function openCloseShiftModal() {
        const modal = document.getElementById('closeShiftModal');
        if (modal) modal.classList.remove('hidden');
    }
    function closeCloseShiftModal() {
        const modal = document.getElementById('closeShiftModal');
        if (modal) modal.classList.add('hidden');
    }

    // 3. حساب الفرق التلقائي في مودال الإغلاق
    function calculateDifference(val) {
        const expected = parseFloat(document.getElementById('expectedCashVal').dataset.val) || 0;
        const actual = parseFloat(val) || 0;
        const diff = actual - expected;

        const badge = document.getElementById('diffBadge');
        const label = document.getElementById('diffLabel');
        const amount = document.getElementById('diffAmount');

        badge.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'bg-rose-50', 'text-rose-700', 'border-rose-200', 'bg-sky-50', 'text-sky-700', 'border-sky-200');

        if (Math.abs(diff) < 0.01) {
            badge.classList.add('bg-emerald-50', 'text-emerald-700', 'border', 'border-emerald-200');
            label.textContent = 'الحساب مطابق تماماً (لا يوجد عجز أو زيادة)';
            amount.textContent = '0.00 ج.م';
        } else if (diff < 0) {
            badge.classList.add('bg-rose-50', 'text-rose-700', 'border', 'border-rose-200');
            label.textContent = 'تنبيه: يوجد عجز في الدرج بقيمة:';
            amount.textContent = diff.toFixed(2) + ' ج.م';
        } else {
            badge.classList.add('bg-sky-50', 'text-sky-700', 'border', 'border-sky-200');
            label.textContent = 'يوجد زيادة في الدرج بقيمة:';
            amount.textContent = '+' + diff.toFixed(2) + ' ج.م';
        }
    }

    // 4. مودال تفاصيل الأكشن (Read-Only)
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

            // معلومات عامة
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

            // إذا كان طلباً أو فاتورة وله أصناف
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

            // تفاصيل إضافية للطلب (العميل والطاولة والدليفري)
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

            // تفاصيل المصروف
            if (details.category_name) {
                html += `
                    <div class="bg-rose-50/50 border border-rose-100 rounded-2xl p-3.5 space-y-1.5 text-xs text-rose-900">
                        <div><strong>نوع المصروف:</strong> ${details.category_name}</div>
                        ${details.expense_date ? `<div><strong>تاريخ المصروف:</strong> ${details.expense_date}</div>` : ''}
                    </div>
                `;
            }

            // الملاحظات
            if (details.notes || details.note) {
                html += `
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3 text-gray-600">
                        <strong class="text-gray-800 block text-[11px] mb-1">الملاحظات:</strong>
                        <p>${details.notes || details.note}</p>
                    </div>
                `;
            }

            // إذا كان تعديلاً
            if (details.changes) {
                html += `
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 text-amber-900 text-xs">
                        <strong class="block font-black mb-1"><i class="fa-solid fa-pen-to-square"></i> تم إجراء تعديل على هذا السجل</strong>
                        <p class="text-[11px]">تم تحديث وتوثيق البيانات في النظام بنجاح.</p>
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
