@extends('layouts.app')

@section('title', 'تعديل الخامة')
@section('page_title', 'تعديل بيانات الخامة')

@section('content')
<div class="max-w-xl mx-auto bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-50">
        <h2 class="text-sm font-bold text-gray-800">تعديل بيانات: {{ $inventoryItem->name }}</h2>
    </div>

    <form action="{{ route('inventory.update', $inventoryItem->id) }}" method="POST" class="p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="space-y-1">
            <label class="text-xs font-bold text-gray-700">اسم الخامة</label>
            <input type="text" name="name" value="{{ old('name', $inventoryItem->name) }}" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs" required>
            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-gray-700">الكمية الحالية</label>
            <input type="number" step="0.01" name="quantity" value="{{ old('quantity', $inventoryItem->quantity) }}" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs" required>
            @error('quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-gray-700">وحدة القياس</label>
            <input type="text" name="unit" value="{{ old('unit', $inventoryItem->unit) }}" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs" required>
            @error('unit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="space-y-1">
            <label class="text-xs font-bold text-gray-700">حد الطلب (الحد الأدنى)</label>
            <input type="number" step="0.01" name="reorder_level" value="{{ old('reorder_level', $inventoryItem->reorder_level) }}" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs" required>
            @error('reorder_level') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="flex gap-3 mt-4">
            <a href="{{ route('inventory.index') }}" class="w-1/3 text-center bg-gray-100 text-gray-600 py-3 rounded-xl text-xs font-bold hover:bg-gray-200 transition">
                إلغاء
            </a>
            <button type="submit" class="w-2/3 bg-sidebar text-white py-3 rounded-xl text-xs font-bold hover:opacity-90 transition">
                حفظ التعديلات
            </button>
        </div>
    </form>
</div>
@endsection