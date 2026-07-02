@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-md shadow-md" dir="rtl">
        <h2 class="text-2xl font-semibold text-gray-700 text-right mb-6">إضافة خامة جديدة للمخزن</h2>
        
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">اسم المادة الخام</label>
                <input type="text" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="مثال: بن برازيلي، لبن، سكر" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الكمية الابتدائية</label>
                <input type="number" step="0.01" name="quantity" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="0.00" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">الوحدة</label>
                <input type="text" name="unit" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="مثال: جرام، مل، كيلو، قطعة" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">حد النواقص (Reorder Level)</label>
                <input type="number" step="0.01" name="reorder_level" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="ينبهك السيستم لو الكمية وصلت أقل من كام" required>
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded">
                    حفظ الخامة
                </button>
                <a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-gray-500 text-sm font-medium">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
