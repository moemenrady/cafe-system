@extends('layouts.app')

@section('page_title', 'تقرير متابعة المخزون والتكاليف')

@section('content')
<div class="container mx-auto space-y-6 pb-16 max-w-6xl" dir="rtl">

    {{-- رأس التقرير وإجراءات التصدير والطباعة --}}
    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-4 print:hidden">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-700 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h2 class="text-xl font-black text-gray-800">تقرير متابعة المخزون والتكاليف اليومي</h2>
                    <p class="text-xs text-gray-400 mt-0.5">مطابق لنموذج شيت Excel الرسمي لإدارة المستودعات وتقييم الأرصدة المالية</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
            <a href="{{ route('inventory.export_tracking') }}"
               class="flex-1 sm:flex-none bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold px-4 py-2.5 rounded-2xl text-xs flex items-center justify-center gap-2 shadow-sm transition">
                <i class="fa-solid fa-file-excel text-sm"></i>
                <span>تصدير Excel (CSV)</span>
            </a>

            <button type="button" onclick="window.print()"
                    class="flex-1 sm:flex-none bg-sky-600 hover:bg-sky-700 active:scale-95 text-white font-bold px-4 py-2.5 rounded-2xl text-xs flex items-center justify-center gap-2 shadow-sm transition">
                <i class="fa-solid fa-print text-sm"></i>
                <span>طباعة التقرير A4</span>
            </button>

            <a href="{{ route('inventory.index') }}"
               class="px-4 py-2.5 rounded-2xl bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs flex items-center gap-1.5 transition">
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                <span>المخزن الرئيسي</span>
            </a>
        </div>
    </div>

    {{-- ورقة التقرير المطبوعة الرسمية (A4 Sheet Replica) --}}
    <div class="bg-white p-6 sm:p-10 rounded-3xl border border-gray-200/80 shadow-sm space-y-6 print:border-none print:shadow-none print:p-0">

        {{-- ترويسة التقرير الرسمية --}}
        <div class="border-b-2 border-sky-800 pb-5 flex flex-col sm:flex-row justify-between items-start sm:items-end gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black text-sky-900 tracking-tight">تقرير متابعة المخزون والتكاليف</h1>
                <p class="text-sm font-bold text-gray-500 mt-1">ملخص الأرصدة والقيم المالية للأصناف المتاحة</p>
            </div>
            <div class="text-left font-mono text-xs text-gray-600 space-y-1 bg-gray-50 sm:bg-transparent p-3 sm:p-0 rounded-xl w-full sm:w-auto">
                <div><span class="font-bold text-gray-400">التاريخ:</span> {{ now()->locale('ar')->isoFormat('D MMMM YYYY') }}</div>
                <div><span class="font-bold text-gray-400">المرجع:</span> <span class="text-sky-700 font-bold">متابعة_المخزون_والتكاليف.xlsx</span></div>
                <div><span class="font-bold text-gray-400">إعداد:</span> إدارة المستودعات والمخازن</div>
            </div>
        </div>

        {{-- كروت الإحصائيات الثلاثية (KPI Summary Cards) --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            {{-- كارت 1: إجمالي قيمة المخزون --}}
            <div class="border-2 border-sky-600/40 bg-sky-50/30 p-5 rounded-2xl text-center flex flex-col justify-center items-center">
                <span class="text-xs font-bold text-gray-500 block mb-1">إجمالي قيمة المخزون</span>
                <div class="text-2xl sm:text-3xl font-black text-sky-900 font-mono">
                    {{ number_format($totalValuation, 2) }}
                    <span class="text-xs font-bold text-sky-700 mr-1">ج.م</span>
                </div>
            </div>

            {{-- كارت 2: الأصناف المتاحة (عدد) --}}
            <div class="border-2 border-gray-200 bg-gray-50/40 p-5 rounded-2xl text-center flex flex-col justify-center items-center">
                <span class="text-xs font-bold text-gray-500 block mb-1">الأصناف المتاحة (عدد)</span>
                <div class="text-2xl sm:text-3xl font-black text-gray-800 font-mono">
                    {{ number_format($totalAvailableUnits) }}
                    <span class="text-xs font-bold text-gray-500 mr-1">وحدة</span>
                </div>
            </div>

            {{-- كارت 3: أصناف تحتاج إعادة طلب --}}
            <div class="border-2 {{ $reorderItemsCount > 0 ? 'border-amber-400/60 bg-amber-50/30' : 'border-gray-200 bg-gray-50/40' }} p-5 rounded-2xl text-center flex flex-col justify-center items-center">
                <span class="text-xs font-bold text-gray-500 block mb-1">أصناف تحتاج إعادة طلب</span>
                <div class="text-2xl sm:text-3xl font-black {{ $reorderItemsCount > 0 ? 'text-amber-600' : 'text-gray-800' }} font-mono">
                    {{ $reorderItemsCount }}
                    <span class="text-xs font-bold text-gray-500 mr-1">أصناف</span>
                </div>
            </div>
        </div>

        {{-- شريط التصفية والبحث (يختفي في الطباعة) --}}
        <div class="bg-gray-50/70 p-4 rounded-2xl border border-gray-100 print:hidden space-y-3">
            <form method="GET" action="{{ route('inventory.daily_tracking') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                <div class="sm:col-span-5 relative">
                    <i class="fa-solid fa-magnifying-glass absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="بحث باسم الصنف أو الكود..."
                           class="w-full bg-white border border-gray-200 rounded-xl pr-9 pl-3 py-2 text-xs outline-none focus:ring-2 focus:ring-sky-500">
                </div>

                <div class="sm:col-span-3">
                    <select name="category" onchange="this.form.submit()"
                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 outline-none">
                        <option value="all">كل التصنيفات</option>
                        @foreach($categoriesList as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <select name="status" onchange="this.form.submit()"
                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2 text-xs font-medium text-gray-700 outline-none">
                        <option value="all">كل الحالات</option>
                        <option value="متوفر" {{ request('status') === 'متوفر' ? 'selected' : '' }}>متوفر</option>
                        <option value="منخفض" {{ request('status') === 'منخفض' ? 'selected' : '' }}>منخفض</option>
                        <option value="نفد" {{ request('status') === 'نفد' ? 'selected' : '' }}>نفد</option>
                    </select>
                </div>

                <div class="sm:col-span-2 flex items-center gap-1.5">
                    <button type="submit" class="flex-1 px-3 py-2 rounded-xl bg-sky-600 hover:bg-sky-700 text-white font-bold text-xs transition">
                        تطبيق
                    </button>
                    <a href="{{ route('inventory.daily_tracking') }}" class="px-2.5 py-2 rounded-xl bg-white border border-gray-200 text-gray-500 hover:text-gray-800 text-xs transition" title="إعادة تعيين">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                </div>
            </form>
        </div>

        {{-- جدول تفاصيل المخزون (جدول رسمي مطابق للشيت) --}}
        <div>
            <div class="text-right pb-2">
                <h3 class="text-base font-black text-gray-800">جدول تفاصيل المخزون</h3>
            </div>

            <div class="overflow-x-auto border border-gray-300 rounded-xl shadow-2xs">
                <table class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 font-bold border-b border-gray-300">
                            <th class="p-3 text-center border-l border-gray-200 w-24">كود الصنف</th>
                            <th class="p-3 border-l border-gray-200">اسم الصنف / البيان</th>
                            <th class="p-3 border-l border-gray-200 w-36">التصنيف</th>
                            <th class="p-3 text-center border-l border-gray-200 w-24">الرصيد</th>
                            <th class="p-3 text-left border-l border-gray-200 w-28">تكلفة الوحدة</th>
                            <th class="p-3 text-left border-l border-gray-200 w-32">القيمة الإجمالية</th>
                            <th class="p-3 text-center w-24">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 font-medium text-gray-800">
                        @php $pageTotalValuation = 0; @endphp
                        @forelse($items as $item)
                            @php
                                $itemVal = (float) $item->quantity * (float) $item->unit_price;
                                $pageTotalValuation += $itemVal;
                            @endphp
                            <tr class="hover:bg-sky-50/30 transition-colors {{ $loop->even ? 'bg-gray-50/50' : 'bg-white' }}">
                                {{-- كود الصنف --}}
                                <td class="p-2.5 text-center font-mono font-bold text-gray-600 border-l border-gray-200">
                                    {{ $item->item_code }}
                                </td>

                                {{-- اسم الصنف --}}
                                <td class="p-2.5 font-bold text-gray-900 border-l border-gray-200">
                                    {{ $item->name }}
                                </td>

                                {{-- التصنيف --}}
                                <td class="p-2.5 text-gray-600 border-l border-gray-200">
                                    {{ $item->category ?? 'خامات ومشروبات' }}
                                </td>

                                {{-- الرصيد --}}
                                <td class="p-2.5 text-center font-mono font-bold text-gray-900 border-l border-gray-200">
                                    {{ number_format($item->quantity, $item->unit === 'قطعة' || $item->unit === 'علبة' ? 0 : 2) }}
                                    <span class="text-[10px] text-gray-400 font-sans block sm:inline">{{ $item->unit }}</span>
                                </td>

                                {{-- تكلفة الوحدة --}}
                                <td class="p-2.5 text-left font-mono font-semibold text-gray-700 border-l border-gray-200">
                                    {{ number_format($item->unit_price, 2) }}
                                </td>

                                {{-- القيمة الإجمالية --}}
                                <td class="p-2.5 text-left font-mono font-black text-gray-900 border-l border-gray-200">
                                    {{ number_format($itemVal, 2) }}
                                </td>

                                {{-- الحالة --}}
                                <td class="p-2.5 text-center">
                                    @if($item->status === 'متوفر')
                                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-black inline-block w-full">
                                            متوفر
                                        </span>
                                    @elseif($item->status === 'منخفض')
                                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[11px] font-black inline-block w-full">
                                            منخفض
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-black inline-block w-full">
                                            نفد
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-8 text-center text-gray-400">
                                    لا توجد أصناف مطابقة لبحثك في المخزن.
                                </td>
                            </tr>
                        @endforelse

                        {{-- صف الإجمالي الكلي للقيمة --}}
                        <tr class="bg-sky-50/60 font-black text-gray-900 border-t-2 border-sky-800 text-sm">
                            <td colspan="5" class="p-3 text-left pl-6 font-bold border-l border-gray-300">
                                الإجمالي الكلي للقيمة (جنيه مصري):
                            </td>
                            <td colspan="2" class="p-3 text-left font-mono text-base font-black text-sky-900">
                                {{ number_format($pageTotalValuation, 2) }} ج.م
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- صندوق ملاحظات الإدارة وتوصيات الشراء (مطابق تماماً للشيت) --}}
        <div class="border border-gray-200 bg-gray-50/50 p-5 rounded-2xl space-y-3">
            <h4 class="text-sm font-black text-gray-800 border-b border-gray-200 pb-2">
                ملاحظات الإدارة وتوصيات الشراء
            </h4>

            <ul class="space-y-2 text-xs text-gray-700 leading-relaxed font-medium">
                {{-- نواقص المخزون --}}
                <li class="flex items-start gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500 mt-1.5 shrink-0"></span>
                    <div>
                        <strong class="text-gray-900">نواقص المخزون:</strong>
                        <span>يُرجى إصدار أمر شراء عاجل لكل من</span>
                        @if($outOfStockItems->count() > 0)
                            <strong class="text-rose-600 font-bold">({{ $outOfStockItems->pluck('name')->join(' - ') }})</strong>
                        @else
                            <strong class="text-emerald-600 font-bold">(لا يوجد أي صنف نافد حالياً)</strong>
                        @endif
                        <span>نظراً لنفاد رصيدها بالكامل.</span>
                    </div>
                </li>

                {{-- أرصدة منخفضة --}}
                <li class="flex items-start gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500 mt-1.5 shrink-0"></span>
                    <div>
                        <strong class="text-gray-900">أرصدة منخفضة:</strong>
                        <span>يجب مراجعة عروض الأسعار لتوريد</span>
                        @if($lowStockItems->count() > 0)
                            <strong class="text-amber-700 font-bold">({{ $lowStockItems->pluck('name')->join(' - ') }})</strong>
                        @else
                            <strong class="text-emerald-600 font-bold">(كافة الأصناف فوق حد الأمان)</strong>
                        @endif
                        <span>لتعزيز الأرصدة قبل النفاد.</span>
                    </div>
                </li>

                {{-- تقييم التكلفة --}}
                <li class="flex items-start gap-2">
                    <span class="w-2 h-2 rounded-full bg-sky-500 mt-1.5 shrink-0"></span>
                    <div>
                        <strong class="text-gray-900">تقييم التكلفة:</strong>
                        <span>تم تقييم التكلفة بناءً على سياسة الوارد أخيراً صادر أولاً (LIFO) / أو المتوسط المرجح حسب المتبع في النظام المالي للشركة.</span>
                    </div>
                </li>
            </ul>
        </div>

        {{-- تذييل الصفحة الرسمي للطباعة --}}
        <div class="hidden print:flex justify-between items-center text-[10px] text-gray-400 pt-6 border-t border-gray-200">
            <span>تقرير متابعة المخزون والتكاليف - كافيه UNO</span>
            <span>تاريخ الطباعة: {{ now()->format('Y/m/d H:i') }}</span>
        </div>

    </div>

</div>

@push('styles')
<style>
    @media print {
        header, aside, #sidebar, #mobileOverlay, .print\:hidden {
            display: none !important;
        }
        body {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .container {
            max-width: 100% !important;
            padding: 0 !important;
        }
    }
</style>
@endpush
@endsection
