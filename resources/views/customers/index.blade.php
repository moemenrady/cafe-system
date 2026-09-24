@extends('layouts.app')

@section('title', 'دليل وسجل العملاء - إدارة الكافيه')
@section('page_title', 'سجل وإدارة العملاء (Customer CRM)')

@section('content')
<div class="space-y-6 animate-slide-in pb-16" dir="rtl">

    {{-- ==================== 1. الترويسة وأزرار الإجراءات الرئيسية ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white flex items-center justify-center text-xl shadow-md shadow-indigo-500/20">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>قاعدة بيانات وسجل عملاء الكافيه</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-indigo-50 text-indigo-700 border border-indigo-200 font-mono">
                        {{ number_format($totalCustomersCount) }} عميل
                    </span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">المرجع الاستراتيجي لإدارة ولاء العملاء وتتبع إنفاقهم ومعدل زياراتهم للصالة</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-end sm:self-auto">
            {{-- زر تصدير إكسيل الاحترافي --}}
            <a href="{{ route('customers.export', request()->query()) }}"
                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer"
                title="تصدير شيت إكسيل احترافي مدعوم بترميز UTF-8 للغة العربية">
                <i class="fa-solid fa-file-excel text-sm"></i>
                <span>تصدير شيت إكسيل (Excel)</span>
            </a>

            {{-- زر إضافة عميل جديد --}}
            <button type="button" onclick="openAddCustomerModal()"
                class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-blue-600/20 cursor-pointer">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>إضافة عميل جديد</span>
            </button>
        </div>
    </div>

    {{-- رسائل التنبيهات والنجاح --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 space-y-1">
            <div class="font-black flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>يرجى مراجعة الأخطاء التالية:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 pr-4 text-[11px]">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ==================== 2. كروت المؤشرات العليا (CRM Top KPIs) ==================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        
        {{-- إجمالي العملاء --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold mb-2">
                <span>إجمالي العملاء</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-900 font-mono">{{ number_format($totalCustomersCount) }}</p>
                <p class="text-[11px] text-gray-400 mt-1">مسجلين بقاعدة البيانات</p>
            </div>
        </div>

        {{-- إجمالي إيرادات العملاء --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-emerald-100 bg-emerald-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-emerald-800 text-xs font-bold mb-2">
                <span>إجمالي مبيعات العملاء</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-emerald-600 font-mono">
                    {{ number_format($totalRevenueFromCustomers, 2) }} <span class="text-xs font-sans">ج.م</span>
                </p>
                <p class="text-[11px] text-emerald-700 mt-1">عبر فواتير المبيعات</p>
            </div>
        </div>

        {{-- متوسط إنفاق العميل --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-blue-100 bg-blue-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-blue-800 text-xs font-bold mb-2">
                <span>متوسط إنفاق العميل</span>
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <i class="fa-solid fa-calculator"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-blue-600 font-mono">
                    {{ number_format($averageCustomerSpend, 2) }} <span class="text-xs font-sans">ج.م</span>
                </p>
                <p class="text-[11px] text-blue-700 mt-1">القيمة الدائمة (LTV)</p>
            </div>
        </div>

        {{-- كبار العملاء VIP --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-amber-100 bg-amber-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-amber-800 text-xs font-bold mb-2">
                <span>كبار العملاء (VIP)</span>
                <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center">
                    <i class="fa-solid fa-crown"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-amber-600 font-mono">{{ number_format($vipCustomersCount) }}</p>
                <p class="text-[11px] text-amber-700 mt-1">صرفوا +2000 ج.م أو +15 زيارة</p>
            </div>
        </div>

        {{-- نشط هذا الشهر --}}
        <div class="bg-white p-4 sm:p-5 rounded-3xl border border-purple-100 bg-purple-50/20 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-purple-800 text-xs font-bold mb-2">
                <span>عملاء نشطون هذا الشهر</span>
                <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-purple-600 font-mono">{{ number_format($activeThisMonthCount) }}</p>
                <p class="text-[11px] text-purple-700 mt-1">خلال شهر {{ now()->translatedFormat('F') }}</p>
            </div>
        </div>

    </div>

    {{-- ==================== 3. شريط البحث والتصفية المتقدمة ==================== --}}
    <div class="bg-white rounded-3xl p-5 border border-gray-100 shadow-xs space-y-4">
        <form action="{{ route('customers.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                
                {{-- البحث بالاسم أو الهاتف --}}
                <div class="lg:col-span-4">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        <i class="fa-solid fa-magnifying-glass text-gray-400 ml-1"></i> بحث باسم العميل أو رقم الهاتف
                    </label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="اكتب اسم العميل، رقم الهاتف، أو العنوان..."
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl pr-9 pl-3 py-2.5 text-xs text-gray-800 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <i class="fa-solid fa-user absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    </div>
                </div>

                {{-- فلتر تصنيف العميل --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">تصنيف العميل</label>
                    <select name="vip_type"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-800 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <option value="">جميع التصنيفات</option>
                        <option value="vip" {{ request('vip_type') === 'vip' ? 'selected' : '' }}>⭐ عملاء VIP (+2000 ج)</option>
                        <option value="regular" {{ request('vip_type') === 'regular' ? 'selected' : '' }}>🌟 عملاء دائمون (500 - 2000 ج)</option>
                        <option value="new" {{ request('vip_type') === 'new' ? 'selected' : '' }}>🌱 عملاء جدد (&lt; 500 ج)</option>
                    </select>
                </div>

                {{-- من تاريخ التسجيل --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">تاريخ التسجيل (من)</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-800 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- إلى تاريخ التسجيل --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">تاريخ التسجيل (إلى)</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-800 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- الترتيب --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">ترتيب حسب</label>
                    <select name="sort_by"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-800 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <option value="total_spent" {{ request('sort_by') === 'total_spent' ? 'selected' : '' }}>💰 الأكثر إنفاقاً</option>
                        <option value="visits" {{ request('sort_by') === 'visits' ? 'selected' : '' }}>🔁 الأكثر زيارات</option>
                        <option value="latest_visit" {{ request('sort_by') === 'latest_visit' ? 'selected' : '' }}>🕒 أحدث زيارة</option>
                        <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>🔤 أبجدياً بالاسم</option>
                    </select>
                </div>
            </div>

            {{-- فلاتر إضافية قابلة للطي أو أزرار التطبيق --}}
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-2 border-t border-gray-100">
                <div class="flex items-center gap-3 text-xs text-gray-500">
                    <div class="flex items-center gap-1.5">
                        <span>الحد الأدنى للإنفاق:</span>
                        <input type="number" step="10" name="min_spent" value="{{ request('min_spent') }}" placeholder="0"
                            class="w-20 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1 text-center font-mono text-xs">
                        <span>ج.م</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span>أقل عدد زيارات:</span>
                        <input type="number" name="min_visits" value="{{ request('min_visits') }}" placeholder="1"
                            class="w-16 bg-gray-50 border border-gray-200 rounded-lg px-2 py-1 text-center font-mono text-xs">
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto">
                    @if(request()->anyFilled(['search', 'vip_type', 'date_from', 'date_to', 'sort_by', 'min_spent', 'min_visits']))
                        <a href="{{ route('customers.index') }}"
                            class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition">
                            <i class="fa-solid fa-arrow-rotate-right ml-1"></i> إعادة ضبط
                        </a>
                    @endif
                    <button type="submit"
                        class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black transition flex items-center gap-1.5 shadow-sm">
                        <i class="fa-solid fa-filter"></i>
                        <span>تطبيق الفلترة</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ==================== 4. جدول العملاء وسجلاتهم ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        @if($customers->isEmpty())
            <div class="p-16 text-center text-gray-400 space-y-3">
                <div class="w-16 h-16 rounded-full bg-gray-50 text-gray-300 mx-auto flex items-center justify-center text-3xl">
                    <i class="fa-solid fa-user-slash"></i>
                </div>
                <h3 class="text-sm font-bold text-gray-700">لا توجد سجلات عملاء مطابقة للبحث أو الفلتر</h3>
                <p class="text-xs text-gray-400">يمكنك تعديل خيارات الفلترة أو تسجيل عميل جديد الآن بكل سهولة</p>
                <div class="pt-2">
                    <button type="button" onclick="openAddCustomerModal()"
                        class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition">
                        <i class="fa-solid fa-plus ml-1"></i> إضافة أول عميل
                    </button>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead class="bg-gray-50/70 text-gray-400 text-[11px] font-black border-b border-gray-100">
                        <tr>
                            <th class="py-3 px-4 text-center">#</th>
                            <th class="py-3 px-4">بيانات العميل</th>
                            <th class="py-3 px-4">رقم الهاتف</th>
                            <th class="py-3 px-4 text-center">تصنيف العميل</th>
                            <th class="py-3 px-4 text-center">عدد الزيارات</th>
                            <th class="py-3 px-4">إجمالي المدفوعات</th>
                            <th class="py-3 px-4">متوسط الفاتورة</th>
                            <th class="py-3 px-4">آخر زيارة</th>
                            <th class="py-3 px-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($customers as $customer)
                            @php
                                $spent = (float) ($customer->invoices_sum_total ?? $customer->total_spent);
                                $visits = (int) ($customer->invoices_count ?? $customer->visits_count);
                                $avg = $visits > 0 ? round($spent / $visits, 2) : 0;
                                $vip = $customer->vip_badge;
                            @endphp
                            <tr class="hover:bg-gray-50/80 transition-colors group">
                                {{-- #ID --}}
                                <td class="py-3.5 px-4 text-center font-mono font-bold text-gray-400">
                                    {{ $customer->id }}
                                </td>

                                {{-- اسم العميل والعنوان --}}
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center text-xs font-black shrink-0">
                                            {{ mb_substr($customer->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('customers.show', $customer->id) }}"
                                                class="font-black text-gray-900 hover:text-blue-600 transition block text-xs">
                                                {{ $customer->name }}
                                            </a>
                                            <span class="text-[10px] text-gray-400 flex items-center gap-1 mt-0.5">
                                                <i class="fa-solid fa-location-dot text-gray-300"></i>
                                                {{ $customer->address ?: 'العنوان غير مسجل' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                {{-- الهاتف --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if($customer->phone)
                                        <div class="flex items-center gap-1.5 font-mono font-bold text-gray-800">
                                            <span>{{ $customer->phone }}</span>
                                            <a href="https://wa.me/2{{ preg_replace('/[^0-9]/', '', $customer->phone) }}" target="_blank"
                                                class="w-5 h-5 rounded-full bg-emerald-50 hover:bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px] transition"
                                                title="مراسلة عبر واتساب">
                                                <i class="fa-brands fa-whatsapp"></i>
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-gray-300 font-mono">غير مسجل</span>
                                    @endif
                                </td>

                                {{-- التصنيف --}}
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $vip['class'] }}">
                                        <i class="{{ $vip['icon'] }} text-[9px]"></i>
                                        <span>{{ $vip['label'] }}</span>
                                    </span>
                                </td>

                                {{-- عدد الزيارات --}}
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 rounded-xl bg-gray-100 text-gray-800 font-mono font-black text-xs">
                                        {{ $visits }} زيارة
                                    </span>
                                </td>

                                {{-- إجمالي المدفوعات --}}
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span class="font-mono font-black text-emerald-600 text-sm">
                                        {{ number_format($spent, 2) }} <span class="text-[10px] text-emerald-500 font-sans">ج.م</span>
                                    </span>
                                </td>

                                {{-- متوسط الفاتورة --}}
                                <td class="py-3.5 px-4 whitespace-nowrap font-mono font-bold text-gray-700">
                                    {{ number_format($avg, 2) }} ج
                                </td>

                                {{-- آخر زيارة --}}
                                <td class="py-3.5 px-4 whitespace-nowrap text-gray-500 text-[11px]">
                                    @if($customer->last_visit_date)
                                        <span title="{{ $customer->last_visit_date->format('Y-m-d H:i') }}">
                                            {{ $customer->last_visit_date->diffForHumans() }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">لا يوجد</span>
                                    @endif
                                </td>

                                {{-- الإجراءات --}}
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- زر عرض الملف وكشف الحساب --}}
                                        <a href="{{ route('customers.show', $customer->id) }}"
                                            class="w-7 h-7 rounded-xl bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 flex items-center justify-center transition"
                                            title="عرض الملف وسجل الزيارات الكامل">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>

                                        {{-- زر تصدير سجل هذا العميل --}}
                                        <a href="{{ route('customers.exportSingle', $customer->id) }}"
                                            class="w-7 h-7 rounded-xl bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-700 flex items-center justify-center transition"
                                            title="تصدير شيت إكسيل لهذا العميل">
                                            <i class="fa-solid fa-file-excel text-xs"></i>
                                        </a>

                                        {{-- زر التعديل --}}
                                        <button type="button" onclick="openEditCustomerModal({{ json_encode($customer) }})"
                                            class="w-7 h-7 rounded-xl bg-amber-50 hover:bg-amber-600 hover:text-white text-amber-700 flex items-center justify-center transition cursor-pointer"
                                            title="تعديل بيانات العميل">
                                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                                        </button>

                                        {{-- زر الحذف --}}
                                        <button type="button" onclick="confirmDeleteCustomer({{ $customer->id }}, '{{ addslashes($customer->name) }}')"
                                            class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-600 hover:text-white text-rose-700 flex items-center justify-center transition cursor-pointer"
                                            title="حذف العميل">
                                            <i class="fa-regular fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- الترقيم وتذييل الجدول --}}
            <div class="p-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500">
                <div>
                    عرض {{ $customers->firstItem() ?? 0 }} إلى {{ $customers->lastItem() ?? 0 }} من أصل <strong>{{ $customers->total() }}</strong> عميل مسجل
                </div>
                <div>
                    {{ $customers->links() }}
                </div>
            </div>
        @endif
    </div>

</div>

{{-- ==================== نافذة منبثقة: إضافة عميل جديد ==================== --}}
<div id="addCustomerModal" class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center hidden p-4" onclick="closeAddCustomerModal()">
    <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl text-right animate-scale-in space-y-4" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <h3 class="text-sm font-black text-gray-900 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-blue-600"></i>
                <span>إضافة عميل جديد للنظام</span>
            </h3>
            <button type="button" onclick="closeAddCustomerModal()" class="text-gray-400 hover:text-gray-600 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="{{ route('customers.store') }}" method="POST" class="space-y-3.5">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">اسم العميل <span class="text-rose-500">*</span></label>
                <input type="text" name="name" required placeholder="مثال: أحمد محمود..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">رقم الهاتف (اختياري)</label>
                <input type="tel" name="phone" placeholder="01xxxxxxxxx"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold font-mono focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">العنوان / المنطقة (اختياري)</label>
                <input type="text" name="address" placeholder="مثال: التجمع الخامس، المعادي..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني (اختياري)</label>
                <input type="email" name="email" placeholder="example@domain.com"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظات خاصة وتفضيلات العميل</label>
                <textarea name="notes" rows="2" placeholder="مشروبه المفضل، تفضيل السكر، طاولة معينة..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden"></textarea>
            </div>

            <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                <button type="button" onclick="closeAddCustomerModal()"
                    class="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-bold transition">
                    إلغاء
                </button>
                <button type="submit"
                    class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-black transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-check"></i>
                    <span>حفظ العميل</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==================== نافذة منبثقة: تعديل بيانات العميل ==================== --}}
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

        <form id="editCustomerForm" method="POST" class="space-y-3.5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">اسم العميل <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="edit_name" required
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">رقم الهاتف</label>
                <input type="tel" name="phone" id="edit_phone"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-bold font-mono focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">العنوان / المنطقة</label>
                <input type="text" name="address" id="edit_address"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">البريد الإلكتروني</label>
                <input type="email" name="email" id="edit_email"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظات خاصة</label>
                <textarea name="notes" id="edit_notes" rows="2"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-2 text-xs focus:ring-2 focus:ring-blue-500 focus:outline-hidden"></textarea>
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

{{-- ==================== نموذج تأكيد حذف العميل ==================== --}}
<form id="deleteCustomerForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    function openAddCustomerModal() {
        document.getElementById('addCustomerModal').classList.remove('hidden');
    }
    function closeAddCustomerModal() {
        document.getElementById('addCustomerModal').classList.add('hidden');
    }

    function openEditCustomerModal(customer) {
        document.getElementById('editCustomerForm').action = `/customers/${customer.id}`;
        document.getElementById('edit_name').value = customer.name || '';
        document.getElementById('edit_phone').value = customer.phone || '';
        document.getElementById('edit_address').value = customer.address || '';
        document.getElementById('edit_email').value = customer.email || '';
        document.getElementById('edit_notes').value = customer.notes || '';
        document.getElementById('editCustomerModal').classList.remove('hidden');
    }
    function closeEditCustomerModal() {
        document.getElementById('editCustomerModal').classList.add('hidden');
    }

    function confirmDeleteCustomer(id, name) {
        if (confirm(`هل أنت متأكد من حذف العميل "${name}"؟\nملاحظة: سيتم فك ارتباط العميل بالسجلات للحفاظ على دقة الفواتير والحسابات المالية.`)) {
            const form = document.getElementById('deleteCustomerForm');
            form.action = `/customers/${id}`;
            form.submit();
        }
    }
</script>
@endpush
@endsection
