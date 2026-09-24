@extends('layouts.app')

@section('title', 'ملف الموظف ومسير الرواتب - ' . $employee->name)
@section('page_title', 'كشف حساب ومسير رواتب الموظف')

@section('content')
<div class="space-y-6 animate-slide-in pb-16" dir="rtl">

    {{-- ==================== 1. شريط التنقل العلوي والأزرار ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs print:hidden">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('employees.index') }}"
                class="w-10 h-10 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-600 flex items-center justify-center transition"
                title="الرجوع لقائمة الموظفين">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-700 text-white font-black flex items-center justify-center text-lg shadow-md shadow-blue-500/20">
                {{ mb_substr($employee->name, 0, 1) }}
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>{{ $employee->name }}</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $employee->job_title }}
                    </span>
                    @if($employee->is_active)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            على رأس العمل
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                            متوقف
                        </span>
                    @endif
                </h2>
                <p class="text-xs text-gray-400 mt-0.5 font-mono">
                    كود: #{{ $employee->id }} | هاتف: {{ $employee->phone }} | تعيين: {{ $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '-' }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 self-end sm:self-auto">
            {{-- زر طباعة كشف ومسير الراتب --}}
            <button type="button" onclick="window.print()"
                class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 active:scale-95 text-gray-700 font-bold text-xs transition flex items-center gap-2 cursor-pointer">
                <i class="fa-solid fa-print text-sm"></i>
                <span>طباعة الكشف</span>
            </button>

            {{-- زر تصدير إكسيل --}}
            <a href="{{ route('employees.exportSingle', $employee->id) }}"
                class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer">
                <i class="fa-solid fa-file-excel text-sm"></i>
                <span>تصدير إكسيل (Excel)</span>
            </a>

            {{-- زر إضافة بونص أو خصم سريع --}}
            <button type="button" onclick="openAddAdjustmentModal()"
                class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-indigo-600/20 cursor-pointer">
                <i class="fa-solid fa-scale-balanced text-xs"></i>
                <span>إضافة خصم / بونص</span>
            </button>
        </div>
    </div>

    {{-- رسائل التنبيهات والنجاح --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 font-bold flex items-center gap-2 print:hidden">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ==================== 2. بانر تسديد كامل المستحقات المعلقة (إذا وجدت) ==================== --}}
    @if($totalDues > 0)
        <div class="bg-gradient-to-r from-rose-500 to-amber-600 text-white p-5 rounded-3xl shadow-lg flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 print:hidden animate-pulse-slow">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-xl shrink-0">
                    <i class="fa-solid fa-hand-holding-dollar"></i>
                </div>
                <div>
                    <div class="text-sm font-black flex items-center gap-2">
                        <span>توجد رواتب ومستحقات معلقة للموظف</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-white text-rose-600 font-black">
                            {{ number_format($totalDues, 2) }} ج.م
                        </span>
                    </div>
                    <p class="text-xs text-white/90 mt-0.5">يمكنك صرف وتسديد كامل الشهور المتأخرة المستحقة دفعة واحدة بضغطة زر واحدة</p>
                </div>
            </div>

            <button type="button" onclick="openPayAllDuesModal()"
                class="px-5 py-2.5 rounded-xl bg-white hover:bg-gray-100 text-gray-900 font-black text-xs transition shadow-md active:scale-95 shrink-0 flex items-center gap-2">
                <i class="fa-solid fa-check-double text-emerald-600"></i>
                <span>تسديد جميع المستحق ({{ number_format($totalDues, 2) }} ج.م)</span>
            </button>
        </div>
    @endif

    {{-- ==================== 3. كروت المؤشرات المالية للموظف ==================== --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3.5">
        {{-- كارت: المرتب الأساسي --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">الراتب الأساسي</span>
                <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-money-bill"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-gray-900 font-mono">{{ number_format($employee->base_salary, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span></div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5">المرتب الشهري الثابت</div>
            </div>
        </div>

        {{-- كارت: إجمالي المقبوض --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">إجمالي المقبوض</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-emerald-600 font-mono">{{ number_format($totalPaid, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span></div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5">تم صرفه تاريخياً للموظف</div>
            </div>
        </div>

        {{-- كارت: إجمالي المستحق حالياً --}}
        <div class="bg-white p-4 rounded-3xl border {{ $totalDues > 0 ? 'border-rose-200 bg-rose-50/20' : 'border-gray-100' }} shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold {{ $totalDues > 0 ? 'text-rose-700' : '' }}">المستحق حالياً</span>
                <div class="w-8 h-8 rounded-xl {{ $totalDues > 0 ? 'bg-rose-100 text-rose-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-xs">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black {{ $totalDues > 0 ? 'text-rose-600' : 'text-gray-900' }} font-mono">
                    {{ number_format($totalDues, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span>
                </div>
                <div class="text-[11px] {{ $totalDues > 0 ? 'text-rose-500 font-bold' : 'text-gray-400' }} mt-0.5">
                    {{ $totalDues > 0 ? 'رواتب قيد الصرف' : 'لا توجد متأخرات' }}
                </div>
            </div>
        </div>

        {{-- كارت: إجمالي المكافآت --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">إجمالي المكافآت</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-gift"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-purple-600 font-mono">+{{ number_format($totalBonus, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span></div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5">حوافز وبونص تميز</div>
            </div>
        </div>

        {{-- كارت: إجمالي الخصومات --}}
        <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between col-span-2 md:col-span-1">
            <div class="flex items-center justify-between text-gray-400 mb-2">
                <span class="text-xs font-bold">إجمالي الخصومات</span>
                <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-circle-minus"></i>
                </div>
            </div>
            <div>
                <div class="text-xl font-black text-rose-600 font-mono">-{{ number_format($totalDeductions, 2) }} <span class="text-xs font-sans text-gray-400">ج.م</span></div>
                <div class="text-[11px] text-gray-400 font-medium mt-0.5">جزاءات وسلف وخصومات</div>
            </div>
        </div>
    </div>

    {{-- ==================== 4. بيانات الموظف والتعاقد ==================== --}}
    <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div class="flex items-center gap-2 text-sm font-black text-gray-900">
                <i class="fa-solid fa-address-card text-blue-600"></i>
                <span>تفاصيل وبيانات الموظف التعاقدية</span>
            </div>
            <button type="button" onclick="openEditEmployeeModal({{ json_encode($employee) }})"
                class="px-3 py-1.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-700 font-bold text-xs transition flex items-center gap-1.5 print:hidden">
                <i class="fa-solid fa-pen text-[11px]"></i>
                <span>تعديل البيانات</span>
            </button>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="block text-gray-400 text-[11px] font-bold">اسم الموظف:</span>
                <span class="font-black text-gray-900 mt-0.5 block">{{ $employee->name }}</span>
            </div>
            <div>
                <span class="block text-gray-400 text-[11px] font-bold">رقم الهاتف:</span>
                <span class="font-bold text-gray-800 font-mono mt-0.5 block dir-ltr text-right">{{ $employee->phone }}</span>
            </div>
            <div>
                <span class="block text-gray-400 text-[11px] font-bold">الرقم القومي / البطاقة:</span>
                <span class="font-bold text-gray-800 font-mono mt-0.5 block">{{ $employee->national_id ?: 'غير مسجل' }}</span>
            </div>
            <div>
                <span class="block text-gray-400 text-[11px] font-bold">المسمى الوظيفي:</span>
                <span class="font-bold text-gray-800 mt-0.5 block">{{ $employee->job_title }}</span>
            </div>
            <div>
                <span class="block text-gray-400 text-[11px] font-bold">تاريخ التعيين:</span>
                <span class="font-bold text-gray-800 font-mono mt-0.5 block">{{ $employee->hire_date ? $employee->hire_date->format('Y-m-d') : 'غير محدد' }}</span>
            </div>
            <div>
                <span class="block text-gray-400 text-[11px] font-bold">عنوان السكن:</span>
                <span class="font-medium text-gray-700 mt-0.5 block">{{ $employee->address ?: 'غير مسجل' }}</span>
            </div>
            <div class="col-span-2">
                <span class="block text-gray-400 text-[11px] font-bold">ملاحظات إدارية:</span>
                <span class="font-medium text-gray-700 mt-0.5 block">{{ $employee->notes ?: 'لا توجد ملاحظات مسجلة' }}</span>
            </div>
        </div>
    </div>

    {{-- ==================== 5. سجل الرواتب الشهرية والقبض (Monthly Payroll Ledger) ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-black text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-check text-blue-600"></i>
                    <span>سجل الرواتب الشهرية والقبض</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-blue-50 text-blue-700 font-black">
                        {{ $employee->payrolls->count() }} شهر
                    </span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">مسير الرواتب الشهرية التلقائي، الخصومات والبونص، وحالة الصرف لكل شهر</p>
            </div>

            @if($totalDues > 0)
                <button type="button" onclick="openPayAllDuesModal()"
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition shadow-sm print:hidden flex items-center gap-1.5">
                    <i class="fa-solid fa-check-double"></i>
                    <span>تسديد كامل المستحق</span>
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-gray-50/80 text-gray-500 font-black border-b border-gray-100">
                        <th class="py-3 px-4">الشهر المستحق</th>
                        <th class="py-3 px-4">الراتب الأساسي</th>
                        <th class="py-3 px-4 text-emerald-600">بونص (+)</th>
                        <th class="py-3 px-4 text-rose-600">خصم (-)</th>
                        <th class="py-3 px-4">صافي المستحق</th>
                        <th class="py-3 px-4">المدفوع</th>
                        <th class="py-3 px-4">المتبقي</th>
                        <th class="py-3 px-4">حالة الصرف</th>
                        <th class="py-3 px-4">تاريخ وطريقة الصرف</th>
                        <th class="py-3 px-4 text-center print:hidden">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employee->payrolls as $payroll)
                        @php
                            $isPaid = $payroll->status === 'paid';
                            $isPartial = $payroll->status === 'partial';
                            $due = $payroll->remaining_due;
                        @endphp
                        <tr class="hover:bg-blue-50/30 transition">
                            {{-- الشهر --}}
                            <td class="py-3 px-4 font-black text-gray-900">
                                <div>{{ $payroll->month_name_ar }}</div>
                                <div class="text-[10px] text-gray-400 font-mono">{{ $payroll->salary_month }}</div>
                            </td>

                            {{-- الأساسي --}}
                            <td class="py-3 px-4 font-mono font-bold text-gray-700">
                                {{ number_format($payroll->base_salary, 2) }}
                            </td>

                            {{-- البونص --}}
                            <td class="py-3 px-4 font-mono font-bold text-emerald-600">
                                @if($payroll->total_bonus > 0)
                                    +{{ number_format($payroll->total_bonus, 2) }}
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>

                            {{-- الخصم --}}
                            <td class="py-3 px-4 font-mono font-bold text-rose-500">
                                @if($payroll->total_deductions > 0)
                                    -{{ number_format($payroll->total_deductions, 2) }}
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>

                            {{-- الصافي المستحق --}}
                            <td class="py-3 px-4 font-mono font-black text-gray-900 text-sm">
                                {{ number_format($payroll->net_salary, 2) }} <span class="text-[10px] font-normal text-gray-400">ج.م</span>
                            </td>

                            {{-- المدفوع --}}
                            <td class="py-3 px-4 font-mono font-bold text-emerald-600">
                                {{ number_format($payroll->paid_amount, 2) }}
                            </td>

                            {{-- المتبقي --}}
                            <td class="py-3 px-4 font-mono font-black">
                                @if($due > 0)
                                    <span class="text-rose-600">{{ number_format($due, 2) }} ج.م</span>
                                @else
                                    <span class="text-emerald-500 font-normal">0.00</span>
                                @endif
                            </td>

                            {{-- حالة الصرف --}}
                            <td class="py-3 px-4">
                                @if($isPaid)
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i>
                                        <span>مدفوع بالكامل</span>
                                    </span>
                                @elseif($isPartial)
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-blue-50 text-blue-700 border border-blue-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-hourglass-half text-[10px]"></i>
                                        <span>مدفوع جزئياً</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-amber-50 text-amber-700 border border-amber-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-clock text-[10px]"></i>
                                        <span>مستحق معلق</span>
                                    </span>
                                @endif
                            </td>

                            {{-- تاريخ وطريقة الصرف --}}
                            <td class="py-3 px-4 text-gray-500 font-mono text-[11px]">
                                @if($payroll->payment_date)
                                    <div>{{ $payroll->payment_date->format('Y-m-d H:i') }}</div>
                                    <div class="text-[10px] text-gray-400 font-sans">
                                        {{ match($payroll->payment_method) {
                                            'visa'          => 'فيزا / بطاقة',
                                            'instapay'      => 'إنستاباي (InstaPay)',
                                            'bank_transfer' => 'تحويل بنكي',
                                            default         => 'نقداً (كاش)',
                                        } }}
                                        @if($payroll->payer)
                                            <span class="text-gray-300">|</span> بوا بواسطة: {{ $payroll->payer->name }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-300">-</span>
                                @endif
                            </td>

                            {{-- الإجراءات --}}
                            <td class="py-3 px-4 text-center print:hidden">
                                @if(!$isPaid)
                                    <button type="button"
                                        onclick="openPayMonthModal({{ json_encode($payroll) }})"
                                        class="px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs transition shadow-xs flex items-center gap-1 mx-auto cursor-pointer">
                                        <i class="fa-solid fa-money-check-dollar"></i>
                                        <span>صرف الراتب</span>
                                    </button>
                                @else
                                    <span class="text-emerald-600 text-xs font-bold flex items-center justify-center gap-1">
                                        <i class="fa-solid fa-check"></i>
                                        <span>مسدد</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-gray-400">
                                لا توجد سجلات رواتب مسجلة للموظف
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ==================== 6. سجل وتفاصيل الخصومات والمكافآت (Adjustments) ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-scale-balanced text-indigo-600"></i>
                    <span>سجل الخصومات والمكافآت التفصيلي</span>
                    <span class="px-2 py-0.5 rounded-full text-xs font-mono bg-indigo-50 text-indigo-700 font-black">
                        {{ $employee->adjustments->count() }} حركة
                    </span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">سجل الحركات المالية المضافة للموظف في أي شهر مع التأثير المباشر على صافي الراتب</p>
            </div>

            <button type="button" onclick="openAddAdjustmentModal()"
                class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs transition shadow-xs flex items-center gap-1.5 print:hidden cursor-pointer">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>إضافة حركة جديدة</span>
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-right text-xs">
                <thead>
                    <tr class="bg-gray-50/80 text-gray-500 font-black border-b border-gray-100">
                        <th class="py-3 px-4">تاريخ الحركة</th>
                        <th class="py-3 px-4">الشهر المتأثر</th>
                        <th class="py-3 px-4">النوع</th>
                        <th class="py-3 px-4">المبلغ</th>
                        <th class="py-3 px-4">السبب والبيان</th>
                        <th class="py-3 px-4">المسؤول</th>
                        <th class="py-3 px-4 text-center print:hidden">إجراء</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($employee->adjustments as $adj)
                        <tr class="hover:bg-indigo-50/20 transition">
                            <td class="py-3 px-4 font-mono text-gray-600">
                                {{ $adj->date ? $adj->date->format('Y-m-d') : $adj->created_at->format('Y-m-d') }}
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-gray-900">
                                {{ $adj->salary_month }}
                            </td>
                            <td class="py-3 px-4">
                                @if($adj->type === 'bonus')
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-gift text-[10px]"></i>
                                        <span>مكافأة / بونص (+)</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-xl text-[11px] font-black bg-rose-50 text-rose-700 border border-rose-200 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-minus text-[10px]"></i>
                                        <span>خصم / جزاء (-)</span>
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 font-mono font-black text-sm {{ $adj->type === 'bonus' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $adj->type === 'bonus' ? '+' : '-' }}{{ number_format($adj->amount, 2) }} ج.م
                            </td>
                            <td class="py-3 px-4 font-medium text-gray-700">
                                {{ $adj->reason }}
                            </td>
                            <td class="py-3 px-4 text-gray-500 text-[11px]">
                                {{ $adj->creator ? $adj->creator->name : 'النظام' }}
                            </td>
                            <td class="py-3 px-4 text-center print:hidden">
                                <button type="button"
                                    onclick="confirmDeleteAdjustment({{ $adj->id }}, '{{ $adj->type === 'bonus' ? 'المكافأة' : 'الخصم' }}', {{ $adj->amount }})"
                                    class="w-7 h-7 rounded-xl bg-rose-50 hover:bg-rose-600 text-rose-600 hover:text-white flex items-center justify-center transition mx-auto"
                                    title="حذف الحركة وإعادة احتساب الراتب">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400">
                                لا توجد أي خصومات أو مكافآت مسجلة لهذا الموظف
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- ==================== نافذة صرف راتب شهر محدد (Modal) ==================== --}}
<div id="payMonthModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 animate-scale-up" dir="rtl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-money-check-dollar"></i>
                </div>
                <h3 class="font-black text-gray-900 text-base">تسجيل صرف واستلام الراتب</h3>
            </div>
            <button type="button" onclick="closePayMonthModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="payMonthForm" method="POST" class="mt-4 space-y-4">
            @csrf

            <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-2xl">
                <div class="text-xs font-bold text-gray-500">شهر الراتب:</div>
                <div id="modal_month_title" class="text-sm font-black text-blue-900 mt-0.5"></div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-blue-100 text-xs">
                    <span class="text-gray-600">صافي المستحق للشهر:</span>
                    <span id="modal_net_salary" class="font-mono font-black text-gray-900"></span>
                </div>
                <div class="flex items-center justify-between mt-1 text-xs">
                    <span class="text-rose-600 font-bold">المتبقي للصرف:</span>
                    <span id="modal_remaining_due" class="font-mono font-black text-rose-600"></span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">المبلغ المراد صرفه وتسديده <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <input type="number" step="0.01" min="0.01" id="modal_pay_amount" name="amount" required
                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-black text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <span class="absolute left-3 top-2.5 text-xs text-gray-400 font-bold">ج.م</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">طريقة الدفع والصرف <span class="text-rose-500">*</span></label>
                <select name="payment_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent font-bold">
                    <option value="cash">نقداً (كاش - من الخزينة)</option>
                    <option value="instapay">إنستاباي (InstaPay)</option>
                    <option value="visa">فيزا / بطاقة بنكية</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظات الصرف (اختياري)</label>
                <input type="text" name="notes" placeholder="رقم التحويل أو أي ملاحظة..."
                    class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closePayMonthModal()"
                    class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                    إلغاء
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs transition shadow-md shadow-blue-500/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-check"></i>
                    <span>تأكيد صرف الراتب</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==================== نافذة تسديد جميع المستحقات دفعة واحدة (Modal) ==================== --}}
<div id="payAllDuesModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 animate-scale-up" dir="rtl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-check-double"></i>
                </div>
                <h3 class="font-black text-gray-900 text-base">تسديد كامل المستحقات المتأخرة</h3>
            </div>
            <button type="button" onclick="closePayAllDuesModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('employees.payAllDues', $employee->id) }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-center">
                <div class="text-xs font-bold text-emerald-800">إجمالي المبلغ المطلوب تسديده لجميع الشهور:</div>
                <div class="text-2xl font-black text-emerald-600 font-mono mt-1">
                    {{ number_format($totalDues, 2) }} <span class="text-xs font-sans text-emerald-800">ج.م</span>
                </div>
                <div class="text-[11px] text-emerald-700 mt-1">سيتم تحويل حالة جميع الشهور المعلقة إلى (مدفوع بالكامل) فورياً</div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">طريقة الدفع والصرف <span class="text-rose-500">*</span></label>
                <select name="payment_method" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-bold">
                    <option value="cash">نقداً (كاش - من الخزينة)</option>
                    <option value="instapay">إنستاباي (InstaPay)</option>
                    <option value="visa">فيزا / بطاقة بنكية</option>
                    <option value="bank_transfer">تحويل بنكي</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">ملاحظات التسوية</label>
                <input type="text" name="notes" placeholder="تسوية شاملة لكافة المتأخرات..."
                    class="w-full px-3.5 py-2 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closePayAllDuesModal()"
                    class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                    إلغاء
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs transition shadow-md shadow-emerald-500/20 flex items-center gap-1.5">
                    <i class="fa-solid fa-check-double"></i>
                    <span>تأكيد تسديد كامل المستحقات</span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ==================== نافذة إضافة خصم أو بونص للموظف (Modal) ==================== --}}
<div id="addAdjustmentModal" class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 animate-scale-up" dir="rtl">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-scale-balanced"></i>
                </div>
                <h3 class="font-black text-gray-900 text-base">إضافة خصم أو مكافأة (بونص)</h3>
            </div>
            <button type="button" onclick="closeAddAdjustmentModal()" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('employees.addAdjustment', $employee->id) }}" method="POST" class="mt-4 space-y-4">
            @csrf

            {{-- نوع الحركة --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">نوع الحركة المالية <span class="text-rose-500">*</span></label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="flex items-center justify-center gap-2 p-3 rounded-2xl border-2 border-emerald-200 bg-emerald-50/40 text-emerald-800 font-bold text-xs cursor-pointer hover:bg-emerald-50 transition has-checked:border-emerald-600 has-checked:bg-emerald-100/50">
                        <input type="radio" name="type" value="bonus" checked class="text-emerald-600 focus:ring-emerald-500">
                        <i class="fa-solid fa-gift"></i>
                        <span>مكافأة / بونص (+)</span>
                    </label>

                    <label class="flex items-center justify-center gap-2 p-3 rounded-2xl border-2 border-rose-200 bg-rose-50/40 text-rose-800 font-bold text-xs cursor-pointer hover:bg-rose-50 transition has-checked:border-rose-600 has-checked:bg-rose-100/50">
                        <input type="radio" name="type" value="deduction" class="text-rose-600 focus:ring-rose-500">
                        <i class="fa-solid fa-circle-minus"></i>
                        <span>خصم / جزاء (-)</span>
                    </label>
                </div>
            </div>

            {{-- الشهر المتأثر --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">الشهر المتأثر بالحركة <span class="text-rose-500">*</span></label>
                <select name="salary_month" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-bold focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    @foreach($availableMonths as $m)
                        <option value="{{ $m }}" {{ $m === Carbon\Carbon::now()->format('Y-m') ? 'selected' : '' }}>
                            شهر {{ $m }} {{ $m === Carbon\Carbon::now()->format('Y-m') ? '(الشهر الحالي)' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-gray-400 mt-1">يمكنك إضافة الخصم أو البونص للشهر الحالي أو أي شهر سابق وسيتم تعديل صافي الراتب فورياً</p>
            </div>

            {{-- المبلغ --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">المبلغ (ج.م) <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <input type="number" step="0.01" min="0.01" name="amount" required placeholder="مثال: 150.00"
                        class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono font-black focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <span class="absolute left-3 top-2.5 text-xs text-gray-400 font-bold">ج.م</span>
                </div>
            </div>

            {{-- السبب والبيان --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">السبب والتفاصيل <span class="text-rose-500">*</span></label>
                <input type="text" name="reason" required placeholder="مثال: مكافأة عمل إضافي / سلفة / تأخير..."
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            {{-- تاريخ الواقعة --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">تاريخ الحركة</label>
                <input type="date" name="date" value="{{ date('Y-m-d') }}"
                    class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeAddAdjustmentModal()"
                    class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs transition">
                    إلغاء
                </button>
                <button type="submit"
                    class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs transition shadow-md shadow-indigo-500/20">
                    حفظ وتحديث الراتب فوراً
                </button>
            </div>
        </form>
    </div>
</div>

{{-- فورم حذف الخصم أو البونص المخفي --}}
<form id="deleteAdjustmentForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
    // فتح وإغلاق نافذة صرف الراتب لشهر
    function openPayMonthModal(payroll) {
        document.getElementById('payMonthForm').action = `/employees/payrolls/${payroll.id}/pay`;
        document.getElementById('modal_month_title').textContent = `${payroll.month_name_ar || payroll.salary_month} (${payroll.salary_month})`;
        document.getElementById('modal_net_salary').textContent = `${Number(payroll.net_salary).toFixed(2)} ج.م`;
        const due = Math.max(0, Number(payroll.net_salary) - Number(payroll.paid_amount));
        document.getElementById('modal_remaining_due').textContent = `${due.toFixed(2)} ج.م`;
        document.getElementById('modal_pay_amount').value = due.toFixed(2);
        document.getElementById('payMonthModal').classList.remove('hidden');
    }
    function closePayMonthModal() {
        document.getElementById('payMonthModal').classList.add('hidden');
    }

    // فتح وإغلاق تسديد جميع المستحقات
    function openPayAllDuesModal() {
        document.getElementById('payAllDuesModal').classList.remove('hidden');
    }
    function closePayAllDuesModal() {
        document.getElementById('payAllDuesModal').classList.add('hidden');
    }

    // فتح وإغلاق إضافة بونص أو خصم
    function openAddAdjustmentModal() {
        document.getElementById('addAdjustmentModal').classList.remove('hidden');
    }
    function closeAddAdjustmentModal() {
        document.getElementById('addAdjustmentModal').classList.add('hidden');
    }

    // تأكيد حذف الخصم أو البونص
    function confirmDeleteAdjustment(id, typeLabel, amount) {
        if (confirm(`هل أنت متأكد من حذف ${typeLabel} بمبلغ ${amount} ج.م؟\nسيتم إعادة احتساب صافي راتب الشهر فوراً.`)) {
            const form = document.getElementById('deleteAdjustmentForm');
            form.action = `/employees/adjustments/${id}`;
            form.submit();
        }
    }
</script>
@endpush
@endsection
