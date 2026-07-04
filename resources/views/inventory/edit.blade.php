@extends('layouts.app')

@section('title', 'تعديل الخامة')
@section('page_title', 'تعديل بيانات الخامة')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-xl mx-auto bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden" dir="rtl">
        <div class="p-5 border-b border-gray-50">
            <h2 class="text-xl font-bold text-gray-800">تعديل بيانات: {{ $inventoryItem->name }}</h2>
            <p class="text-xs text-gray-400 mt-1">تعديل البيانات الأساسية، لاحظ أن تغيير الكمية يدوياً هنا سيسجل كحركة تسوية في الدفتر.</p>
        </div>

        <form action="{{ route('inventory.update', $inventoryItem->id) }}" method="POST" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div class="space-y-1">
                <label class="text-sm font-bold text-gray-700 block">اسم الخامة</label>
                <input type="text" name="name" value="{{ old('name', $inventoryItem->name) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-1">
                <label class="text-sm font-bold text-gray-700 block">الكمية الحالية</label>
                <input type="number" step="0.01" name="quantity" value="{{ old('quantity', $inventoryItem->quantity) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                @error('quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-1">
                <label class="text-sm font-bold text-gray-700 block">وحدة القياس</label>
                <input type="text" name="unit" value="{{ old('unit', $inventoryItem->unit) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                @error('unit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="space-y-1">
                <label class="text-sm font-bold text-gray-700 block">حد الطلب (الحد الأدنى للنواقص)</label>
                <input type="number" step="0.01" name="reorder_level" value="{{ old('reorder_level', $inventoryItem->reorder_level) }}" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" required>
                @error('reorder_level') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-4 pt-4">
                <a href="{{ route('inventory.index') }}" class="w-1/3 text-center bg-gray-100 text-gray-600 py-3 rounded-xl text-sm font-bold hover:bg-gray-200 transition">
                    إلغاء
                </a>
                <button type="submit" class="w-2/3 bg-blue-600 text-white py-3 rounded-xl text-sm font-bold hover:bg-blue-500 shadow-md shadow-blue-100 transition">
                    حفظ التعديلات
                </button>
            </div>
        </form>
    </div>
</div>
@endsection