@extends('layouts.app')

@section('page_title', 'سجل المصروفات')

@section('content')
<div class="container mx-auto space-y-5 pb-12" dir="rtl">

    {{-- رسائل الفلاش --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-3.5 rounded-2xl text-xs font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-500 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 p-3.5 rounded-2xl text-xs font-bold flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-red-500 text-sm"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- ==================== 1. بطاقات الإحصائيات ==================== --}}
    <div class="grid grid-cols-2 {{ $isManager ? 'sm:grid-cols-4' : 'sm:grid-cols-3' }} gap-3">
        {{-- إجمالي المصروفات --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>{{ $selectedCategory ? 'إجمالي: ' . $selectedCategory->name : ($isManager ? 'إجمالي كل المصروفات' : 'إجمالي مصروفاتي') }}</span>
                <i class="fa-solid fa-wallet text-red-500"></i>
            </div>
            <p class="text-xl font-black text-gray-800 leading-tight">
                {{ number_format($stats['total_amount'], 2) }} <span class="text-xs text-gray-400">ج.م</span>
            </p>
        </div>

        {{-- مصروفات اليوم --}}
        <div class="bg-white p-4 rounded-2xl border border-amber-100 shadow-xs flex flex-col justify-between bg-amber-50/20">
            <div class="flex items-center justify-between text-amber-700 text-xs font-bold mb-1">
                <span>مصروفات اليوم</span>
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <p class="text-xl font-black text-amber-600 leading-tight">
                {{ number_format($stats['today_amount'], 2) }} <span class="text-xs text-amber-400">ج.م</span>
            </p>
        </div>

        {{-- عدد العمليات --}}
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex flex-col justify-between">
            <div class="flex items-center justify-between text-gray-400 text-xs font-bold mb-1">
                <span>عدد السجلات</span>
                <i class="fa-solid fa-receipt text-blue-500"></i>
            </div>
            <p class="text-xl font-black text-gray-800 leading-tight">
                {{ number_format($stats['total_count']) }} <span class="text-xs text-gray-400">بند</span>
            </p>
        </div>

        {{-- للمدير: عدد الموظفين --}}
        @if($isManager)
            <div class="bg-white p-4 rounded-2xl border border-purple-100 shadow-xs flex flex-col justify-between bg-purple-50/20">
                <div class="flex items-center justify-between text-purple-700 text-xs font-bold mb-1">
                    <span>الموظفون المشاركون</span>
                    <i class="fa-solid fa-users-gear"></i>
                </div>
                <p class="text-xl font-black text-purple-700 leading-tight">
                    {{ $stats['employees_count'] }} <span class="text-xs text-purple-400">موظف</span>
                </p>
            </div>
        @endif
    </div>

    {{-- ==================== 2. شريط البحث والفلترة ==================== --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
        <form method="GET" action="{{ route('expenses.index') }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 {{ $isManager ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-3">
                
                {{-- البحث --}}
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full bg-gray-50 text-gray-800 pr-9 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-medium"
                        placeholder="ابحث في الملاحظات أو البنود...">
                </div>

                {{-- نوع المصروف --}}
                <div>
                    <select name="category_id"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-bold">
                        <option value="all">كل أنواع المصروفات</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- الفلترة بالتاريخ --}}
                <div>
                    <select name="date_filter"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-bold">
                        <option value="">كل التواريخ</option>
                        <option value="today" {{ request('date_filter') === 'today' ? 'selected' : '' }}>اليوم</option>
                        <option value="yesterday" {{ request('date_filter') === 'yesterday' ? 'selected' : '' }}>أمس</option>
                        <option value="this_week" {{ request('date_filter') === 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                        <option value="this_month" {{ request('date_filter') === 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                    </select>
                </div>

                {{-- فلترة بالموظف (خاصة بالمدير فقط) --}}
                @if($isManager)
                    <div>
                        <select name="employee_id"
                            class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-bold">
                            <option value="all">كل الموظفين</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    👤 {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- أزرار التصفية والإضافة --}}
                <div class="flex items-center gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                        <i class="fa-solid fa-filter text-xs"></i> تصفية
                    </button>
                    @if(request()->hasAny(['search', 'category_id', 'date_filter', 'employee_id', 'date_from', 'date_to']))
                        <a href="{{ route('expenses.index') }}"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2 px-3 rounded-xl text-xs transition flex items-center justify-center"
                            title="إلغاء التصفية">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif
                    <a href="{{ route('expenses.create') }}"
                        class="bg-red-600 hover:bg-red-700 active:scale-95 text-white font-black py-2 px-3.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs shrink-0"
                        title="تسجيل مصروف جديد">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>مصروف جديد</span>
                    </a>

                    @if($isManager)
                        <a href="{{ route('expense-categories.index') }}"
                            class="bg-purple-600 hover:bg-purple-700 active:scale-95 text-white font-black py-2 px-3 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs shrink-0"
                            title="إدارة أنواع المصروفات">
                            <i class="fa-solid fa-tags text-xs"></i>
                            <span class="hidden sm:inline">أنواع المصروفات</span>
                        </a>
                    @endif
                </div>

            </div>
        </form>
    </div>

    {{-- بانر تنبيه عند الفلترة بتصنيف محدد --}}
    @if($selectedCategory)
        <div class="bg-blue-50 border border-blue-200 text-blue-900 p-3.5 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-fade-in">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-filter text-blue-600 text-sm"></i>
                <span>يتم حالياً عرض مصروفات تصنيف: <span class="underline text-blue-700 font-black">«{{ $selectedCategory->name }}»</span> فقط (الإجمالي: {{ number_format($stats['total_amount'], 2) }} ج.م - {{ $stats['total_count'] }} بند)</span>
            </div>
            <a href="{{ route('expenses.index') }}" class="text-blue-600 hover:text-blue-800 text-[11px] underline flex items-center gap-1">
                <i class="fa-solid fa-xmark"></i>
                <span>عرض كل الأنواع</span>
            </a>
        </div>
    @endif

    {{-- ==================== 3. عرض المصروفات مجمعة بالتاريخ (Sub-Groups) ==================== --}}
    @if($groupedExpenses->isEmpty())
        <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 text-gray-300 flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h3 class="text-base font-black text-gray-700 mb-1">لا توجد مصروفات مسجلة</h3>
            <p class="text-xs text-gray-400 mb-4">لم يتم العثور على أي مصروفات تطابق اختيارات البحث الحالية.</p>
            <a href="{{ route('expenses.create') }}"
                class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-black px-5 py-2.5 rounded-xl text-xs shadow-xs transition">
                <i class="fa-solid fa-plus"></i> إضافة أول مصروف
            </a>
        </div>
    @else
        <div class="space-y-5">
            @foreach($groupedExpenses as $dateString => $dayItems)
                @php
                    $carbonDate = \Carbon\Carbon::parse($dateString);
                    $isToday = $carbonDate->isToday();
                    $isYesterday = $carbonDate->isYesterday();
                    $dayTotal = $dayItems->sum('amount');
                @endphp

                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
                    
                    {{-- شريط العنوان الفرعي لكل تاريخ (Date Sub-Group Header) --}}
                    <div class="p-3.5 px-4 bg-slate-50 border-b border-gray-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg {{ $isToday ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-700' }} flex items-center justify-center text-xs font-black shadow-2xs">
                                <i class="fa-regular fa-calendar-days"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-xs sm:text-sm font-black text-gray-900 font-mono">
                                        {{ $carbonDate->format('Y-m-d') }}
                                    </h3>
                                    @if($isToday)
                                        <span class="px-2 py-0.2 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-black border border-emerald-200">
                                            اليوم
                                        </span>
                                    @elseif($isYesterday)
                                        <span class="px-2 py-0.2 rounded-md bg-amber-100 text-amber-800 text-[10px] font-black border border-amber-200">
                                            أمس
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] text-gray-400">
                                    عدد العمليات: <strong class="text-gray-700">{{ $dayItems->count() }}</strong>
                                </span>
                            </div>
                        </div>

                        {{-- إجمالي مصروفات هذا اليوم --}}
                        <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-xl border border-gray-200/80 self-end sm:self-auto">
                            <span class="text-[11px] font-bold text-gray-500">إجمالي اليوم:</span>
                            <span class="text-sm font-black text-red-600 font-mono">
                                {{ number_format($dayTotal, 2) }} <span class="text-[10px] font-bold">ج.م</span>
                            </span>
                        </div>
                    </div>

                    {{-- جدول مصروفات هذا اليوم فقط --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-right border-collapse text-xs">
                            <thead>
                                <tr class="bg-white text-gray-400 font-bold border-b border-gray-100">
                                    <th class="p-3 w-40">نوع المصروف</th>
                                    <th class="p-3 w-32 text-left">المبلغ</th>
                                    <th class="p-3">البيان / الملاحظات</th>
                                    @if($isManager)
                                        <th class="p-3 w-36">الموظف المسجل</th>
                                    @endif
                                    <th class="p-3 w-28 text-center">الوقت</th>
                                    <th class="p-3 w-28 text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                                @foreach($dayItems as $expense)
                                    <tr class="hover:bg-blue-50/20 transition-colors">
                                        {{-- نوع المصروف --}}
                                        <td class="p-3">
                                            <span class="inline-flex items-center gap-1.5 bg-red-50 text-red-700 border border-red-200 px-2.5 py-1 rounded-xl font-black text-xs">
                                                <i class="fa-solid fa-tag text-[10px]"></i>
                                                {{ $expense->category->name ?? $expense->title ?? 'مصروف عام' }}
                                            </span>
                                        </td>

                                        {{-- المبلغ --}}
                                        <td class="p-3 text-left font-black text-sm text-gray-900 font-mono">
                                            {{ number_format($expense->amount, 2) }} <span class="text-[10px] text-gray-400">ج</span>
                                        </td>

                                        {{-- الملاحظات --}}
                                        <td class="p-3 text-gray-600">
                                            @if($expense->notes)
                                                <span class="leading-relaxed">{{ $expense->notes }}</span>
                                            @else
                                                <span class="text-gray-300 italic">لا توجد ملاحظات</span>
                                            @endif
                                        </td>

                                        {{-- الموظف (يظهر للمدير) --}}
                                        @if($isManager)
                                            <td class="p-3">
                                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-800 bg-gray-50 border border-gray-200 px-2 py-0.5 rounded-lg">
                                                    <i class="fa-solid fa-user-pen text-blue-500 text-[10px]"></i>
                                                    {{ $expense->creator->name ?? 'غير معروف' }}
                                                </span>
                                            </td>
                                        @endif

                                        {{-- وقت التسجيل --}}
                                        <td class="p-3 text-center text-gray-400 font-mono text-[11px]">
                                            {{ $expense->created_at->format('h:i A') }}
                                        </td>

                                        {{-- الإجراءات (RUD) --}}
                                        <td class="p-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                {{-- عرض --}}
                                                <a href="{{ route('expenses.show', $expense->id) }}"
                                                    class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 flex items-center justify-center transition"
                                                    title="عرض التفاصيل">
                                                    <i class="fa-solid fa-eye text-xs"></i>
                                                </a>

                                                {{-- تعديل --}}
                                                <a href="{{ route('expenses.edit', $expense->id) }}"
                                                    class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 flex items-center justify-center transition"
                                                    title="تعديل">
                                                    <i class="fa-solid fa-pen text-xs"></i>
                                                </a>

                                                {{-- حذف --}}
                                                <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST"
                                                    onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف بمبلغ {{ number_format($expense->amount, 2) }} ج.م؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="w-7 h-7 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 flex items-center justify-center transition"
                                                        title="حذف">
                                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            @endforeach
        </div>

        {{-- الترقيم الصفحي --}}
        @if($paginatedExpenses->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-gray-100 shadow-xs">
                {{ $paginatedExpenses->links() }}
            </div>
        @endif
    @endif

</div>
@endsection
