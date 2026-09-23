@extends('layouts.app')

@section('page_title', 'إضافة نوع مصروف جديد')

@section('content')
<div class="container mx-auto max-w-2xl px-4 py-6" dir="rtl">

    {{-- زر الرجوع والعنوان --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-black text-gray-800">إضافة نوع مصروف جديد</h1>
            <p class="text-xs text-gray-400 mt-1">قم بإدخال بيانات تصنيف المصروفات لاعتماده في النظام</p>
        </div>
        <a href="{{ route('expense-categories.index') }}"
            class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-4 py-2 rounded-2xl text-xs transition">
            <i class="fa-solid fa-arrow-right"></i>
            <span>رجوع للأنواع</span>
        </a>
    </div>

    {{-- رسائل الأخطاء --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-2xl text-xs font-bold mb-5 space-y-1 shadow-xs">
            <div class="flex items-center gap-2 mb-1">
                <i class="fa-solid fa-triangle-exclamation text-red-500"></i>
                <span>يرجى تصحيح الأخطاء التالية:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-red-700 pr-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- بطاقة الفورم --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-6 md:p-8">
        <form action="{{ route('expense-categories.store') }}" method="POST" class="space-y-5">
            @csrf

            {{-- اسم نوع المصروف --}}
            <div>
                <label for="name" class="block text-xs font-black text-gray-700 mb-2">
                    اسم نوع المصروف <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-tag text-xs"></i>
                    </span>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        placeholder="مثال: فواتير كهرباء ومياه، خامات ومشروبات، صيانة دورية..."
                        class="w-full bg-gray-50 text-gray-800 pr-10 pl-4 py-2.5 rounded-2xl border border-gray-200 focus:border-blue-500 focus:bg-white outline-hidden text-xs font-bold transition">
                </div>
                <p class="text-[11px] text-gray-400 mt-1">يجب أن يكون الاسم معبراً وفريداً لتسهيل التصنيف والتقارير.</p>
            </div>

            {{-- الوصف / ملاحظات التصنيف --}}
            <div>
                <label for="description" class="block text-xs font-black text-gray-700 mb-2">
                    الوصف / تفاصيل إضافية <span class="text-gray-400 font-normal">(اختياري)</span>
                </label>
                <textarea name="description" id="description" rows="3"
                    placeholder="اكتب توضيحاً لنوع المصروفات التي تندرج تحت هذا التصنيف..."
                    class="w-full bg-gray-50 text-gray-800 p-4 rounded-2xl border border-gray-200 focus:border-blue-500 focus:bg-white outline-hidden text-xs font-medium transition resize-none">{{ old('description') }}</textarea>
            </div>

            {{-- حالة التفعيل --}}
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 flex items-center justify-between">
                <div>
                    <label for="is_active" class="text-xs font-black text-gray-800 block cursor-pointer">
                        تفعيل هذا النوع فوراً
                    </label>
                    <span class="text-[11px] text-gray-400 block">عند التفعيل، سيظهر في قائمة الأنواع المتاحة للموظفين عند تسجيل أي مصروف.</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            {{-- أزرار الإجراء --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                <a href="{{ route('expense-categories.index') }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-5 py-2.5 rounded-2xl text-xs transition">
                    إلغاء
                </a>
                <button type="submit"
                    class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black px-7 py-2.5 rounded-2xl text-xs transition flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-check"></i>
                    <span>حفظ نوع المصروف</span>
                </button>
            </div>

        </form>
    </div>

</div>
@endsection
