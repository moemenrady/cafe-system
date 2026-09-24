@extends('layouts.app')

@section('title', 'إنشاء فاتورة شراء وتوريد جديدة')
@section('page_title', 'إنشاء فاتورة شراء جديدة')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto pb-16" dir="rtl">

    {{-- ==================== رأس الصفحة والتنقل السريع ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('purchase-invoices.index') }}"
                class="w-10 h-10 rounded-2xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع لقائمة فواتير الشراء">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>فاتورة شراء ومشتريات جديدة</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black bg-blue-50 text-blue-700 border border-blue-200 font-mono">
                        {{ $nextInvoiceNumber }}
                    </span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">تسجيل فاتورة شراء مواد خام وإضافتها فورياً للمخزن وتحديث الأسعار</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchase-invoices.index') }}"
                class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-bold transition">
                إلغاء
            </a>
            <button type="submit" form="purchaseInvoiceForm" id="saveInvoiceBtn"
                class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-blue-600/20 cursor-pointer">
                <i class="fa-solid fa-check"></i>
                <span>حفظ واعتماد الفاتورة</span>
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 space-y-1">
            <div class="font-black flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>يرجى مراجعة الأخطاء التالية:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 pr-4 text-[11px]">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ==================== نموذج فاتورة الشراء ==================== --}}
    <form action="{{ route('purchase-invoices.store') }}" method="POST" id="purchaseInvoiceForm" class="space-y-6">
        @csrf

        {{-- 1. البيانات الأساسية للفاتورة --}}
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-gray-100 shadow-xs space-y-5">
            <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-3">
                <i class="fa-solid fa-file-invoice text-blue-600"></i>
                <span>بيانات الفاتورة والمورد</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- رقم الفاتورة --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        رقم الفاتورة <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="invoice_number" id="invoiceNumberInput"
                        value="{{ old('invoice_number', $nextInvoiceNumber) }}" required
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-900 font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- اسم المورد --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        اسم المورد / الشركة <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="supplier_name" id="supplierNameInput"
                        value="{{ old('supplier_name') }}" required
                        placeholder="مثال: شركة النيل للألبان، بن عبد المعبود..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- تاريخ الفاتورة --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        تاريخ الشراء / التوريد <span class="text-rose-500">*</span>
                    </label>
                    <input type="date" name="invoice_date" id="invoiceDateInput"
                        value="{{ old('invoice_date', date('Y-m-d')) }}" required
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- طريقة الدفع --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        طريقة الدفع <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_method" id="paymentMethodSelect"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>💵 كاش نقدي</option>
                        <option value="instapay" {{ old('payment_method') === 'instapay' ? 'selected' : '' }}>📱 إنستا باي</option>
                        <option value="card" {{ old('payment_method') === 'card' ? 'selected' : '' }}>💳 فيزا / شبكة</option>
                        <option value="credit" {{ old('payment_method') === 'credit' ? 'selected' : '' }}>⏳ آجل (حساب مورد)</option>
                        <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>🏦 تحويل بنكي</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                {{-- حالة السداد --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        حالة السداد <span class="text-rose-500">*</span>
                    </label>
                    <select name="payment_status" id="paymentStatusSelect" onchange="handlePaymentStatusChange()"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <option value="paid" {{ old('payment_status') === 'paid' ? 'selected' : '' }}>✅ مدفوعة بالكامل</option>
                        <option value="partial" {{ old('payment_status') === 'partial' ? 'selected' : '' }}>⚠️ مدفوعة جزئياً</option>
                        <option value="unpaid" {{ old('payment_status') === 'unpaid' ? 'selected' : '' }}>❌ غير مدفوعة (آجلة بالكامل)</option>
                    </select>
                </div>

                {{-- المبلغ المدفوع --}}
                <div id="paidAmountContainer">
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        المبلغ المدفوع حالياً (ج.م)
                    </label>
                    <input type="number" step="0.01" min="0" name="paid_amount" id="paidAmountInput"
                        value="{{ old('paid_amount', '0.00') }}" oninput="calculateTotals()"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-gray-900 font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- المتبقي للمورد --}}
                <div>
                    <label class="block text-xs font-bold text-gray-500 mb-1.5">
                        المبلغ المتبقي على الفاتورة (ج.م)
                    </label>
                    <input type="text" id="remainingAmountDisplay" readonly value="0.00 ج.م"
                        class="w-full bg-gray-100 border border-gray-200 rounded-xl px-3 py-2.5 text-xs text-rose-700 font-mono font-black focus:outline-hidden">
                </div>
            </div>
        </div>

        {{-- 2. قسم تفاصيل وأصناف الفاتورة المتعددة (Dynamic Multi-Items Section) --}}
        <div class="bg-white rounded-3xl p-5 sm:p-6 border border-gray-100 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-gray-100 pb-3">
                <div>
                    <h3 class="text-xs font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-orange-500"></i>
                        <span>أصناف ومحتويات فاتورة الشراء</span>
                    </h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">ابحث عن الصنف لتعبئة بياناته تلقائياً أو أدخل اسماً جديداً لإنشائه فوراً في المخزن</p>
                </div>
                <button type="button" onclick="addNewItemRow()"
                    class="px-4 py-2 rounded-xl bg-orange-50 hover:bg-orange-100 text-orange-700 font-black text-xs transition flex items-center gap-1.5 border border-orange-200 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>إضافة صنف آخر للفاتورة</span>
                </button>
            </div>

            {{-- حاوية صفوف الأصناف --}}
            <div id="itemsContainer" class="space-y-4">
                {{-- الصف الأول افتراضياً --}}
            </div>

            {{-- زر إضافة صنف عريض في أسفل القائمة --}}
            <div class="pt-2">
                <button type="button" onclick="addNewItemRow()"
                    class="w-full py-3 rounded-2xl border-2 border-dashed border-gray-200 hover:border-orange-400 hover:bg-orange-50/50 text-gray-600 hover:text-orange-700 font-bold text-xs transition flex items-center justify-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-circle-plus text-orange-500 text-sm"></i>
                    <span>إضافة صنف آخر للفاتورة (Add Item)</span>
                </button>
            </div>
        </div>

        {{-- 3. الملخص المالي الإجمالي وملاحظات الفاتورة --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- الملاحظات --}}
            <div class="lg:col-span-2 bg-white rounded-3xl p-5 border border-gray-100 shadow-xs space-y-2">
                <label class="block text-xs font-bold text-gray-700">
                    <i class="fa-regular fa-comment-dots text-gray-400 ml-1"></i> ملاحظات إضافية على الفاتورة (اختياري)
                </label>
                <textarea name="notes" rows="4" placeholder="أدخل أي ملاحظات خاصة بالتسليم أو شروط المورد أو تفاصيل الشحنة..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-2xl p-3 text-xs text-gray-800 placeholder-gray-400 focus:outline-hidden focus:ring-2 focus:ring-blue-500">{{ old('notes') }}</textarea>
            </div>

            {{-- كارت الحساب المالي الختامي --}}
            <div class="bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-3xl p-5 shadow-xl space-y-4">
                <h4 class="text-xs font-black text-gray-300 uppercase tracking-wider flex items-center justify-between border-b border-gray-700 pb-2.5">
                    <span>الملخص المالي للفاتورة</span>
                    <i class="fa-solid fa-calculator text-cafePrimary"></i>
                </h4>

                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between items-center text-gray-300">
                        <span>إجمالي الأصناف:</span>
                        <span class="font-mono font-bold text-white text-sm" id="subtotalDisplay">0.00 ج.م</span>
                    </div>

                    <div class="flex justify-between items-center text-gray-300 gap-2">
                        <span class="shrink-0">الخصم (ج.م):</span>
                        <input type="number" step="0.01" min="0" name="discount" id="discountInput"
                            value="{{ old('discount', '0') }}" oninput="calculateTotals()"
                            class="w-24 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1 text-right font-mono text-xs text-amber-400 focus:ring-1 focus:ring-amber-400 focus:outline-hidden">
                    </div>

                    <div class="flex justify-between items-center text-gray-300 gap-2">
                        <span class="shrink-0">الضريبة (ج.م):</span>
                        <input type="number" step="0.01" min="0" name="tax" id="taxInput"
                            value="{{ old('tax', '0') }}" oninput="calculateTotals()"
                            class="w-24 bg-gray-800 border border-gray-700 rounded-lg px-2 py-1 text-right font-mono text-xs text-sky-400 focus:ring-1 focus:ring-sky-400 focus:outline-hidden">
                    </div>

                    <div class="border-t border-gray-700/80 pt-2.5 flex justify-between items-baseline">
                        <span class="font-black text-sm text-cafePrimary">الصافي الإجمالي:</span>
                        <span class="text-2xl font-black font-mono text-emerald-400" id="netTotalDisplay">0.00 <span class="text-xs">ج.م</span></span>
                    </div>

                    <div class="border-t border-gray-700/60 pt-2 flex justify-between items-center text-[11px] text-gray-400">
                        <span>المبلغ المدفوع:</span>
                        <span class="font-mono font-bold text-gray-200" id="paidDisplay">0.00 ج.م</span>
                    </div>

                    <div class="flex justify-between items-center text-[11px] text-rose-400 font-bold">
                        <span>المتبقي للمورد:</span>
                        <span class="font-mono" id="remainingDisplay">0.00 ج.م</span>
                    </div>
                </div>

                <button type="submit" id="saveInvoiceBottomBtn"
                    class="w-full mt-2 py-3 bg-blue-600 hover:bg-blue-500 active:scale-95 text-white font-black text-xs rounded-xl transition flex items-center justify-center gap-2 shadow-lg shadow-blue-600/30 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>حفظ واعتماد الفاتورة وتحديث المخزون</span>
                </button>
            </div>
        </div>

    </form>
