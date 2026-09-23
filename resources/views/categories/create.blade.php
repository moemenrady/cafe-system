@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-md shadow-md" dir="rtl">
        <h2 class="text-2xl font-semibold text-gray-700 text-right mb-6">إضافة قسم جديد</h2>
        
        <form action="{{ route('categories.store') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2 text-right" for="name">اسم القسم</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-right @error('name') border-red-500 @enderror" placeholder="مثال: مشروبات باردة" required>
                @error('name')
                    <p class="text-red-500 text-xs italic mt-2 text-right">{{ $message }}</p>
                @enderror
            </div>
            
            <div class="flex items-center justify-between mt-6">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    حفظ القسم
                </button>
                <a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-gray-500 text-sm font-medium">إلغاء</a>
            </div>
        </form>
    </div>
</div>
@endsection
