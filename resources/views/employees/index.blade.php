@extends('layouts.app')

@section('title', 'إدارة الموظفين والرواتب - نظام الكافيه')
@section('page_title', 'إدارة الموظفين والرواتب والقبض')

@section('content')
<div class="space-y-6 animate-slide-in pb-16" dir="rtl">

    {{-- ==================== 1. الترويسة وأزرار الإجراءات الرئيسية ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center text-xl shadow-md shadow-blue-500/20">
                <i class="fa-solid fa-users-gear"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>إدارة فريق العمل والرواتب الشهرية</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                        {{ number_format($totalEmployeesCount) }} موظف
                    </span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">متابعة شؤون الموظفين، مسيرات الرواتب الشهرية، الخصومات والبونص، والمستحقات المعلقة</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-end sm:self-auto">
            {{-- زر تصدير إكسيل الاحترافي --}}
            <a href="{{ route('employees.export', request()->query()) }}"
                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer"
                title="تصدير شيت إكسيل شامل لجميع الموظفين ورواتبهم مع دعم UTF-8">
                <i class="fa-solid fa-file-excel text-sm"></i>
                <span>تصدير شيت إكسيل (Excel)</span>
            </a>

            {{-- زر إضافة موظف جديد --}}
            <button type="button" onclick="openAddEmployeeModal()"
                class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-blue-600/20 cursor-pointer">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>إضافة موظف جديد</span>
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

    {{-- ==================== 2. كروت المؤشرات المالية والإدارية (Staff KPIs) ==================== --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
        {{-- كارت 1: إجمالي الموظفين --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">فريق العمل</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-gray-900 font-mono">{{ number_format($totalEmployeesCount) }}</div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                    <span>{{ number_format($activeEmployeesCount) }} على رأس العمل</span>
                </div>
            </div>
        </div>

        {{-- كارت 2: إجمالي الرواتب الشهرية --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">كتلة الرواتب الثابتة</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-emerald-600 font-mono">{{ number_format($totalMonthlySalaries, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span></div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5">شهرياً للموظفين النشطين</div>
            </div>
        </div>

        {{-- كارت 3: المصروف لشهر الحالي --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">مدفوع هذا الشهر</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-indigo-600 font-mono">{{ number_format($totalPaidThisMonth, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span></div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5 font-mono">شهر: {{ $currentMonth }}</div>
            </div>
        </div>

        {{-- كارت 4: إجمالي المستحق لكافة الموظفين (المتأخرات) --}}
        <div class="bg-white p-4 rounded-3xl border {{ $totalPendingDues > 0 ? 'border-rose-200 bg-rose-50/20' : 'border-gray-100' }} shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold {{ $totalPendingDues > 0 ? 'text-rose-700' : '' }}">المستحق للموظفين</span>
                <div class="w-8 h-8 rounded-xl {{ $totalPendingDues > 0 ? 'bg-rose-100 text-rose-600 animate-pulse' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-xs">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black {{ $totalPendingDues > 0 ? 'text-rose-600' : 'text-gray-900' }} font-mono">
                    {{ number_format($totalPendingDues, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span>
                </div>
                <div class="text-[11px] {{ $totalPendingDues > 0 ? 'text-rose-500 font-bold' : 'text-gray-400' }} mt-0.5">
                    {{ $totalPendingDues > 0 ? 'رواتب معلقة قيد الصرف' : 'لا توجد مستحقات معلقة' }}
                </div>
            </div>
        </div>

        {{-- كارت 5: حركات الشهر (بونص - خصومات) --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between col-span-2 md:col-span-1">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">بونص وخصومات الشهر</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
            </div>
            <div>
                <div class="flex items-center gap-2 text-xs font-mono font-bold">
                    <span class="text-emerald-600">+{{ number_format($currentMonthBonus, 1) }}</span>
                    <span class="text-gray-300">|</span>
                    <span class="text-rose-500">-{{ number_format($currentMonthDeductions, 1) }}</span>
                </div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5">مكافآت وخصومات الشهر الحالي</div>
            </div>
        </div>
    </div>

    {{-- ==================== 3. شريط البحث والتصفية المتطورة ==================== --}}
    <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs">
        <form action="{{ route('employees.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                {{-- البحث --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1">بحث شامل</label>
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="اسم الموظف، الهاتف، المسمى، البطاقة..."
                            class="w-full pl-3 pr-9 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <i class="fa-solid fa-magnifying-glass absolute right-3 top-2.5 text-gray-400 text-xs"></i>
                    </div>
                </div>

                {{-- فلتر الحالة --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1">حالة العمل</label>
                    <select name="status" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">جميع الحالات</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>على رأس العمل (نشط)</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>متوقف عن العمل</option>
                    </select>
                </div>

                {{-- فلتر المستحقات --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1">المستحقات والقبض</label>
                    <select name="dues_status" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="">الكل</option>
                        <option value="has_dues" {{ request('dues_status') === 'has_dues' ? 'selected' : '' }}>لديهم مستحقات متأخرة لم تصرف</option>
                        <option value="all_paid" {{ request('dues_status') === 'all_paid' ? 'selected' : '' }}>مسدد بالكامل (لا توجد مستحقات)</option>
                    </select>
                </div>

                {{-- الترتيب --}}
                <div>
                    <label class="block text-[11px] font-bold text-gray-500 mb-1">الترتيب حسب</label>
                    <select name="sort_by" class="w-full px-3 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>الأحدث إضافة</option>
                        <option value="salary" {{ request('sort_by') === 'salary' ? 'selected' : '' }}>الأعلى راتباً</option>
                        <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>الاسم (أبجدياً)</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                <a href="{{ route('employees.index') }}"
                    class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition">
                    إعادة ضبط
                </a>
                <button type="submit"
                    class="px-5 py-2 rounded-xl bg-gray-900 hover:bg-black text-white font-bold text-xs transition flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-filter text-[11px]"></i>
                    <span>تطبيق الفلتر</span>
                </button>
            </div>
        </form>
    </div>

    {{-- ==================== 4. جدول الموظفين ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-gray-50/80 text-gray-500 font-black border-b border-gray-100">
                        <th class="py-3.5 px-4"># الكود</th>
                        <th class="py-3.5 px-4">الموظف والمسمى</th>
                        <th class="py-3.5 px-4">رقم الهاتف</th>
                        <th class="py-3.5 px-4">المرتب الشهري</th>
                        <th class="py-3.5 px-4">راتب الشهر الحالي</th>
                        <th class="py-3.5 px-4">المستحق المتأخر</th>
                        <th class="py-3.5 px-4">تاريخ التعيين</th>
                        <th class="py-3.5 px-4 text-center">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employees as $emp)
                        @php
                            $dues = $emp->total_dues;
                            $currentStatus = $emp->current_month_status;
                        @endphp
                        <tr class="hover:bg-blue-50/30 transition group">
                            {{-- كود الموظف --}}
                            <td class="py-3.5 px-4 font-mono font-bold text-gray-400">
                                #{{ $emp->id }}
                            </td>

                            {{-- الموظف والمسمى الوظيفي --}}
                            <td class="py-3.5 px-4">
                                <a href="{{ route('employees.show', $emp->id) }}" class="flex items-center gap-3 group-hover:text-blue-600 transition">
                                    <div class="w-9 h-9 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-600 text-white font-black flex items-center justify-center text-xs shadow-xs">
                                        {{ mb_substr($emp->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="font-black text-gray-900 flex items-center gap-1.5">
                                            <span>{{ $emp->name }}</span>
                                            @if(!$emp->is_active)
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-500">متوقف</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-gray-400 font-medium flex items-center gap-1">
                                            <i class="fa-solid fa-briefcase text-[10px] text-blue-500"></i>
                                            <span>{{ $emp->job_title }}</span>
                                            @if($emp->national_id)
                                                <span class="text-gray-300">|</span>
                                                <span class="font-mono text-[10px]">{{ $emp->national_id }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            </td>

                            {{-- رقم الهاتف مع زر اتصال وواتساب --}}
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-gray-700 font-bold dir-ltr text-right">{{ $emp->phone }}</span>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $emp->phone) }}" target="_blank"
                                        class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white flex items-center justify-center text-[11px] transition"
                                        title="مراسلة واتساب">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </div>
                            </td>

                            {{-- المرتب الشهري --}}
                            <td class="py-3.5 px-4">
                                <span class="font-mono font-black text-gray-900 text-sm">
                                    {{ number_format($emp->base_salary, 2) }}
                                </span>
                                <span class="text-[10px] text-gray-400">ج.م</span>
                            </td>

                            {{-- راتب الشهر الحالي --}}
                            <td class="py-3.5 px-4">
                                @if($currentStatus === 'paid')
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1 w-max">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>مدفوع بالكامل</span>
                                    </span>
                                @elseif($currentStatus === 'partial')
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-blue-50 text-blue-700 border border-blue-200 flex items-center gap-1 w-max">
                                        <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                                        <span>مدفوع جزئياً</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-amber-50 text-amber-700 border border-amber-200 flex items-center gap-1 w-max">
                                        <i class="fa-solid fa-clock text-[10px]"></i>
                                        <span>مستحق معلق</span>
                                    </span>
                                @endif
                            </td>

                            {{-- المستحق المتأخر التراكمي --}}
                            <td class="py-3.5 px-4 font-mono font-bold">
                                @if($dues > 0)
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-exclamation text-[10px]"></i>
                                        <span>{{ number_format($dues, 2) }} ج.م</span>
                                    </span>
                                @else
                                    <span class="text-emerald-600 font-bold text-xs flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-[11px]"></i>
                                        <span>مسدد بالكامل</span>
                                    </span>
                                @endif
                            </td>

                            {{-- تاريخ التعيين --}}
                            <td class="py-3.5 px-4 text-gray-500 font-mono text-[11px]">
                                {{ $emp->hire_date ? $emp->hire_date->format('Y-m-d') : '-' }}
                            </td>

                            {{-- الإجراءات --}}
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    {{-- عرض البروفايل وكشف الرواتب --}}
                                    <a href="{{ route('employees.show', $emp->id) }}"
                                        class="w-7 h-7 rounded-xl bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white flex items-center justify-center transition"
                                        title="عرض ملف الموظف وسجل الرواتب والمستحقات">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>

                                    {{-- تعديل الموظف --}}
                                    <button type="button"
                                        onclick="openEditEmployeeModal({{ json_encode($emp) }})"
                                        class="w-7 h-7 rounded-xl bg-amber-50 hover:bg-amber-600 text-amber-600 hover:text-white flex items-center justify-center transition"
                                        title="تعديل بيانات الموظف">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>

                                    {{-- حذف الموظف --}}
                                    <button type="button"
                                        onclick="confirmDeleteEmployee({{ $emp->id }}, '{{ addslashes($emp->name) }}')"
                                        class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white flex items-center justify-center transition"
                                        title="حذف الموظف">
                                        <i class="fa-solid fa-trash text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <div class="w-16 h-16 rounded-full bg-gray-50 text-gray-300 mx-auto flex items-center justify-center text-2xl mb-3">
                                    <i class="fa-solid fa-users-slash"></i>
                                </div>
                                <div class="font-bold text-gray-600 text-sm">لم يتم العثور على أي موظفين</div>
                                <div class="text-xs text-gray-400 mt-1">جرّب تغيير معايير البحث أو أضف موظفاً جديداً للبدء</div>
                                <button type="button" onclick="openAddEmployeeModal()"
                                    class="mt-4 px-4 py-2 rounded-xl bg-blue-600 text-white font-bold text-xs inline-flex items-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-plus"></i>
                                    <span>إضافة موظف الآن</span>
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- الترقيم الصفحي --}}
        @if($employees->hasPages())
            <div class="p-4 border-t border-gray-100 flex items-center justify-between">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

</div>

{{-- ==================== نافذة إضافة موظف جديد (Modal) ==================== --}}
<div id="addEmployeeModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 animate-scale-up" dir="rtl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h3 class="font-black text-gray-900 text-base">إضافة موظف جديد وتحديد الراتب</h3>
            </div>
            <button type="button" onclick="closeAddEmployeeModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('employees.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                {{-- اسم الموظف --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">اسم الموظف الثلاثي <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="مثال: أحمد محمود إبراهيم"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- رقم الهاتف --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">رقم الهاتف <span class="text-rose-500">*</span></label>
                    <input type="text" name="phone" required placeholder="01xxxxxxxxx"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                </div>

                {{-- المسمى الوظيفي --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">المسمى الوظيفي <span class="text-rose-500">*</span></label>
                    <select name="job_title" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="كاشير">كاشير</option>
                        <option value="باريستا">باريستا</option>
                        <option value="شيف / مطبخ">شيف / مطبخ</option>
                        <option value="ويتر / كابتن صالة">ويتر / كابتن صالة</option>
                        <option value="عامل نظافة وتجهيز">عامل نظافة وتجهيز</option>
                        <option value="مشرف وردية">مشرف وردية</option>
                        <option value="دليفري">دليفري</option>
                        <option value="موظف عام">موظف عام</option>
                    </select>
                </div>

                {{-- المرتب الشهري الأساسي --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">المرتب الشهري الأساسي <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" name="base_salary" required placeholder="3500.00"
                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <span class="absolute left-3 top-2.5 text-[11px] text-gray-400 font-bold">ج.م</span>
                    </div>
                </div>

                {{-- تاريخ التعيين --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">تاريخ بدء العمل / التعيين</label>
                    <input type="date" name="hire_date" value="{{ date('Y-m-d') }}"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- رقم البطاقة القومية --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">الرقم القومي (اختياري)</label>
                    <input type="text" name="national_id" placeholder="14 رقم" maxlength="20"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- عنوان السكن --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">عنوان السكن (اختياري)</label>
                    <input type="text" name="address" placeholder="المدينة، الحي..."
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- ملاحظات --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظات إضافية</label>
                    <textarea name="notes" rows="2" placeholder="أي شروط تعاقدية أو ملاحظات تخص الموظف..."
                        class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeAddEmployeeModal()"
                    class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                    إلغاء
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs transition shadow-md shadow-blue-500/20">
                    حفظ الموظف وتوليد الراتب
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==================== نافذة تعديل بيانات الموظف (Modal) ==================== --}}
<div id="editEmployeeModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 animate-scale-up" dir="rtl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-pen"></i>
                </div>
                <h3 class="font-black text-gray-900 text-base">تعديل بيانات الموظف</h3>
            </div>
            <button type="button" onclick="closeEditEmployeeModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="editEmployeeForm" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                {{-- اسم الموظف --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">اسم الموظف <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_name" name="name" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- رقم الهاتف --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">رقم الهاتف <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_phone" name="phone" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono">
                </div>

                {{-- المسمى الوظيفي --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">المسمى الوظيفي <span class="text-rose-500">*</span></label>
                    <input type="text" id="edit_job_title" name="job_title" required
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- المرتب الشهري الأساسي --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">المرتب الشهري الأساسي <span class="text-rose-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" min="0" id="edit_base_salary" name="base_salary" required
                            class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <span class="absolute left-3 top-2.5 text-[11px] text-gray-400 font-bold">ج.م</span>
                    </div>
                </div>

                {{-- تاريخ التعيين --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">تاريخ بدء العمل</label>
                    <input type="date" id="edit_hire_date" name="hire_date"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- رقم البطاقة --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">الرقم القومي</label>
                    <input type="text" id="edit_national_id" name="national_id"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- عنوان السكن --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">عنوان السكن</label>
                    <input type="text" id="edit_address" name="address"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                {{-- حالة العمل --}}
                <div class="sm:col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer mt-1">
                        <input type="checkbox" id="edit_is_active" name="is_active" value="1"
                            class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-bold text-gray-700">الموظف على رأس العمل (نشط)</span>
                    </label>
                </div>

                {{-- ملاحظات --}}
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظات</label>
                    <textarea id="edit_notes" name="notes" rows="2"
                        class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeEditEmployeeModal()"
                    class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                    إلغاء
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-black text-xs transition shadow-md shadow-amber-500/20">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>

{{-- فورم الحذف المخفي --}}
<form id="deleteEmployeeForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    function openAddEmployeeModal() {
        document.getElementById('addEmployeeModal').classList.remove('hidden');
    }
    function closeAddEmployeeModal() {
        document.getElementById('addEmployeeModal').classList.add('hidden');
    }

    function openEditEmployeeModal(employee) {
        document.getElementById('editEmployeeForm').action = `/employees/${employee.id}`;
        document.getElementById('edit_name').value = employee.name || '';
        document.getElementById('edit_phone').value = employee.phone || '';
        document.getElementById('edit_job_title').value = employee.job_title || '';
        document.getElementById('edit_base_salary').value = employee.base_salary || '';
        document.getElementById('edit_hire_date').value = employee.hire_date ? employee.hire_date.substring(0, 10) : '';
        document.getElementById('edit_national_id').value = employee.national_id || '';
        document.getElementById('edit_address').value = employee.address || '';
        document.getElementById('edit_notes').value = employee.notes || '';
        document.getElementById('edit_is_active').checked = employee.is_active == 1;

        document.getElementById('editEmployeeModal').classList.remove('hidden');
    }
    function closeEditEmployeeModal() {
        document.getElementById('editEmployeeModal').classList.add('hidden');
    }

    function confirmDeleteEmployee(id, name) {
        if (confirm(`هل أنت متأكد من حذف الموظف "${name}"؟\nملاحظة: سيتم الحذف مع الحفاظ على سلامة سجلات الرواتب والحسابات المالية.`)) {
            const form = document.getElementById('deleteEmployeeForm');
            form.action = `/employees/${id}`;
            form.submit();
        }
    }
</script>
@endpush
@endsection
