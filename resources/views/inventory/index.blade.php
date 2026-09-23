@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-6 py-8" dir="rtl">

        <!-- رأس الصفحة -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h3 class="text-gray-800 text-3xl font-bold">مخزن المواد الخام</h3>
                <p class="text-sm text-gray-500 mt-1">إدارة الكميات، التوريدات الجديدة، ومتابعة الهالك والتالف.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('inventory.daily_tracking') }}"
                    class="bg-emerald-600 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>تقرير متابعة المخزون والتكاليف</span>
                </a>
                <a href="{{ route('inventory.movements') }}"
                    class="bg-gray-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs hover:bg-gray-800 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>سجل حركات الخامات</span>
                </a>
                <a href="{{ route('inventory.create') }}"
                    class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-md shadow-blue-600/20 hover:bg-blue-700 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-plus"></i>
                    <span>إضافة خامة جديدة</span>
                </a>
            </div>
        </div>

        <!-- كروت الإحصائيات السريعة -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">إجمالي الأصناف</p>
                    <h3 class="text-2xl font-bold text-gray-800">{{ $items->count() }} أصناف</h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-xl">
                    📦
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">أصناف أوشكت على النفاد</p>
                    <h3 class="text-2xl font-bold text-yellow-600">
                        {{ $items->where('quantity', '<=', 'reorder_level')->count() }} أصناف</h3>
                </div>
                <div class="w-12 h-12 bg-yellow-50 text-yellow-500 rounded-xl flex items-center justify-center text-xl">
                    ⚠️
                </div>
            </div>

            <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">حالة المخزن العامة</p>
                    <h3 class="text-xl font-bold text-green-600">مستقر وآمن</h3>
                </div>
                <div class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-xl">
                    🛡️
                </div>
            </div>
        </div>

        <!-- التنبيهات والرسائل -->
        @if (session('success'))
            <div
                class="bg-green-50 border-r-4 border-green-500 text-green-700 p-4 rounded-xl mb-6 shadow-sm flex items-center">
                <span class="font-medium">✅ {{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border-r-4 border-red-500 text-red-700 p-4 rounded-xl mb-6 shadow-sm flex items-center">
                <span class="font-medium">❌ {{ session('error') }}</span>
            </div>
        @endif

        <!-- جدول البيانات العصري -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-max w-full table-auto text-right">
                    <thead>
                        <tr
                            class="bg-gray-50 text-gray-500 uppercase text-xs font-bold tracking-wider border-b border-gray-100">
                            <th class="py-4 px-6">الخامة</th>
                            <th class="py-4 px-6">الكمية الحالية</th>
                            <th class="py-4 px-6">الوحدة</th>
                            <th class="py-4 px-6">حد الطلب (النواقص)</th>
                            <th class="py-4 px-6 text-center">حالة المخزن</th>
                            <th class="py-4 px-6 text-center">إجراء سريع</th>
                            <th class="py-4 px-6 text-center">العمليات</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light divide-y divide-gray-100">
                        @forelse($items as $item)
                            <tr class="hover:bg-gray-50/80 transition-colors duration-200">
                                <td class="py-4 px-6 font-semibold text-gray-800 text-base">{{ $item->name }}</td>
                                <td class="py-4 px-6 font-bold text-gray-900 text-base">{{ $item->quantity }}</td>
                                <td class="py-4 px-6 text-gray-500 font-medium">{{ $item->unit }}</td>
                                <td class="py-4 px-6 text-yellow-600 font-medium">{{ $item->reorder_level }}</td>
                                <td class="py-4 px-6 text-center">
                                    @if ($item->quantity <= $item->reorder_level)
                                        <span
                                            class="bg-red-50 text-red-600 py-1 px-3 rounded-full text-xs font-bold border border-red-100">⚠️
                                            محتاج شراء فوراً</span>
                                    @else
                                        <span
                                            class="bg-green-50 text-green-600 py-1 px-3 rounded-full text-xs font-medium border border-green-100">✅
                                            آمن</span>
                                    @endif
                                </td>

                                <!-- أزرار التوريد والإهلاك السريع -->
                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            onclick="openAdjustModal({{ $item->id }}, '{{ $item->name }}', '{{ $item->unit }}', 'restock')"
                                            class="bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200">
                                            ➕ توريد بضاعة
                                        </button>
                                        <button
                                            onclick="openAdjustModal({{ $item->id }}, '{{ $item->name }}', '{{ $item->unit }}', 'waste')"
                                            class="bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition-all duration-200">
                                            🗑️ تسجيل تالف / فساد
                                        </button>
                                    </div>
                                </td>

                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-3">
                                        <a href="{{ route('inventory.edit', $item->id) }}"
                                            class="text-blue-600 hover:text-blue-800 font-bold bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-lg transition-all">
                                            تعديل
                                        </a>
                                        <form action="{{ route('inventory.destroy', $item->id) }}" method="POST"
                                            onsubmit="return confirm('هل أنت متأكد من حذف هذه المادة بالكامل؟ سيتم فحص ارتباطها بالمنيو أولاً.');"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-red-600 hover:text-red-800 font-bold bg-red-50 hover:bg-red-100 px-2.5 py-1 rounded-lg transition-all">
                                                حذف
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 px-6 text-center text-gray-400 font-medium text-base">المخزن
                                    فارغ حالياً. قم بإضافة خامات لتبدأ العمل.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- النافذة المنبثقة الذكية للتعديل السريع (Tailwind Modal) -->
    <div id="adjustModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog"
        aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="closeAdjustModal()"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div
                class="inline-block align-bottom bg-white rounded-2xl text-right overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form id="adjustForm" method="POST" action="">
                    @csrf
                    <input type="hidden" id="adjustType" name="type" value="">

                    <div class="bg-white px-6 pt-6 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-start gap-4">
                            <div class="mt-3 text-right sm:mt-0 w-full">
                                <h3 class="text-xl leading-6 font-bold text-gray-900" id="modalTitle">تحديث المخزون</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500" id="modalDescription">قم بإدخال الكمية المطلوبة بدقة
                                        ليتم إدراجها في دفتر الحركات.</p>
                                </div>

                                <div class="mt-5">
                                    <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">الكمية
                                        (<span id="modalUnit" class="text-blue-600 font-bold"></span>)</label>
                                    <input type="number" step="0.01" name="amount" id="amount" required
                                        min="0.01"
                                        class="focus:ring-blue-500 focus:border-blue-500 block w-full px-4 py-3 text-base border border-gray-200 rounded-xl text-right font-bold text-gray-800"
                                        placeholder="0.00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button type="submit" id="submitBtn"
                            class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2.5 text-base font-medium text-white sm:ml-3 sm:w-auto transition-colors duration-200">
                            تأكيد وحفظ
                        </button>
                        <button type="button" onclick="closeAdjustModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto transition-colors duration-200">
                            إلغاء
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAdjustModal(itemId, itemName, itemUnit, actionType) {
            const modal = document.getElementById('adjustModal');
            const form = document.getElementById('adjustForm');
            const typeInput = document.getElementById('adjustType');
            const title = document.getElementById('modalTitle');
            const desc = document.getElementById('modalDescription');
            const unitSpan = document.getElementById('modalUnit');
            const submitBtn = document.getElementById('submitBtn');

            form.action = `/inventory/${itemId}/adjust`;
            typeInput.value = actionType;
            unitSpan.innerText = itemUnit;
            document.getElementById('amount').value = '';

            if (actionType === 'restock') {
                title.innerText = `توريد بضاعة لـ [${itemName}]`;
                desc.innerText = "أدخل الكمية الجديدة التي وصلت للمخزن لتتم إضافتها وحساب رصيد جديد.";
                submitBtn.className =
                    "w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2.5 text-base font-medium text-white bg-emerald-600 hover:bg-emerald-500 sm:w-auto transition-colors duration-200";
            } else {
                title.innerText = `تسجيل هالك وتالف لـ [${itemName}]`;
                desc.innerText =
                    "أدخل كمية المواد التي فسدت، انتهت صلاحيتها أو سُكبت ليتم خصمها تلقائياً من الـ Inventory.";
                submitBtn.className =
                    "w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2.5 text-base font-medium text-white bg-amber-600 hover:bg-amber-500 sm:w-auto transition-colors duration-200";
            }

            modal.classList.remove('hidden');
        }

        function closeAdjustModal() {
            document.getElementById('adjustModal').classList.add('hidden');
        }
    </script>
@endsection
