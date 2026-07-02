@extends('layouts.app')

@section('content')
<div class="container mx-auto px-6 py-8">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-gray-700 text-3xl font-medium">مخزن المواد الخام</h3>
        <a href="{{ route('inventory.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-500 transition">
            إضافة خامة جديدة +
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
                    <th class="py-3 px-6 text-right">الخامة</th>
                    <th class="py-3 px-6 text-right">الكمية الحالية</th>
                    <th class="py-3 px-6 text-right">الوحدة</th>
                    <th class="py-3 px-6 text-right">حد الطلب (النواقص)</th>
                    <th class="py-3 px-6 text-center">حالة المخزن</th>
                    <th class="py-3 px-6 text-center">العمليات</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
                @forelse($items as $item)
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-right font-medium">{{ $item->name }}</td>
                        <td class="py-3 px-6 text-right font-semibold">{{ $item->quantity }}</td>
                        <td class="py-3 px-6 text-right text-gray-500">{{ $item->unit }}</td>
                        <td class="py-3 px-6 text-right text-yellow-600 font-medium">{{ $item->reorder_level }}</td>
                        <td class="py-3 px-6 text-center">
                            @if($item->quantity <= $item->reorder_level)
                                <span class="bg-red-200 text-red-800 py-1 px-3 rounded-full text-xs font-bold">ناقص / محتاج شراء</span>
                            @else
                                <span class="bg-green-200 text-green-800 py-1 px-3 rounded-full text-xs font-medium">آمن</span>
                            @endif
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center space-x-4 space-x-reverse">
                                <a href="{{ route('inventory.edit', $item->id) }}" class="text-yellow-600 hover:text-yellow-500 font-medium">تعديل</a>
                                <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المادة؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-500 font-medium">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-6 px-6 text-center text-gray-500 font-medium">المخزن فارغ حالياً.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
