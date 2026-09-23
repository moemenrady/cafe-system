@extends('layouts.app')

@section('page_title', 'أنواع المصروفات')

@section('content')
<div class="container mx-auto space-y-6 pb-12" dir="rtl">

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-fade-in">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs animate-fade-in">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-red-500 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    {{-- ==================== 1. بطاقات الإحصائيات العامة ==================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- إجمالي المصروفات في الفترة المحددة --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between relative overflow-hidden group hover:shadow-md transition">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold mb-2">
                <span>إجمالي المصروفات للفترة</span>
                <div class="w-9 h-9 rounded-2xl bg-red-50 text-red-500 flex items-center justify-center">
                    <i class="fa-solid fa-money-bill-wave text-sm"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-800 leading-tight">
                    {{ number_format($stats['total_period_amount'], 2) }} <span class="text-xs font-bold text-gray-400">ج.م</span>
                </p>
                <p class="text-[11px] text-gray-400 font-medium mt-1">
                    @if(request('date_filter') == 'today')
                        اليوم فقط
                    @elseif(request('date_filter') == 'yesterday')
                        أمس فقط
                    @elseif(request('date_filter') == 'this_week')
                        خلال هذا الأسبوع
                    @elseif(request('date_filter') == 'this_month')
                        خلال هذا الشهر
                    @elseif(request('date_from') || request('date_to'))
                        الفترة من {{ request('date_from', 'البداية') }} إلى {{ request('date_to', 'الآن') }}
                    @else
                        منذ بداية التسجيل
                    @endif
                </p>
            </div>
        </div>

        {{-- عدد حركات الصرف --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between group hover:shadow-md transition">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold mb-2">
                <span>عدد الحركات المسجلة</span>
                <div class="w-9 h-9 rounded-2xl bg-blue-50 text-blue-500 flex items-center justify-center">
                    <i class="fa-solid fa-receipt text-sm"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-800 leading-tight">
                    {{ number_format($stats['total_period_count']) }} <span class="text-xs font-bold text-gray-400">حركة</span>
                </p>
                <p class="text-[11px] text-gray-400 font-medium mt-1">إجمالي الإيصالات والفواتير المسجلة</p>
            </div>
        </div>

        {{-- عدد أنواع المصروفات --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex flex-col justify-between group hover:shadow-md transition">
            <div class="flex items-center justify-between text-gray-500 text-xs font-bold mb-2">
                <span>أنواع المصروفات</span>
                <div class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center">
                    <i class="fa-solid fa-tags text-sm"></i>
                </div>
            </div>
            <div>
                <p class="text-2xl font-black text-gray-800 leading-tight">
                    {{ $stats['categories_count'] }} <span class="text-xs font-bold text-amber-500">({{ $stats['active_count'] }} نشط)</span>
                </p>
                <p class="text-[11px] text-gray-400 font-medium mt-1">تصنيفات المصروفات المعتمدة</p>
            </div>
        </div>

        {{-- أعلى نوع استهلاكاً --}}
        <div class="bg-white p-5 rounded-3xl border border-purple-100 shadow-xs flex flex-col justify-between bg-purple-50/20 group hover:shadow-md transition">
            <div class="flex items-center justify-between text-purple-700 text-xs font-bold mb-2">
                <span>الأعلى إنفاقاً في الفترة</span>
                <div class="w-9 h-9 rounded-2xl bg-purple-100 text-purple-600 flex items-center justify-center">
                    <i class="fa-solid fa-fire-flame-curved text-sm"></i>
                </div>
            </div>
            <div>
                <p class="text-lg font-black text-purple-900 leading-tight truncate" title="{{ $stats['top_category_name'] }}">
                    {{ $stats['top_category_name'] }}
                </p>
                <p class="text-xs font-bold text-purple-600 mt-1">
                    {{ number_format($stats['top_category_amount'], 2) }} <span class="text-[10px] text-purple-400">ج.م</span>
                </p>
            </div>
        </div>
    </div>

    {{-- ==================== 2. شريط البحث والفلترة بالتاريخ ==================== --}}
    <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs space-y-4">
        <form method="GET" action="{{ route('expense-categories.index') }}" id="filterForm">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                
                {{-- البحث بالاسم --}}
                <div class="md:col-span-3 relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                        class="w-full bg-gray-50 text-gray-800 pr-10 pl-3 py-2.5 rounded-2xl border border-gray-200 focus:border-blue-500 focus:bg-white outline-hidden text-xs font-semibold transition"
                        placeholder="ابحث في اسم أو وصف النوع...">
                </div>

                {{-- الفلترة السريعة بالتاريخ --}}
                <div class="md:col-span-3">
                    <select name="date_filter" id="dateFilterSelect" onchange="toggleCustomDates(this.value)"
                        class="w-full bg-gray-50 text-gray-800 px-3.5 py-2.5 rounded-2xl border border-gray-200 focus:border-blue-500 focus:bg-white outline-hidden text-xs font-bold transition">
                        <option value="">كل الفترات الزمنية</option>
                        <option value="today" {{ request('date_filter') === 'today' ? 'selected' : '' }}>اليوم</option>
                        <option value="yesterday" {{ request('date_filter') === 'yesterday' ? 'selected' : '' }}>أمس</option>
                        <option value="this_week" {{ request('date_filter') === 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                        <option value="this_month" {{ request('date_filter') === 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                        <option value="custom" {{ (request('date_from') || request('date_to')) ? 'selected' : '' }}>تحديد فترة مخصصة...</option>
                    </select>
                </div>

                {{-- فلترة الحالة --}}
                <div class="md:col-span-2">
                    <select name="status"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2.5 rounded-2xl border border-gray-200 focus:border-blue-500 focus:bg-white outline-hidden text-xs font-bold transition">
                        <option value="all">كل الحالات</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>النشطة فقط</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>المعطلة فقط</option>
                    </select>
                </div>

                {{-- أزرار التصفية والإلغاء --}}
                <div class="md:col-span-4 flex items-center gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 active:scale-98 text-white font-bold py-2.5 px-4 rounded-2xl text-xs transition flex items-center justify-center gap-2 shadow-xs">
                        <i class="fa-solid fa-filter text-xs"></i>
                        <span>تصفية وحساب</span>
                    </button>
                    
                    @if(request()->hasAny(['search', 'date_filter', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('expense-categories.index') }}"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2.5 px-3 rounded-2xl text-xs transition flex items-center justify-center"
                            title="إعادة التعيين">
                            <i class="fa-solid fa-rotate-left"></i>
                        </a>
                    @endif

                    <a href="{{ route('expense-categories.create') }}"
                        class="bg-emerald-600 hover:bg-emerald-700 active:scale-98 text-white font-black py-2.5 px-4 rounded-2xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs shrink-0">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>نوع جديد</span>
                    </a>

                    <a href="{{ route('expenses.index') }}"
                        class="bg-gray-800 hover:bg-gray-900 active:scale-98 text-white font-bold py-2.5 px-3.5 rounded-2xl text-xs transition flex items-center justify-center gap-1.5 shadow-xs shrink-0"
                        title="الانتقال لسجل المصروفات">
                        <i class="fa-solid fa-wallet text-xs text-cafePrimary"></i>
                        <span class="hidden sm:inline">سجل المصروفات</span>
                    </a>
                </div>

            </div>

            {{-- حقول التاريخ المخصص (تظهر عند اختيار Custom أو عند وجود قيم) --}}
            <div id="customDateRow" class="{{ (request('date_filter') === 'custom' || request('date_from') || request('date_to')) ? 'grid' : 'hidden' }} grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-gray-100 mt-3">
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-gray-500 shrink-0">من تاريخ:</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 text-xs font-semibold">
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs font-bold text-gray-500 shrink-0">إلى تاريخ:</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full bg-gray-50 text-gray-800 px-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 text-xs font-semibold">
                </div>
            </div>
        </form>
    </div>

    {{-- ==================== 3. بطاقات أنواع المصروفات ==================== --}}
    @if($categories->isEmpty())
        <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-12 text-center">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 text-gray-400 flex items-center justify-center mx-auto mb-3 text-2xl">
                <i class="fa-solid fa-tags"></i>
            </div>
            <h3 class="text-base font-black text-gray-700 mb-1">لا توجد أنواع مصروفات تطابق البحث</h3>
            <p class="text-xs text-gray-400 mb-5">قم بتعديل خيارات البحث أو قم بإضافة نوع مصروف جديد الآن.</p>
            <a href="{{ route('expense-categories.create') }}"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black px-6 py-2.5 rounded-2xl text-xs shadow-xs transition">
                <i class="fa-solid fa-plus"></i> إضافة نوع مصروف جديد
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($categories as $category)
                @php
                    $categoryAmount = $category->total_amount ?? 0;
                    $categoryCount = $category->expenses_count ?? 0;
                    // تجهيز الرابط المفلتر لصفحة المصروفات
                    $filterParams = array_filter([
                        'category_id' => $category->id,
                        'date_filter' => request('date_filter'),
                        'date_from'   => request('date_from'),
                        'date_to'     => request('date_to'),
                    ]);
                    $expensesUrl = route('expenses.index', $filterParams);
                @endphp

                <div class="bg-white rounded-3xl border border-gray-100 shadow-xs hover:shadow-md hover:border-blue-200 transition-all duration-200 flex flex-col justify-between overflow-hidden group">
                    
                    {{-- رأس البطاقة: الاسم والحالة --}}
                    <div class="p-5 pb-3">
                        <div class="flex items-start justify-between gap-2 mb-2">
                            <a href="{{ $expensesUrl }}" class="group-hover:text-blue-600 transition flex items-center gap-2 font-black text-base text-gray-800 leading-snug">
                                <span class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xs group-hover:bg-blue-600 group-hover:text-white transition">
                                    <i class="fa-solid fa-tag"></i>
                                </span>
                                <span class="truncate">{{ $category->name }}</span>
                            </a>

                            {{-- شارة الحالة --}}
                            @if($category->is_active)
                                <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black px-2 py-0.5 rounded-full shrink-0">
                                    نشط
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-500 border border-gray-200 text-[10px] font-black px-2 py-0.5 rounded-full shrink-0">
                                    معطل
                                </span>
                            @endif
                        </div>

                        {{-- الوصف --}}
                        <p class="text-xs text-gray-400 font-medium line-clamp-2 min-h-[32px]">
                            {{ $category->description ?: 'لا يوجد وصف مضاف لهذا النوع.' }}
                        </p>
                    </div>

                    {{-- قسم الإحصائيات الخاصة بالنوع --}}
                    <div class="px-5 py-3 bg-gray-50/60 border-y border-gray-100 grid grid-cols-2 gap-2 text-center">
                        <div class="p-2 bg-white rounded-2xl border border-gray-100">
                            <span class="text-[10px] font-bold text-gray-400 block mb-0.5">إجمالي المصروف</span>
                            <span class="text-sm font-black text-red-600">
                                {{ number_format($categoryAmount, 2) }}
                            </span>
                            <span class="text-[9px] text-gray-400 font-bold">ج.م</span>
                        </div>
                        <div class="p-2 bg-white rounded-2xl border border-gray-100">
                            <span class="text-[10px] font-bold text-gray-400 block mb-0.5">عدد الحركات</span>
                            <span class="text-sm font-black text-gray-800">
                                {{ number_format($categoryCount) }}
                            </span>
                            <span class="text-[9px] text-gray-400 font-bold">حركة</span>
                        </div>
                    </div>

                    {{-- أسفل البطاقة: زر الانتقال للمصروفات وأزرار التحكم --}}
                    <div class="p-4 pt-3 flex items-center justify-between gap-2 bg-white">
                        {{-- زر عرض مصروفات هذا النوع حصراً --}}
                        <a href="{{ $expensesUrl }}"
                            class="flex-1 bg-blue-50 hover:bg-blue-600 hover:text-white text-blue-700 text-xs font-black py-2 px-3 rounded-xl transition flex items-center justify-center gap-1.5"
                            title="عرض مصروفات {{ $category->name }}">
                            <i class="fa-solid fa-list-check text-xs"></i>
                            <span>عرض المصروفات</span>
                            <span class="text-[10px] bg-blue-100 text-blue-800 px-1.5 py-0.2 rounded-full group-hover:bg-white group-hover:text-blue-700 font-bold">
                                {{ $categoryCount }}
                            </span>
                        </a>

                        {{-- زر التعديل --}}
                        <a href="{{ route('expense-categories.edit', $category->id) }}"
                            class="w-8 h-8 rounded-xl bg-amber-50 hover:bg-amber-500 hover:text-white text-amber-600 flex items-center justify-center text-xs transition"
                            title="تعديل بيانات النوع">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </a>

                        {{-- زر الحذف --}}
                        <form action="{{ route('expense-categories.destroy', $category->id) }}" method="POST"
                            onsubmit="return confirm('هل أنت متأكد من حذف نوع المصروف «{{ $category->name }}»؟');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-8 h-8 rounded-xl bg-red-50 hover:bg-red-600 hover:text-white text-red-500 flex items-center justify-center text-xs transition"
                                title="حذف نوع المصروف">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>

                </div>
            @endforeach
        </div>
    @endif

</div>

<script>
    function toggleCustomDates(val) {
        const row = document.getElementById('customDateRow');
        if (val === 'custom') {
            row.classList.remove('hidden');
            row.classList.add('grid');
        } else {
            row.classList.add('hidden');
            row.classList.remove('grid');
            // تنظيف تواريخ المخصص إذا اختار قيمة سريعة
            if (val !== '') {
                const fromInput = document.querySelector('input[name="date_from"]');
                const toInput = document.querySelector('input[name="date_to"]');
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            }
        }
    }
</script>
@endsection
