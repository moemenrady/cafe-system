@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-md shadow-md" dir="rtl">
        <h2 class="text-2xl font-semibold text-gray-700 text-right mb-6">تعديل القسم</h2>
        
        <form action="{{ route('categories.update', $category->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2 text-right" for="name">اسم القسم الجديد</label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-right @error('name') border-red-500 @enderror" required>
                @error('name')
                    <p class="text-red-500 text-xs italic mt-2 text-right">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    تحديث القسم
                </button>
                <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-gray-500 text-sm font-medium">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
