@extends('layouts.app')

@section('content')
<div class="max-w-xl mx-auto bg-white p-6 rounded-2xl shadow-sm" dir="rtl">
    <h2 class="text-lg font-bold mb-4 text-right">تعديل المنتج: {{ $menu->name }}</h2>
    
    <form action="{{ route('menu.update', $menu->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="space-y-4">
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">اسم المنتج</label>
                <input type="text" name="name" value="{{ old('name', $menu->name) }}" class="w-full border rounded-xl px-3 py-2 text-xs" required>
                @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">القسم</label>
                <select name="category_id" class="w-full border rounded-xl px-3 py-2 text-xs bg-white" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $menu->category_id == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">السعر</label>
                <input type="number" step="0.01" name="price" value="{{ old('price', $menu->price) }}" class="w-full border rounded-xl px-3 py-2 text-xs" required>
                @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center">
                <input type="checkbox" name="is_available" id="is_available" value="1" {{ old('is_available', $menu->is_available) ? 'checked' : '' }} class="h-4 w-4 text-blue-600 rounded">
                <label class="mr-2 text-xs font-bold text-gray-700" for="is_available">متاح للطلب في الكافيه</label>
            </div>
            
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">صورة المنتج</label>
                @if($menu->image)
                    <div class="mb-2">
                        <img src="{{ asset('storage/' . $menu->image) }}" alt="{{ $menu->name }}" class="w-20 h-20 object-cover rounded-xl border">
                        <span class="text-[10px] text-gray-400">الصورة الحالية</span>
                    </div>
                @endif
                <input type="file" name="image" class="w-full text-xs mt-1">
                @error('image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-xl text-xs font-bold transition">تحديث المنتج</button>
                <a href="{{ route('menu.index') }}" class="w-1/3 text-center bg-gray-100 text-gray-600 py-3 rounded-xl text-xs font-bold hover:bg-gray-200 transition">إلغاء</a>
            </div>
        </div>
    </form>
</div>
@endsection