</div>

@push('scripts')
<script>
    // قاعدة بيانات الأصناف المسبقة للبحث والإكمال التلقائي الفوري
    const dbInventoryItems = @json($allInventoryItems);
    const standardUnits = @json($units);
    const standardCategories = @json($categories);

    let rowCounter = 0;

    // إضافة صف صنف جديد
    function addNewItemRow(initialData = null) {
        rowCounter++;
        const container = document.getElementById('itemsContainer');
        const rowIndex = rowCounter;

        const rowCard = document.createElement('div');
        rowCard.className = 'item-row bg-gray-50/80 rounded-2xl p-4 border border-gray-200 transition-all hover:border-blue-300 relative';
        rowCard.id = `itemRow_${rowIndex}`;

        rowCard.innerHTML = `
            <div class="flex items-center justify-between pb-2.5 mb-3 border-b border-gray-200/80">
                <div class="flex items-center gap-2">
                    <span class="w-6 h-6 rounded-lg bg-gray-200 text-gray-700 text-xs font-black flex items-center justify-center row-num-badge">
                        ${container.children.length + 1}
                    </span>
                    <span class="text-xs font-bold text-gray-800">بيانات الصنف</span>
                    <span id="badge_new_${rowIndex}" class="hidden px-2 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-700 border border-purple-200">
                        ✨ صنف جديد للمخزن
                    </span>
                    <span id="stock_info_${rowIndex}" class="hidden text-[11px] text-gray-400 font-bold"></span>
                </div>
                <button type="button" onclick="removeItemRow(${rowIndex})"
                    class="text-gray-400 hover:text-rose-600 text-xs flex items-center gap-1 transition p-1 hover:bg-rose-50 rounded-lg" title="حذف هذا الصنف">
                    <i class="fa-regular fa-trash-can text-sm"></i>
                    <span class="hidden sm:inline">حذف</span>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-start">
                {{-- 1. اسم الصنف مع البحث والإكمال التلقائي --}}
                <div class="lg:col-span-4 relative">
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">
                        اسم الصنف الخام <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="items[${rowIndex}][name]" id="name_${rowIndex}" required
                            placeholder="اكتب للبحث أو لإضافة صنف جديد..."
                            autocomplete="off"
                            oninput="handleItemSearch(${rowIndex})"
                            onfocus="handleItemFocus(${rowIndex})"
                            class="w-full bg-white border border-gray-200 rounded-xl pr-8 pl-3 py-2 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                        <i class="fa-solid fa-magnifying-glass absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    </div>
                    <input type="hidden" name="items[${rowIndex}][inventory_item_id]" id="inv_id_${rowIndex}">

                    {{-- قائمة مقترحات البحث التلقائي --}}
                    <div id="suggestions_${rowIndex}" class="absolute z-30 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl max-h-48 overflow-y-auto hidden">
                    </div>
                </div>

                {{-- 2. وحدة القياس --}}
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">
                        الوحدة <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" list="unitsList" name="items[${rowIndex}][unit]" id="unit_${rowIndex}" required
                        value="قطعة" placeholder="كجم، لتر، علبة..."
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-2 text-xs text-gray-900 font-bold text-center focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- 3. التصنيف / القسم --}}
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">
                        القسم / التصنيف
                    </label>
                    <input type="text" list="catsList" name="items[${rowIndex}][category]" id="category_${rowIndex}"
                        value="عام" placeholder="القسم..."
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-2 text-xs text-gray-900 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- 4. الكمية المشتراة --}}
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">
                        الكمية <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][quantity]" id="qty_${rowIndex}" required
                        value="1" oninput="calculateRowTotal(${rowIndex})"
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-2 text-xs text-gray-900 font-mono font-black text-center focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>

                {{-- 5. سعر شراء الوحدة --}}
                <div class="lg:col-span-2">
                    <label class="block text-[11px] font-bold text-gray-600 mb-1">
                        سعر الوحدة (ج.م) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" name="items[${rowIndex}][unit_price]" id="price_${rowIndex}" required
                        value="0.00" oninput="calculateRowTotal(${rowIndex})"
                        class="w-full bg-white border border-gray-200 rounded-xl px-2.5 py-2 text-xs text-gray-900 font-mono font-black text-center focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>
            </div>

            {{-- إجمالي الصنف وملاحظته --}}
            <div class="mt-3 pt-2.5 border-t border-gray-200/60 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                <div class="flex-1 w-full sm:w-auto">
                    <input type="text" name="items[${rowIndex}][notes]" placeholder="ملاحظات الصنف (اختياري، مثل تاريخ الصلاحية أو كود التشغيلة)..."
                        class="w-full bg-white/70 border border-gray-200 rounded-lg px-2.5 py-1 text-[11px] text-gray-700 placeholder-gray-400 focus:outline-hidden focus:bg-white">
                </div>
                <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto">
                    <span class="text-xs font-bold text-gray-500">إجمالي الصنف:</span>
                    <span class="text-base font-black text-blue-600 font-mono" id="row_subtotal_${rowIndex}">0.00 ج.م</span>
                </div>
            </div>
        `;

        container.appendChild(rowCard);
        calculateRowTotal(rowIndex);
        updateRowNumbers();

        // إغلاق المقترحات عند النقر خارجها
        document.addEventListener('click', function(e) {
            const suggestions = document.getElementById(`suggestions_${rowIndex}`);
            const input = document.getElementById(`name_${rowIndex}`);
            if (suggestions && !suggestions.contains(e.target) && e.target !== input) {
                suggestions.classList.add('hidden');
            }
        });
    }

    // إزالة صف صنف
    function removeItemRow(rowIndex) {
        const rows = document.querySelectorAll('.item-row');
        if (rows.length <= 1) {
            alert('يجب أن تحتوي الفاتورة على صنف واحد على الأقل.');
            return;
        }
        const row = document.getElementById(`itemRow_${rowIndex}`);
        if (row) {
            row.remove();
            calculateTotals();
            updateRowNumbers();
        }
    }

    // تحديث أرقام الصفوف
    function updateRowNumbers() {
        const rows = document.querySelectorAll('.item-row');
        rows.forEach((row, index) => {
            const badge = row.querySelector('.row-num-badge');
            if (badge) badge.textContent = index + 1;
        });
    }

    // البحث في الأصناف والإكمال التلقائي
    function handleItemSearch(rowIndex) {
        const input = document.getElementById(`name_${rowIndex}`);
        const query = (input.value || '').trim().toLowerCase();
        const suggestionsBox = document.getElementById(`suggestions_${rowIndex}`);
        const invIdInput = document.getElementById(`inv_id_${rowIndex}`);
        const newBadge = document.getElementById(`badge_new_${rowIndex}`);
        const stockInfo = document.getElementById(`stock_info_${rowIndex}`);

        if (!query) {
            suggestionsBox.innerHTML = '';
            suggestionsBox.classList.add('hidden');
            invIdInput.value = '';
            newBadge.classList.add('hidden');
            stockInfo.classList.add('hidden');
            return;
        }

        const matches = dbInventoryItems.filter(i => 
            i.name.toLowerCase().includes(query) || (i.code && i.code.toLowerCase().includes(query))
        );

        let html = '';
        if (matches.length > 0) {
            matches.slice(0, 8).forEach(item => {
                html += `
                    <div onclick="selectExistingItem(${rowIndex}, ${item.id})"
                        class="p-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-100 flex items-center justify-between transition">
                        <div>
                            <span class="text-xs font-bold text-gray-800 block">${item.name}</span>
                            <span class="text-[10px] text-gray-400">${item.category || 'عام'} • الوحدة: ${item.unit || 'قطعة'}</span>
                        </div>
                        <div class="text-left font-mono">
                            <span class="text-xs font-black text-blue-600 block">${parseFloat(item.unit_price || 0).toFixed(2)} ج</span>
                            <span class="text-[10px] text-gray-400">الرصيد: ${parseFloat(item.quantity || 0)}</span>
                        </div>
                    </div>
                `;
            });
        }

        // خيار إضافة كصنف جديد
        html += `
            <div onclick="selectAsNewItem(${rowIndex})"
                class="p-2.5 bg-purple-50/60 hover:bg-purple-100/80 cursor-pointer text-purple-700 font-bold text-xs flex items-center gap-2 transition">
                <i class="fa-solid fa-sparkles text-xs"></i>
                <span>استخدام "<strong>${input.value}</strong>" كصنف جديد في المخزن</span>
            </div>
        `;

        suggestionsBox.innerHTML = html;
        suggestionsBox.classList.remove('hidden');

        // إذا كان يطابق بالضبط اسماً موجوداً
        const exactMatch = dbInventoryItems.find(i => i.name.toLowerCase() === query);
        if (exactMatch) {
            invIdInput.value = exactMatch.id;
            newBadge.classList.add('hidden');
            stockInfo.classList.remove('hidden');
            stockInfo.textContent = `(الرصيد الحالي: ${exactMatch.quantity} ${exactMatch.unit})`;
        } else {
            invIdInput.value = '';
            newBadge.classList.remove('hidden');
            stockInfo.classList.add('hidden');
        }
    }

    function handleItemFocus(rowIndex) {
        const input = document.getElementById(`name_${rowIndex}`);
        if (input.value.trim().length > 0) {
            handleItemSearch(rowIndex);
        }
    }

    // اختيار صنف موجود مسبقاً في الداتابيز
    function selectExistingItem(rowIndex, itemId) {
        const item = dbInventoryItems.find(i => i.id == itemId);
        if (!item) return;

        document.getElementById(`name_${rowIndex}`).value = item.name;
        document.getElementById(`inv_id_${rowIndex}`).value = item.id;
        document.getElementById(`unit_${rowIndex}`).value = item.unit || 'قطعة';
        document.getElementById(`category_${rowIndex}`).value = item.category || 'عام';
        document.getElementById(`price_${rowIndex}`).value = parseFloat(item.unit_price || 0).toFixed(2);

        document.getElementById(`badge_new_${rowIndex}`).classList.add('hidden');
        const stockInfo = document.getElementById(`stock_info_${rowIndex}`);
        stockInfo.classList.remove('hidden');
        stockInfo.textContent = `(الرصيد الحالي: ${item.quantity} ${item.unit})`;

        document.getElementById(`suggestions_${rowIndex}`).classList.add('hidden');
        calculateRowTotal(rowIndex);
    }

    // تحديد الصنف كجديد
    function selectAsNewItem(rowIndex) {
        document.getElementById(`inv_id_${rowIndex}`).value = '';
        document.getElementById(`badge_new_${rowIndex}`).classList.remove('hidden');
        document.getElementById(`stock_info_${rowIndex}`).classList.add('hidden');
        document.getElementById(`suggestions_${rowIndex}`).classList.add('hidden');
    }

    // حساب إجمالي الصف
    function calculateRowTotal(rowIndex) {
        const qty = parseFloat(document.getElementById(`qty_${rowIndex}`)?.value) || 0;
        const price = parseFloat(document.getElementById(`price_${rowIndex}`)?.value) || 0;
        const subtotal = Math.round(qty * price * 100) / 100;

        const subtotalDisplay = document.getElementById(`row_subtotal_${rowIndex}`);
        if (subtotalDisplay) {
            subtotalDisplay.textContent = subtotal.toFixed(2) + ' ج.م';
        }

        calculateTotals();
    }

    // حساب إجماليات الفاتورة بالكامل
    function calculateTotals() {
        let totalItemsSum = 0;
        const rows = document.querySelectorAll('.item-row');

        rows.forEach(row => {
            const rowIndex = row.id.replace('itemRow_', '');
            const qty = parseFloat(document.getElementById(`qty_${rowIndex}`)?.value) || 0;
            const price = parseFloat(document.getElementById(`price_${rowIndex}`)?.value) || 0;
            totalItemsSum += Math.round(qty * price * 100) / 100;
        });

        const discount = parseFloat(document.getElementById('discountInput').value) || 0;
        const tax = parseFloat(document.getElementById('taxInput').value) || 0;
        const netTotal = Math.max(0, Math.round((totalItemsSum - discount + tax) * 100) / 100);

        document.getElementById('subtotalDisplay').textContent = totalItemsSum.toFixed(2) + ' ج.م';
        document.getElementById('netTotalDisplay').innerHTML = netTotal.toFixed(2) + ' <span class="text-xs">ج.م</span>';

        // تحديث المدفوع والمتبقي حسب حالة السداد
        const paymentStatus = document.getElementById('paymentStatusSelect').value;
        const paidAmountInput = document.getElementById('paidAmountInput');
        let paidAmount = 0;

        if (paymentStatus === 'paid') {
            paidAmount = netTotal;
            paidAmountInput.value = netTotal.toFixed(2);
        } else if (paymentStatus === 'unpaid') {
            paidAmount = 0;
            paidAmountInput.value = '0.00';
        } else {
            paidAmount = parseFloat(paidAmountInput.value) || 0;
        }

        const remaining = Math.max(0, Math.round((netTotal - paidAmount) * 100) / 100);
        document.getElementById('remainingAmountDisplay').value = remaining.toFixed(2) + ' ج.م';
        document.getElementById('paidDisplay').textContent = paidAmount.toFixed(2) + ' ج.م';
        document.getElementById('remainingDisplay').textContent = remaining.toFixed(2) + ' ج.م';
    }

    function handlePaymentStatusChange() {
        const paymentStatus = document.getElementById('paymentStatusSelect').value;
        const paidContainer = document.getElementById('paidAmountContainer');
        const paidInput = document.getElementById('paidAmountInput');

        if (paymentStatus === 'unpaid') {
            paidInput.value = '0.00';
            paidInput.readOnly = true;
        } else if (paymentStatus === 'paid') {
            paidInput.readOnly = true;
        } else {
            paidInput.readOnly = false;
        }

        calculateTotals();
    }

    // تهيئة الصفحة بإضافة أول صنف تلقائياً
    document.addEventListener('DOMContentLoaded', function() {
        addNewItemRow();
        handlePaymentStatusChange();
    });
</script>

{{-- قوائم الإكمال التلقائي للوحدات والتصنيفات --}}
<datalist id="unitsList">
    @foreach($units as $u)
        <option value="{{ $u }}">{{ $u }}</option>
    @endforeach
</datalist>

<datalist id="catsList">
    @foreach($categories as $c)
        <option value="{{ $c }}">{{ $c }}</option>
    @endforeach
</datalist>
@endpush
@endsection
