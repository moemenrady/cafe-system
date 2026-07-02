@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-md shadow-md" dir="rtl">
        <h2 class="text-2xl font-semibold text-gray-700 text-right mb-6">إضافة منتج جديد للمينيو</h2>
        
        <form action="{{ route('menu.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">اسم المنتج</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" placeholder="مثال: Spanish Latte" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="category_id">القسم</label>
                <select name="category_id" id="category_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline bg-white" required>
                    <option value="">اختر القسم...</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="price">السعر (ج.م)</label>
                <input type="number" step="0.01" name="price" id="price" value="{{ old('price') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline" placeholder="0.00" required>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">صورة المنتج</label>
                <input type="file" name="image" id="image" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 focus:outline-none focus:shadow-outline bg-white">
            </div>

            <div class="mb-4 flex items-center">
                <input type="checkbox" name="is_available" id="is_available" value="1" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <label class="mr-2 block text-sm text-gray-900 font-bold" for="is_available">المنتج متاح حالياً في الكافيه</label>
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    حفظ المنتج
                </button>
                <a href="{{ route('menu.index') }}" class="text-gray-600 hover:text-gray-500 text-sm font-medium">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
