@extends('layouts.app')

@section('title', 'تعديل الخامة')
@section('page_title', 'تعديل بيانات الخامة')

@section('content')
    <div class="container mx-auto px-6 py-8">
        <div class="max-w-xl mx-auto bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden" dir="rtl">
            <div class="p-5 border-b border-gray-50 flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">تعديل بيانات: {{ $inventoryItem->name }}</h2>
                    <p class="text-xs text-gray-400 mt-1">تعديل البيانات الأساسية، لاحظ أن تغيير الكمية يدوياً هنا سيسجل
                        كحركة تسوية في الدفتر.</p>
                </div>
                <div class="bg-blue-50 text-blue-700 px-3 py-1.5 rounded-lg text-xs font-bold border border-blue-100">
                    السعر الحالي: {{ $inventoryItem->unit_price }}
                </div>
            </div>

            <form action="{{ route('inventory.update', $inventoryItem->id) }}" method="POST" class="p-6 space-y-5">
                @csrf
                @method('PUT')

                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700 block">اسم الخامة</label>
                    <input type="text" name="name" value="{{ old('name', $inventoryItem->name) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        required>
                    @error('name')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700 block">الكمية الحالية</label>
                    <input type="number" step="0.01" name="quantity"
                        value="{{ old('quantity', $inventoryItem->quantity) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        required>
                    @error('quantity')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700 block">وحدة القياس</label>
                    <input type="text" name="unit" value="{{ old('unit', $inventoryItem->unit) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        required>
                    @error('unit')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-3 relative overflow-hidden">
                    <div class="absolute right-0 top-0 bottom-0 w-1 bg-blue-500 rounded-r-2xl"></div>

                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-blue-600 uppercase tracking-wider block">قسم تسعير الخامة
                            (للتعديل)</span>
                        <span class="text-[10px] bg-gray-200 text-gray-600 px-2 py-1 rounded-md">افتراضي: 1000 وحدة</span>
                    </div>

                    @php
                        // المعادلة المبدئية: الكمية 1000 والسعر الإجمالي = سعر الوحدة * 1000
                        $defaultQuantity = 1000;
                        $defaultUnitPrice = $inventoryItem->unit_price ?? 0;
                        $defaultTotalPrice = $defaultUnitPrice * $defaultQuantity;
                    @endphp

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-600 block">الكمية</label>
                            <input type="number" step="0.01" id="price_quantity" name="price_quantity"
                                value="{{ old('price_quantity', $defaultQuantity) }}"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition-all"
                                placeholder="مثال: 10" required>
                            @error('price_quantity')
                                <span class="text-red-500 text-xs block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-600 block">إجمالي السعر</label>
                            <input type="number" step="0.01" id="total_price" name="total_price"
                                value="{{ old('total_price', $defaultTotalPrice) }}"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white transition-all"
                                placeholder="مثال: 500" required>
                            @error('total_price')
                                <span class="text-red-500 text-xs block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-gray-600 block">سعر الوحدة الواحد</label>
                            <input type="number" step="0.001" id="unit_price" name="unit_price"
                                value="{{ old('unit_price', $defaultUnitPrice) }}"
                                class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm bg-gray-100 text-gray-800 font-bold outline-none cursor-not-allowed"
                                placeholder="0.00" readonly>
                            @error('unit_price')
                                <span class="text-red-500 text-xs block">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-bold text-gray-700 block">حد الطلب (الحد الأدنى للنواقص)</label>
                    <input type="number" step="0.01" name="reorder_level"
                        value="{{ old('reorder_level', $inventoryItem->reorder_level) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                        required>
                    @error('reorder_level')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex flex-col-reverse sm:flex-row gap-3 pt-4">
                    <a href="{{ route('inventory.index') }}"
                        class="w-full sm:w-1/3 text-center bg-gray-100 text-gray-600 py-3 rounded-xl text-sm font-bold hover:bg-gray-200 transition-all duration-200">
                        إلغاء
                    </a>
                    <button type="submit"
                        class="w-full sm:w-2/3 bg-blue-600 text-white py-3 rounded-xl text-sm font-bold hover:bg-blue-500 shadow-md shadow-blue-100 transition-all duration-200 flex justify-center items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceQtyInput = document.getElementById('price_quantity');
            const totalPriceInput = document.getElementById('total_price');
            const unitPriceInput = document.getElementById('unit_price');

            function calculateUnitPrice() {
                const qty = parseFloat(priceQtyInput.value) || 0;
                const total = parseFloat(totalPriceInput.value) || 0;

                if (qty > 0 && total >= 0) {
                    const result = total / qty;
                    // تحديث سعر الوحدة مع لفت انتباه المستخدم بتغيير اللون لحظياً (Animation خفيف)
                    unitPriceInput.value = Number(result.toFixed(3));

                    unitPriceInput.classList.add('bg-blue-50', 'text-blue-700');
                    setTimeout(() => {
                        unitPriceInput.classList.remove('bg-blue-50', 'text-blue-700');
                    }, 300);
                } else {
                    unitPriceInput.value = '';
                }
            }

            // تحديث الحساب فوراً عند تعديل المستخدم لأي من الحقلين
            priceQtyInput.addEventListener('input', calculateUnitPrice);
            totalPriceInput.addEventListener('input', calculateUnitPrice);
        });
    </script>
@endsection
