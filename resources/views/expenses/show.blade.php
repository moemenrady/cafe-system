@extends('layouts.app')

@section('page_title', 'تفاصيل المصروف #' . $expense->id)

@section('content')
<div class="container mx-auto max-w-xl px-4 py-4" dir="rtl">
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-100 shadow-md space-y-6">
        
        {{-- الرأس --}}
        <div class="flex items-center justify-between pb-5 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-receipt"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-gray-800">تفاصيل سند المصروف</h2>
                    <p class="text-xs text-gray-400 font-mono">رقم المصروف: #{{ $expense->id }}</p>
                </div>
            </div>
            <a href="{{ route('expenses.index') }}"
                class="text-gray-400 hover:text-gray-600 w-9 h-9 rounded-xl bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition border border-gray-100"
                title="الرجوع للقائمة">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>

        {{-- كارت المبلغ البارز --}}
        <div class="bg-gradient-to-br from-red-500 to-rose-600 rounded-2xl p-5 text-white text-center shadow-lg shadow-red-500/20">
            <span class="text-xs font-medium text-white/80 block mb-1">المبلغ المصروف</span>
            <div class="text-3xl font-black font-mono">
                {{ number_format($expense->amount, 2) }} <span class="text-sm font-bold">ج.م</span>
            </div>
            <div class="inline-block mt-2 px-3 py-0.5 rounded-full bg-white/20 text-white text-xs font-bold">
                {{ $expense->category->name ?? $expense->title ?? 'مصروف عام' }}
            </div>
        </div>

        {{-- تفاصيل السجل --}}
        <div class="grid grid-cols-2 gap-3 text-xs">
            <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1">تاريخ المصروف:</span>
                <span class="font-bold text-gray-800 font-mono">
                    {{ $expense->expense_date ? $expense->expense_date->format('Y-m-d') : $expense->created_at->format('Y-m-d') }}
                </span>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1">الموظف المسؤول:</span>
                <span class="font-bold text-gray-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-user-pen text-blue-500"></i>
                    {{ $expense->creator->name ?? 'غير معروف' }}
                </span>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1">توقيت التسجيل:</span>
                <span class="font-mono text-gray-700">
                    {{ $expense->created_at->format('Y-m-d h:i A') }}
                </span>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-100">
                <span class="text-gray-400 block mb-1">آخر تحديث:</span>
                <span class="font-mono text-gray-700">
                    {{ $expense->updated_at->format('Y-m-d h:i A') }}
                </span>
            </div>
        </div>

        {{-- الملاحظات والبيان --}}
        <div>
            <span class="text-xs font-bold text-gray-700 block mb-1.5">البيان / الملاحظات:</span>
            <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100 text-xs text-gray-700 leading-relaxed min-h-[60px]">
                {{ $expense->notes ?: 'لا توجد ملاحظات مسجلة على هذا المصروف.' }}
            </div>
        </div>

        {{-- أزرار الإجراءات --}}
        <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
            <a href="{{ route('expenses.edit', $expense->id) }}"
                class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center justify-center gap-2 shadow-xs">
                <i class="fa-solid fa-pen"></i>
                <span>تعديل المصروف</span>
            </a>
            <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST"
                onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟');" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2.5 px-4 rounded-xl text-xs transition flex items-center gap-1.5 border border-red-200">
                    <i class="fa-solid fa-trash-can"></i>
                    <span>حذف</span>
                </button>
            </form>
            <a href="{{ route('expenses.index') }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold py-2.5 px-4 rounded-xl text-xs transition">
                رجوع
            </a>
        </div>

    </div>
</div>
@endsection
