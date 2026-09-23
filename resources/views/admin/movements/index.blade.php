@extends('layouts.app')

@section('page_title', 'سجل الرقابة - حركات المشرفين')

@section('content')
    <div class="container mx-auto space-y-6" dir="rtl">

        <!-- Header Section -->
        <div class="bg-gradient-to-r from-red-50 to-orange-50 p-6 rounded-2xl border border-red-100 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-red-500 text-2xl"></i>
                        سجل تعديلات الفواتير
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">متابعة كافة عمليات التعديل التي يقوم بها المشرفون لضمان الأمان المالي</p>
                </div>
                <div class="bg-white px-4 py-2 rounded-xl shadow-sm">
                    <span class="text-sm text-gray-600">إجمالي الحركات</span>
                    <span class="block text-2xl font-bold text-red-600">{{ $stats['total'] }}</span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">اليوم</span>
                    <i class="fa-solid fa-calendar-day text-blue-500"></i>
                </div>
                <span class="block text-2xl font-bold mt-1">{{ $stats['today'] }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">إنشاء</span>
                    <i class="fa-solid fa-plus text-green-500"></i>
                </div>
                <span class="block text-2xl font-bold mt-1 text-green-600">{{ $stats['creates'] }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">تعديل</span>
                    <i class="fa-solid fa-pen text-amber-500"></i>
                </div>
                <span class="block text-2xl font-bold mt-1 text-amber-600">{{ $stats['updates'] }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">حذف</span>
                    <i class="fa-solid fa-trash text-red-500"></i>
                </div>
                <span class="block text-2xl font-bold mt-1 text-red-600">{{ $stats['deletes'] }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">المشرفين</span>
                    <i class="fa-solid fa-users text-purple-500"></i>
                </div>
                <span class="block text-2xl font-bold mt-1 text-purple-600">{{ $movements->pluck('creator_id')->unique()->count() }}</span>
            </div>
        </div>

        <!-- Movements List -->
        <div class="space-y-4">
            @forelse($movements as $movement)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-shadow duration-300">
                    <!-- Main Info - Always Visible -->
                    <div class="p-4 cursor-pointer" onclick="toggleMovement({{ $movement->id }})">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 flex-1">
                                <!-- Icon -->
                                <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center text-amber-600 flex-shrink-0">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </div>
                                
                                <!-- Main Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <span class="font-bold text-gray-800">
                                            {{ $movement->creator->name ?? 'غير معروف' }}
                                        </span>
                                        <span class="text-xs text-gray-400">|</span>
                                        <span class="font-black text-cafePrimary">
                                            فاتورة #{{ $movement->invoice->invoice_number ?? 'محذوفة' }}
                                        </span>
                                        <span class="text-xs bg-amber-50 text-amber-600 px-2 py-1 rounded-full border border-amber-100">
                                            تعديل
                                        </span>
                                        @if($movement->invoice)
                                            <span class="text-xs bg-green-50 text-green-600 px-2 py-1 rounded-full border border-green-100">
                                                {{ number_format($movement->invoice->total, 2) }} ج.م
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500 mt-1 truncate">
                                        {{ $movement->description }}
                                    </p>
                                </div>

                                <!-- Time & Toggle -->
                                <div class="flex items-center gap-3 flex-shrink-0">
                                    <span class="text-xs text-gray-400 font-mono hidden sm:block">
                                        {{ $movement->created_at->format('Y-m-d - h:i A') }}
                                    </span>
                                    <i id="arrow-{{ $movement->id }}" class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expanded Details - BEFORE & AFTER -->
                    <div id="details-{{ $movement->id }}" class="hidden border-t border-gray-100">
                        <div class="p-4 space-y-4">
                            
                            <!-- BEFORE - AFTER Comparison -->
                            @if($movement->old_data && $movement->new_data)
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- BEFORE -->
                                    <div class="bg-red-50 rounded-xl border border-red-200 overflow-hidden">
                                        <div class="bg-red-100 px-4 py-3 border-b border-red-200">
                                            <span class="font-bold text-red-700 flex items-center gap-2">
                                                <i class="fa-solid fa-arrow-left"></i> 
                                                قبل التعديل
                                            </span>
                                        </div>
                                        <div class="p-4 space-y-3">
                                            @php
                                                $oldData = is_array($movement->old_data) ? $movement->old_data : json_decode($movement->old_data, true);
                                            @endphp
                                            
                                            @if($oldData)
                                                <!-- Invoice Info -->
                                                <div class="bg-white rounded-lg p-3">
                                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                                        <div>
                                                            <span class="text-xs text-gray-500">رقم الفاتورة</span>
                                                            <p class="font-bold">#{{ $oldData['invoice_number'] ?? 'N/A' }}</p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-gray-500">الإجمالي</span>
                                                            <p class="font-bold text-red-600">{{ number_format($oldData['total'] ?? 0, 2) }} ج.م</p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-gray-500">الخصم</span>
                                                            <p class="font-bold">{{ number_format($oldData['discount'] ?? 0, 2) }} ج.م</p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-gray-500">طريقة الدفع</span>
                                                            <p class="font-bold">{{ $oldData['payment_method'] ?? 'N/A' }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Old Items -->
                                                @if(isset($oldData['items']) && is_array($oldData['items']))
                                                    <div class="bg-white rounded-lg overflow-hidden">
                                                        <div class="px-3 py-2 bg-gray-50 border-b text-xs font-bold text-gray-600">
                                                            الأصناف ({{ count($oldData['items']) }})
                                                        </div>
                                                        <div class="overflow-x-auto">
                                                            <table class="w-full text-xs">
                                                                <thead class="bg-gray-50">
                                                                    <tr>
                                                                        <th class="px-2 py-1 text-right">المنتج</th>
                                                                        <th class="px-2 py-1 text-center">الكمية</th>
                                                                        <th class="px-2 py-1 text-center">السعر</th>
                                                                        <th class="px-2 py-1 text-center">الإجمالي</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($oldData['items'] as $oldItem)
                                                                        <tr class="border-b">
                                                                            <td class="px-2 py-1">{{ $oldItem['menu']['name'] ?? $oldItem['name'] ?? 'منتج' }}</td>
                                                                            <td class="px-2 py-1 text-center">{{ $oldItem['quantity'] ?? 0 }}</td>
                                                                            <td class="px-2 py-1 text-center">{{ number_format($oldItem['item_price'] ?? $oldItem['price'] ?? 0, 2) }}</td>
                                                                            <td class="px-2 py-1 text-center">{{ number_format($oldItem['total'] ?? 0, 2) }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <p class="text-sm text-gray-500">لا توجد بيانات قديمة</p>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- AFTER -->
                                    <div class="bg-green-50 rounded-xl border border-green-200 overflow-hidden">
                                        <div class="bg-green-100 px-4 py-3 border-b border-green-200">
                                            <span class="font-bold text-green-700 flex items-center gap-2">
                                                <i class="fa-solid fa-arrow-right"></i> 
                                                بعد التعديل
                                            </span>
                                        </div>
                                        <div class="p-4 space-y-3">
                                            @php
                                                $newData = is_array($movement->new_data) ? $movement->new_data : json_decode($movement->new_data, true);
                                            @endphp
                                            
                                            @if($newData)
                                                <!-- Invoice Info -->
                                                <div class="bg-white rounded-lg p-3">
                                                    <div class="grid grid-cols-2 gap-2 text-sm">
                                                        <div>
                                                            <span class="text-xs text-gray-500">رقم الفاتورة</span>
                                                            <p class="font-bold">#{{ $newData['invoice_number'] ?? 'N/A' }}</p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-gray-500">الإجمالي</span>
                                                            <p class="font-bold text-green-600">{{ number_format($newData['total'] ?? 0, 2) }} ج.م</p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-gray-500">الخصم</span>
                                                            <p class="font-bold">{{ number_format($newData['discount'] ?? 0, 2) }} ج.م</p>
                                                        </div>
                                                        <div>
                                                            <span class="text-xs text-gray-500">طريقة الدفع</span>
                                                            <p class="font-bold">{{ $newData['payment_method'] ?? 'N/A' }}</p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- New Items -->
                                                @if(isset($newData['items']) && is_array($newData['items']))
                                                    <div class="bg-white rounded-lg overflow-hidden">
                                                        <div class="px-3 py-2 bg-gray-50 border-b text-xs font-bold text-gray-600">
                                                            الأصناف ({{ count($newData['items']) }})
                                                        </div>
                                                        <div class="overflow-x-auto">
                                                            <table class="w-full text-xs">
                                                                <thead class="bg-gray-50">
                                                                    <tr>
                                                                        <th class="px-2 py-1 text-right">المنتج</th>
                                                                        <th class="px-2 py-1 text-center">الكمية</th>
                                                                        <th class="px-2 py-1 text-center">السعر</th>
                                                                        <th class="px-2 py-1 text-center">الإجمالي</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($newData['items'] as $newItem)
                                                                        <tr class="border-b">
                                                                            <td class="px-2 py-1">{{ $newItem['menu']['name'] ?? $newItem['name'] ?? 'منتج' }}</td>
                                                                            <td class="px-2 py-1 text-center">{{ $newItem['quantity'] ?? 0 }}</td>
                                                                            <td class="px-2 py-1 text-center">{{ number_format($newItem['item_price'] ?? $newItem['price'] ?? 0, 2) }}</td>
                                                                            <td class="px-2 py-1 text-center">{{ number_format($newItem['total'] ?? 0, 2) }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <p class="text-sm text-gray-500">لا توجد بيانات جديدة</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Summary of Changes -->
                                <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                                    <div class="flex items-center gap-2 text-blue-700 font-bold mb-2">
                                        <i class="fa-solid fa-list-check"></i>
                                        ملخص التغييرات
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
                                        @php
                                            $oldTotal = isset($oldData['total']) ? floatval($oldData['total']) : 0;
                                            $newTotal = isset($newData['total']) ? floatval($newData['total']) : 0;
                                            $diff = $newTotal - $oldTotal;
                                        @endphp
                                        <div class="bg-white rounded-lg p-3">
                                            <span class="text-xs text-gray-500">تغير الإجمالي</span>
                                            <p class="font-bold {{ $diff > 0 ? 'text-green-600' : ($diff < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                                {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }} ج.م
                                            </p>
                                        </div>
                                        <div class="bg-white rounded-lg p-3">
                                            <span class="text-xs text-gray-500">عدد الأصناف القديم</span>
                                            <p class="font-bold">{{ isset($oldData['items']) ? count($oldData['items']) : 0 }}</p>
                                        </div>
                                        <div class="bg-white rounded-lg p-3">
                                            <span class="text-xs text-gray-500">عدد الأصناف الجديد</span>
                                            <p class="font-bold">{{ isset($newData['items']) ? count($newData['items']) : 0 }}</p>
                                        </div>
                                    </div>
                                </div>

                            @else
                                <div class="text-center py-8 text-gray-400">
                                    <i class="fa-solid fa-info-circle text-2xl"></i>
                                    <p class="mt-2">لا توجد بيانات مقارنة لعرضها</p>
                                </div>
                            @endif

                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-2xl p-12 text-center border border-gray-100">
                    <div class="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-shield-check text-green-500 text-2xl"></i>
                    </div>
                    <h4 class="text-lg font-bold text-gray-700">السجل الرقابي نظيف تماماً</h4>
                    <p class="text-sm text-gray-400 mt-1">لم يقم أي مشرف بإجراء تعديلات على الفواتير حتى الآن</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="bg-white p-4 rounded-2xl border border-gray-100">
            {{ $movements->links() }}
        </div>
    </div>

    <style>
        .hidden {
            display: none;
        }
        .transition-transform {
            transition: transform 0.3s ease;
        }
        .hover\:shadow-md:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>

    <script>
        function toggleMovement(id) {
            const details = document.getElementById(`details-${id}`);
            const arrow = document.getElementById(`arrow-${id}`);
            
            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                arrow.style.transform = 'rotate(180deg)';
            } else {
                details.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
            }
        }
    </script>
@endsection