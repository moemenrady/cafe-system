@extends('layouts.app')

@section('page_title', 'تعديل المصروف #' . $expense->id)

@section('content')
<div class="container mx-auto max-w-xl px-4 py-4" dir="rtl">
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-100 shadow-md">
        
        {{-- الرأس --}}
        <div class="flex items-center justify-between pb-5 border-b border-gray-100 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-gray-800">تعديل بيانات المصروف</h2>
                    <p class="text-xs text-gray-400">تعديل المبلغ أو النوع أو الملاحظات</p>
                </div>
            </div>
            <a href="{{ route('expenses.index') }}"
                class="text-gray-400 hover:text-gray-600 w-9 h-9 rounded-xl bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition border border-gray-100"
                title="إلغاء والعودة">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>

        <form action="{{ route('expenses.update', $expense->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- نوع المصروف والمبلغ --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- نوع المصروف --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5" for="category_id">
                        نوع المصروف <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" id="category_id"
                        class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl px-3.5 py-2.5 text-xs font-bold outline-hidden transition @error('category_id') border-red-500 @enderror"
                        required>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $expense->category_id) == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- المبلغ --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5" for="amount">
                        المبلغ المصروف <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.25" min="0.25" name="amount" id="amount"
                            value="{{ old('amount', $expense->amount) }}"
                            class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl pr-3.5 pl-10 py-2.5 text-xs font-black outline-hidden transition @error('amount') border-red-500 @enderror"
                            required>
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-gray-400">ج.م</span>
                    </div>
                    @error('amount')
                        <p class="text-red-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- تاريخ المصروف والعنوان --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- تاريخ المصروف --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5" for="expense_date">
                        تاريخ المصروف <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="expense_date" id="expense_date"
                        value="{{ old('expense_date', $expense->expense_date ? $expense->expense_date->format('Y-m-d') : $expense->created_at->format('Y-m-d')) }}"
                        class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl px-3.5 py-2.5 text-xs font-mono font-bold outline-hidden transition @error('expense_date') border-red-500 @enderror"
                        required>
                    @error('expense_date')
                        <p class="text-red-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- عنوان / بيان إضافي --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5" for="title">
                        بيان مختصر
                    </label>
                    <input type="text" name="title" id="title" value="{{ old('title', $expense->title) }}"
                        class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl px-3.5 py-2.5 text-xs font-medium outline-hidden transition">
                </div>
            </div>

            {{-- الملاحظات والبيان التفصيلي --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5" for="notes">
                    ملاحظات أو تفاصيل المصروف
                </label>
                <textarea name="notes" id="notes" rows="3"
                    class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl p-3 text-xs font-medium outline-hidden transition resize-none">{{ old('notes', $expense->notes) }}</textarea>
                @error('notes')
                    <p class="text-red-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- معلومات المنشئ الأصلي --}}
            <div class="bg-gray-50 p-3 rounded-2xl border border-gray-200/70 text-xs text-gray-500 flex items-center justify-between">
                <span>الموظف المسجل: <strong>{{ $expense->creator->name ?? 'غير معروف' }}</strong></span>
                <span class="font-mono text-[11px]">{{ $expense->created_at->format('Y-m-d h:i A') }}</span>
            </div>

            {{-- أزرار الإجراءات --}}
            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                <button type="submit"
                    class="flex-1 bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-black py-3 px-6 rounded-xl text-xs transition shadow-md shadow-amber-500/25 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>حفظ التعديلات</span>
                </button>
                <a href="{{ route('expenses.index') }}"
                    class="px-5 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition">
                    إلغاء
                </a>
            </div>

        </form>

    </div>
</div>
@endsection
