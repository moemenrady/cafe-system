@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-gray-700 text-3xl font-medium">قائمة المنتجات (المينيو)</h3>
        <a href="{{ route('menu.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-500 transition">
            إضافة منتج جديد +
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-4 text-right" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow-md rounded my-6 overflow-x-auto">
        <table class="min-w-max w-full table-auto text-right" dir="rtl">
            <thead>
                <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                    <th class="py-3 px-6 text-right">الصورة</th>
                    <th class="py-3 px-6 text-right">الاسم</th>
                    <th class="py-3 px-6 text-right">القسم</th>
                    <th class="py-3 px-6 text-right">السعر</th>
                    <th class="py-3 px-6 text-center">الحالة</th>
                    <th class="py-3 px-6 text-center">العمليات</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                @forelse($menus as $item)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-right">
                            @if($item->image)
                                <img src="{{ asset('storage/' . $item->image) }}" class="w-12 h-12 rounded-full object-cover border">
                            @else
                                <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-400">بدون</div>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-right font-medium">{{ $item->name }}</td>
                        <td class="py-3 px-6 text-right">{{ $item->category->name ?? 'بدون قسم' }}</td>
                        <td class="py-3 px-6 text-right font-semibold text-green-600">{{ $item->price }} ج.م</td>
                        <td class="py-3 px-6 text-center">
                            @if($item->is_available)
                                <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-medium">متاح</span>
                            @else
                                <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-medium">غير متاح</span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center space-x-4 space-x-reverse">
                                <a href="{{ route('menu.edit', $item->id) }}" class="text-yellow-600 hover:text-yellow-500 font-medium">تعديل</a>
                                <form action="{{ route('menu.destroy', $item->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المنتج؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-500 font-medium">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 px-6 text-center text-gray-500 font-medium">لا توجد منتجات في المينيو بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
