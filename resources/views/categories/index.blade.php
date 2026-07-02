@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-gray-700 text-3xl font-medium">أقسام المينيو</h3>
        <a href="{{ route('categories.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-500 transition">
            إضافة قسم جديد +
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-4 text-right" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-4 text-right" role="alert">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="min-w-max w-full table-auto text-right" dir="rtl">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-right">#ID</th>
                    <th class="py-3 px-6 text-right">اسم القسم</th>
                    <th class="py-3 px-6 text-center">العمليات</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                @forelse($categories as $category)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-right whitespace-nowrap font-medium">{{ $category->id }}</td>
                        <td class="py-3 px-6 text-right font-medium">{{ $category->name }}</td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center space-x-4 space-x-reverse">
                                <a href="{{ route('categories.edit', $category->id) }}" class="text-yellow-600 hover:text-yellow-500 font-medium">تعديل</a>
                                <form action="{{ route('categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا القسم؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-500 font-medium">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="py-6 px-6 text-center text-gray-500 font-medium">لا توجد أقسام مضافة بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
