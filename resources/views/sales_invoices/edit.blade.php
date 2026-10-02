@extends('layouts.app')

@section('page_title', 'تعديل فاتورة #' . $invoice->invoice_number)

@section('content')
<div class="container mx-auto max-w-5xl space-y-4 pb-12" dir="rtl">

    {{-- شريط التنقل --}}
    <div class="flex items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('sales-invoices.show', $invoice->id) }}"
                class="w-10 h-10 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع لتفاصيل الفاتورة">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <h2 class="text-base font-black text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-pen text-amber-500"></i>
                    <span>تعديل فاتورة</span>
                    <span class="font-mono text-amber-600 bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-100 text-xs">
                        #{{ $invoice->invoice_number }}
                    </span>
                </h2>
                <p class="text-xs text-gray-400">يمكنك تعديل الأصناف والكميات والخصم</p>
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs text-gray-500 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2">
            <i class="fa-solid fa-triangle-exclamation text-amber-500"></i>
            <span>التعديل يحدث حركات مخزن تلقائياً</span>
        </div>
    </div>

    {{-- رسائل الخطأ والنجاح --}}
    <div id="alertBox" class="hidden"></div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- ======== قائمة المنتجات للإضافة ======== --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="font-black text-gray-800 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-list text-blue-500"></i>
                        قائمة الأصناف
                    </h3>
                    <input type="text" id="menuSearch" placeholder="ابحث عن صنف..."
                        class="mt-2 w-full text-xs border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                </div>
                <div class="overflow-y-auto max-h-[500px] divide-y divide-gray-100" id="menuList">
                    @foreach($menus->groupBy('category.name') as $categoryName => $items)
                    <div class="menu-category">
                        <div class="px-3 py-1.5 bg-gray-50 text-[10px] font-black text-gray-500 uppercase tracking-wider sticky top-0">
                            {{ $categoryName ?? 'أخرى' }}
                        </div>
                        @foreach($items as $menu)
                        <button type="button"
                            onclick="addItem({{ $menu->id }}, '{{ addslashes($menu->name) }}', {{ $menu->price }})"
                            class="menu-item w-full flex items-center justify-between px-3 py-2.5 hover:bg-blue-50 transition text-right group"
                            data-name="{{ strtolower($menu->name) }}">
                            <div>
                                <div class="text-xs font-bold text-gray-800 group-hover:text-blue-700">{{ $menu->name }}</div>
                                <div class="text-[10px] text-gray-400">{{ number_format($menu->price, 2) }} ج</div>
                            </div>
                            <div class="w-6 h-6 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <i class="fa-solid fa-plus text-[9px]"></i>
                            </div>
                        </button>
                        @endforeach
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ======== سلة التعديل والإجماليات ======== --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- جدول الأصناف المختارة --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-black text-gray-800 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-cart-shopping text-amber-500"></i>
                        أصناف الفاتورة المعدّلة
                    </h3>
                    <span class="text-xs text-gray-400" id="itemsCount">0 صنف</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-right">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                                <th class="p-3">الصنف</th>
                                <th class="p-3 text-center w-28">الكمية</th>
                                <th class="p-3 text-center w-28">السعر</th>
                                <th class="p-3 text-left w-28">الإجمالي</th>
                                <th class="p-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody" class="divide-y divide-gray-100">
                            {{-- الأصناف الحالية من الفاتورة --}}
                        </tbody>
                    </table>
                    <div id="emptyState" class="p-8 text-center text-gray-400 hidden">
                        <i class="fa-solid fa-cart-plus text-3xl mb-2 text-gray-300"></i>
                        <p class="font-bold text-sm">لم تختر أي أصناف بعد</p>
                        <p class="text-xs mt-1">انقر على أي صنف من القائمة لإضافته</p>
                    </div>
                </div>
            </div>

            {{-- ملخص الحساب والحفظ --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-xs p-4 space-y-3">
                <h3 class="font-black text-gray-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-emerald-500"></i>
                    ملخص الفاتورة
                </h3>

                {{-- خانة الخصم --}}
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-gray-600 w-24 shrink-0">الخصم (ج.م):</label>
                    <input type="number" id="discount" value="{{ $invoice->discount }}" min="0" step="0.01"
                        oninput="recalculate()"
                        class="flex-1 text-sm border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-300 font-mono">
                </div>

                {{-- خانة سبب التعديل للرقابة --}}
                <div class="flex items-center gap-3">
                    <label class="text-xs font-bold text-gray-600 w-24 shrink-0">سبب التعديل:</label>
                    <input type="text" id="editReason" placeholder="توضيح سبب التعديل للرقابة والتدقيق..."
                        class="flex-1 text-xs border border-gray-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-300">
                </div>

                {{-- الإجماليات --}}
                <div class="bg-gray-50 rounded-xl p-3 space-y-2 text-xs">
                    <div class="flex justify-between text-gray-600">
                        <span>المجموع الفرعي:</span>
                        <span id="subtotalDisplay" class="font-mono font-bold">0.00 ج</span>
                    </div>
                    <div class="flex justify-between text-red-600 font-bold">
                        <span>الخصم:</span>
                        <span id="discountDisplay" class="font-mono">- 0.00 ج</span>
                    </div>
                    <div class="border-t border-gray-200 pt-2 flex justify-between font-black text-gray-900">
                        <span>الإجمالي الصافي:</span>
                        <span id="totalDisplay" class="font-mono text-emerald-600 text-base">0.00 ج.م</span>
                    </div>
                </div>

                {{-- أزرار الحفظ والإلغاء --}}
                <div class="flex gap-2 pt-1">
                    <button type="button" id="saveBtn" onclick="saveInvoice()"
                        class="flex-1 bg-amber-500 hover:bg-amber-600 text-white font-black py-2.5 rounded-xl text-sm transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span>حفظ التعديلات</span>
                    </button>
                    <a href="{{ route('sales-invoices.show', $invoice->id) }}"
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-sm transition flex items-center gap-2">
                        <i class="fa-solid fa-xmark"></i>
                        إلغاء
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // ======== البيانات الأولية من الفاتورة ========
    let items = [];

    // بيانات الأصناف الحالية من الـ PHP
    const currentItems = @json($invoice->items->map(function($item) {
        return [
            'menu_id'  => $item->menu_id,
            'name'     => $item->menu->name ?? 'صنف محذوف',
            'price'    => (float) $item->item_price,
            'quantity' => $item->quantity,
        ];
    }));

    // تحميل الأصناف الحالية فور فتح الصفحة
    window.addEventListener('DOMContentLoaded', () => {
        currentItems.forEach(item => {
            addItem(item.menu_id, item.name, item.price, item.quantity);
        });
    });

    // ======== دوال إدارة الأصناف ========
    function addItem(menuId, name, price, quantity = 1) {
        const existing = items.find(i => i.menu_id === menuId);
        if (existing) {
            existing.quantity += quantity;
            renderItems();
            return;
        }
        items.push({ menu_id: menuId, name, price: parseFloat(price), quantity });
        renderItems();
    }

    function removeItem(menuId) {
        items = items.filter(i => i.menu_id !== menuId);
        renderItems();
    }

    function changeQty(menuId, delta) {
        const item = items.find(i => i.menu_id === menuId);
        if (item) {
            item.quantity = Math.max(1, item.quantity + delta);
            renderItems();
        }
    }

    function renderItems() {
        const tbody = document.getElementById('itemsTableBody');
        const emptyState = document.getElementById('emptyState');
        const itemsCount = document.getElementById('itemsCount');

        if (items.length === 0) {
            tbody.innerHTML = '';
            emptyState.classList.remove('hidden');
            itemsCount.textContent = '0 صنف';
            recalculate();
            return;
        }

        emptyState.classList.add('hidden');
        itemsCount.textContent = `${items.length} صنف`;

        tbody.innerHTML = items.map(item => `
            <tr>
                <td class="p-3 font-bold text-gray-800">${item.name}</td>
                <td class="p-3 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                        <button type="button" onclick="changeQty(${item.menu_id}, -1)"
                            class="w-6 h-6 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-black text-xs flex items-center justify-center">-</button>
                        <span class="font-mono font-black text-blue-600 w-8 text-center">${item.quantity}</span>
                        <button type="button" onclick="changeQty(${item.menu_id}, 1)"
                            class="w-6 h-6 rounded-lg bg-blue-100 hover:bg-blue-200 text-blue-700 font-black text-xs flex items-center justify-center">+</button>
                    </div>
                </td>
                <td class="p-3 text-center font-mono text-gray-500">${item.price.toFixed(2)} ج</td>
                <td class="p-3 text-left font-mono font-bold text-gray-800">${(item.price * item.quantity).toFixed(2)} ج</td>
                <td class="p-3 text-center">
                    <button type="button" onclick="removeItem(${item.menu_id})"
                        class="w-6 h-6 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 flex items-center justify-center text-xs">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        recalculate();
    }

    function recalculate() {
        const subtotal  = items.reduce((sum, i) => sum + i.price * i.quantity, 0);
        const discount  = parseFloat(document.getElementById('discount').value) || 0;
        const total     = Math.max(0, subtotal - discount);

        document.getElementById('subtotalDisplay').textContent  = subtotal.toFixed(2) + ' ج';
        document.getElementById('discountDisplay').textContent  = '- ' + discount.toFixed(2) + ' ج';
        document.getElementById('totalDisplay').textContent     = total.toFixed(2) + ' ج.م';
    }

    // ======== بحث المنتجات ========
    document.getElementById('menuSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.menu-item').forEach(btn => {
            const name = btn.dataset.name || '';
            btn.style.display = (!q || name.includes(q)) ? '' : 'none';
        });
        document.querySelectorAll('.menu-category').forEach(cat => {
            const visible = Array.from(cat.querySelectorAll('.menu-item')).some(b => b.style.display !== 'none');
            cat.style.display = visible ? '' : 'none';
        });
    });

    // ======== حفظ التعديلات عبر AJAX ========
    function saveInvoice() {
        if (items.length === 0) {
            showAlert('يجب أن تحتوي الفاتورة على صنف واحد على الأقل.', 'error');
            return;
        }

        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جارٍ الحفظ...';

        const payload = {
            _method:  'PUT',
            _token:   '{{ csrf_token() }}',
            discount: parseFloat(document.getElementById('discount').value) || 0,
            reason:   document.getElementById('editReason') ? document.getElementById('editReason').value.trim() : '',
            items:    items.map(i => ({ menu_id: i.menu_id, quantity: i.quantity })),
        };

        fetch('{{ route("sales-invoices.update", $invoice->id) }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showAlert('✅ تم تعديل الفاتورة بنجاح! جارٍ التحويل...', 'success');
                setTimeout(() => { window.location.href = data.redirect; }, 1200);
            } else {
                showAlert('❌ ' + (data.error || 'حدث خطأ غير متوقع.'), 'error');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>حفظ التعديلات</span>';
            }
        })
        .catch(() => {
            showAlert('❌ فشل الاتصال بالخادم، حاول مرة أخرى.', 'error');
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>حفظ التعديلات</span>';
        });
    }

    function showAlert(msg, type) {
        const box = document.getElementById('alertBox');
        const colors = type === 'success'
            ? 'bg-emerald-50 border-emerald-300 text-emerald-800'
            : 'bg-red-50 border-red-300 text-red-800';
        box.className = `border rounded-2xl px-4 py-3 text-sm font-bold ${colors}`;
        box.textContent = msg;
        box.classList.remove('hidden');
        box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
</script>
@endpush
@endsection
