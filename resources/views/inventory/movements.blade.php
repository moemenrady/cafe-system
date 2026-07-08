@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8" dir="rtl">

        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h3 class="text-gray-800 text-3xl font-bold">سجل حركات الخامات</h3>
                <p class="text-sm text-gray-500 mt-1">تتبع دقيق لكل عمليات السحب، التوريد، والهالك في المخزن.</p>
            </div>
            <a href="{{ route('inventory.index') }}"
                class="bg-gray-100 text-gray-700 px-5 py-2.5 rounded-xl font-medium shadow-sm border border-gray-200 hover:bg-gray-200 transition-all duration-300 flex items-center gap-2">
                <span>العودة للمخزن</span>
                <span>↩️</span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between transition-transform hover:-translate-y-1">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">حركات السحب (اليوم)</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $todaySales }} حركة</h3>
                </div>
                <div class="w-12 h-12 bg-rose-50 text-rose-500 rounded-xl flex items-center justify-center text-xl">📉</div>
            </div>

            <div
                class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between transition-transform hover:-translate-y-1">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">عمليات التوريد (اليوم)</p>
                    <h3 class="text-2xl font-bold text-emerald-600">{{ $todayRestock }} توريد</h3>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center text-xl">📦
                </div>
            </div>

            <div
                class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between transition-transform hover:-translate-y-1">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">تسجيل الهالك (اليوم)</p>
                    <h3 class="text-2xl font-bold text-gray-600">{{ $todayWaste }} عملية</h3>
                </div>
                <div class="w-12 h-12 bg-gray-100 text-gray-500 rounded-xl flex items-center justify-center text-xl">🗑️
                </div>
            </div>

            <div
                class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between transition-transform hover:-translate-y-1">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">الخامة الأكثر استهلاكاً</p>
                    <h3 class="text-lg font-bold text-blue-600 truncate max-w-[120px]">{{ $mostConsumedName }}</h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl">🔥</div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm mb-8">
            <form method="GET" action="{{ route('inventory.movements') }}"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 items-end">

                <div class="lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">من تاريخ</label>
                    <input type="date" name="from" value="{{ request('from') }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">إلى تاريخ</label>
                    <input type="date" name="to" value="{{ request('to') }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">بحث بالخامة</label>
                    <input type="text" name="inventory" value="{{ request('inventory') }}" placeholder="مثال: بن برازيلي"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">بحث بالمشروب</label>
                    <input type="text" name="menu" value="{{ request('menu') }}" placeholder="مثال: Latte"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                </div>

                <div class="lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-2">نوع الحركة</label>
                    <select name="type"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all text-gray-700">
                        <option value="">الكل</option>
                        <option value="sale" {{ request('type') == 'sale' ? 'selected' : '' }}>بيع</option>
                        <option value="restock" {{ request('type') == 'restock' ? 'selected' : '' }}>توريد</option>
                        <option value="waste" {{ request('type') == 'waste' ? 'selected' : '' }}>هالك</option>
                        <option value="sale_update" {{ request('type') == 'sale_update' ? 'selected' : '' }}>تعديل بيع
                        </option>
                    </select>
                </div>

                <div class="lg:col-span-1 flex gap-2">
                    <button type="submit"
                        class="flex-1 bg-blue-600 text-white rounded-xl py-2.5 text-sm font-semibold shadow-md hover:bg-blue-700 transition-all">بحث</button>
                    <a href="{{ route('inventory.movements') }}"
                        class="flex-1 bg-gray-100 text-gray-600 rounded-xl py-2.5 text-sm font-semibold text-center border border-gray-200 hover:bg-gray-200 transition-all">إلغاء</a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-max w-full table-auto text-right">
                    <thead>
                        <tr
                            class="bg-gray-50 text-gray-500 uppercase text-xs font-bold tracking-wider border-b border-gray-100">
                            <th class="py-4 px-6">التاريخ و الوقت</th>
                            <th class="py-4 px-6 text-center">نوع الحركة</th>
                            <th class="py-4 px-6">الخامة</th>
                            <th class="py-4 px-6">الكمية</th>
                            <th class="py-4 px-6">الرصيد بعد الحركة</th>
                            <th class="py-4 px-6">التفاصيل / المشاريب</th>
                            <th class="py-4 px-6">الموظف</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light divide-y divide-gray-100">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-gray-50/80 transition-colors duration-200">

                                <td class="py-4 px-6 font-medium text-gray-700" dir="ltr">
                                    {{ $movement->created_at->format('Y-m-d h:i A') }}
                                </td>

                                <td class="py-4 px-6 text-center">
                                    @switch($movement->type)
                                        @case('restock')
                                            <span
                                                class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> توريد
                                            </span>
                                        @break

                                        @case('sale')
                                            <span
                                                class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> سحب (بيع)
                                            </span>
                                        @break

                                        @case('sale_update')
                                            <span
                                                class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-bold bg-amber-50 text-amber-600 border border-amber-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> تعديل مبيعات
                                            </span>
                                        @break

                                        @case('waste')
                                            <span
                                                class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> هالك
                                            </span>
                                        @break
                                    @endswitch
                                </td>

                                <td class="py-4 px-6 font-bold text-gray-800 text-base">
                                    {{ $movement->inventoryItem->name }}
                                </td>

                                <td class="py-4 px-6 font-bold text-base {{ in_array($movement->type, ['sale', 'waste']) ? 'text-rose-600' : 'text-emerald-600' }}"
                                    dir="ltr">
                                    {{ in_array($movement->type, ['sale', 'waste']) ? '-' : '+' }}
                                    {{ $movement->quantity }} <span
                                        class="text-xs text-gray-400 font-normal">{{ $movement->inventoryItem->unit }}</span>
                                </td>

                                <td class="py-4 px-6 font-bold text-gray-900 bg-gray-50/50">
                                    {{ $movement->balance_after }} <span
                                        class="text-xs text-gray-400 font-normal">{{ $movement->inventoryItem->unit }}</span>
                                </td>

                                <td class="py-4 px-6">
                                    @if ($movement->invoice)
                                        <div class="flex flex-col gap-2">
                                            <span
                                                class="text-xs font-bold text-gray-500 bg-white border border-gray-200 px-2 py-1 rounded w-max">
                                                رقم الفاتورة: #{{ $movement->invoice->invoice_number }}
                                            </span>
                                            <div class="flex flex-wrap gap-1 mt-1 max-w-[200px]">
                                                @foreach ($movement->invoice->items as $item)
                                                    <span
                                                        class="bg-blue-50 text-blue-700 border border-blue-100 text-[11px] px-2 py-0.5 rounded-md font-medium">
                                                        {{ $item->menu->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm italic">حركة مخزنية مباشرة</span>
                                    @endif
                                </td>

                                <td class="py-4 px-6 font-medium text-gray-700 flex items-center gap-2">
                                    <div
                                        class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600">
                                        {{ mb_substr($movement->user?->name ?? '?', 0, 1) }}
                                    </div>
                                    {{ $movement->user?->name ?? 'نظام' }}
                                </td>

                            </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 px-6 text-center text-gray-400">
                                        <div class="flex flex-col items-center justify-center gap-3">
                                            <span class="text-4xl">📭</span>
                                            <span class="font-medium text-base">لا توجد حركات مسجلة تطابق بحثك حالياً.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($movements->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>

        </div>
    @endsection
