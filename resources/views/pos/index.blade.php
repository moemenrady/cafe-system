@extends('layouts.app')

@section('page_title', 'شاشة الكاشير - POS')

@section('content')
<div class="relative pb-4" dir="rtl">

    {{-- ========== نافذة النجاح ========== --}}
    <div id="successModal"
        class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center hidden"
        onclick="hideSuccessModal()">
        <div class="bg-white rounded-3xl p-7 max-w-xs w-full mx-4 text-center shadow-2xl" onclick="event.stopPropagation()">
            <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-3 text-emerald-500 text-2xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h4 class="text-lg font-black text-gray-800 mb-1">تم الطلب بنجاح!</h4>
            <p id="successOrderNumber" class="text-sm font-bold text-blue-600 mb-1"></p>
            <p class="text-xs text-gray-500">تم إرسال الطلب للمطبخ وتحديث المخزون.</p>
        </div>
    </div>

    {{-- ========== Toast للأخطاء ========== --}}
    <div id="errorToast"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 hidden bg-red-600 text-white text-sm font-bold px-5 py-3 rounded-2xl shadow-xl flex items-center gap-2 max-w-sm text-center">
        <i class="fa-solid fa-triangle-exclamation shrink-0"></i>
        <span id="errorToastMsg">خطأ</span>
    </div>

    {{-- ========== Grid الرئيسي ========== --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">

        {{-- ==================== يسار: المنتجات ==================== --}}
        <div class="lg:col-span-8 space-y-3">

            {{-- شريط البحث والفئات --}}
            <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 space-y-3 sticky top-2 z-10">

                {{-- البحث --}}
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" id="searchInput"
                        class="w-full bg-gray-50 text-gray-800 pr-10 pl-10 py-2.5 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none font-medium text-sm transition-all"
                        placeholder="ابحث بسرعة... (أو اضغط أي حرف)">
                    <button id="clearSearch" onclick="clearSearch()"
                        class="absolute inset-y-0 left-0 hidden px-3 text-gray-400 hover:text-gray-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                {{-- الفئات --}}
                <div class="flex flex-wrap gap-1.5 items-center">
                    <button type="button" onclick="selectCategory('all')" id="tab-all"
                        class="category-tab bg-blue-600 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm shadow-blue-500/20">
                        📁 الكل
                        <span class="bg-blue-500/40 text-white text-[10px] font-black px-1.5 py-0.5 rounded-lg ml-1">
                            {{ $menus->count() }}
                        </span>
                    </button>
                    @foreach($categories as $category)
                        <button type="button" onclick="selectCategory('{{ $category->id }}')"
                            id="tab-{{ $category->id }}"
                            class="category-tab bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- شبكة المنتجات --}}
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 min-h-[50vh]">
                <div id="menuGrid" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                    @foreach ($menus as $menu)
                        <button type="button"
                            onclick='addItem("{{ $menu->id }}", @json($menu->name), {{ $menu->price }})'
                            data-id="{{ $menu->id }}"
                            data-name="{{ $menu->name }}"
                            data-category="{{ $menu->category_id }}"
                            class="menu-item-card bg-white hover:bg-blue-50/40 border border-gray-100 hover:border-blue-300 rounded-2xl flex flex-col overflow-hidden group transition-all duration-150 active:scale-95 shadow-sm hover:shadow-md h-36 relative">

                            @if ($menu->image)
                                <div class="w-full h-16 bg-gray-100 overflow-hidden shrink-0">
                                    <img src="{{ asset('storage/' . $menu->image) }}"
                                        alt="{{ $menu->name }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy">
                                </div>
                                <div class="p-2.5 flex-1 flex flex-col justify-between text-right">
                                    <span class="font-bold text-gray-800 text-xs line-clamp-2 group-hover:text-blue-600 transition-colors">
                                        {{ $menu->name }}
                                    </span>
                                    <span class="text-blue-600 font-black text-xs bg-blue-50 px-2 py-0.5 rounded-lg self-start">
                                        {{ number_format($menu->price, 2) }} ج
                                    </span>
                                </div>
                            @else
                                <div class="p-3 flex-1 flex flex-col justify-center items-center w-full text-center gap-2">
                                    <span class="font-bold text-gray-800 text-sm line-clamp-3 group-hover:text-blue-700 transition-colors">
                                        {{ $menu->name }}
                                    </span>
                                    <span class="text-blue-600 font-black text-xs bg-white border border-blue-100 px-2.5 py-1 rounded-xl shadow-sm">
                                        {{ number_format($menu->price, 2) }} ج
                                    </span>
                                </div>
                            @endif

                            {{-- بادج الكمية في السلة --}}
                            <span id="badge-{{ $menu->id }}"
                                class="hidden absolute top-1.5 left-1.5 w-5 h-5 bg-blue-600 text-white text-[10px] font-black rounded-full flex items-center justify-center shadow">
                            </span>
                        </button>
                    @endforeach
                </div>

                <div id="noResults" class="hidden text-center py-16 text-gray-400 font-medium text-sm">
                    <i class="fa-solid fa-box-open text-4xl text-gray-200 block mb-3"></i>
                    لم نجد أي منتج يطابق بحثك.
                </div>
            </div>
        </div>

        {{-- ==================== يمين: الطلب ==================== --}}
        <div class="lg:col-span-4 lg:sticky lg:top-2 lg:max-h-[calc(100vh-5rem)] flex flex-col gap-3">
            <div class="bg-white rounded-3xl shadow-lg shadow-gray-200/50 border border-gray-100 flex flex-col overflow-hidden max-h-[calc(100vh-5rem)]">

                {{-- رأس الطلب --}}
                <div class="p-3.5 border-b border-gray-100 bg-gray-50 flex justify-between items-center shrink-0">
                    <h3 class="text-sm font-black text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-basket-shopping text-blue-500"></i>
                        الطلب الحالي
                    </h3>
                    <div class="flex items-center gap-2">
                        <span id="itemsCountBadge"
                            class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-xl">0 أصناف</span>
                        <button id="clearCartBtn" onclick="clearCart()" title="مسح الطلب"
                            class="hidden w-7 h-7 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                    </div>
                </div>

                {{-- نوع الطلب --}}
                <div class="p-3 border-b border-gray-100 shrink-0">
                    <div class="grid grid-cols-3 gap-1.5 bg-gray-100 p-1 rounded-xl">
                        <button type="button" onclick="setOrderType('takeaway')" id="type_takeaway"
                            class="order-type-btn bg-white shadow-sm text-blue-600 font-bold text-xs py-2 rounded-lg transition-all">
                            🥤 تيك أواي
                        </button>
                        <button type="button" onclick="setOrderType('dine_in')" id="type_dine_in"
                            class="order-type-btn text-gray-500 hover:text-gray-700 font-bold text-xs py-2 rounded-lg transition-all">
                            🪑 صالة
                        </button>
                        <button type="button" onclick="setOrderType('delivery')" id="type_delivery"
                            class="order-type-btn text-gray-500 hover:text-gray-700 font-bold text-xs py-2 rounded-lg transition-all">
                            🛵 ديليفري
                        </button>
                    </div>
                </div>

                {{-- الحقول الديناميكية --}}
                <div id="dynamicFields" class="px-3 pt-2.5 pb-1 shrink-0 space-y-2 hidden">

                    {{-- صالة: اختيار الطاولة --}}
                    <div id="field_dine_in" class="hidden">
                        <p class="text-xs font-bold text-gray-600 mb-1.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-chair text-cafePrimary"></i>
                            اختر الطاولة <span class="text-red-500">*</span>
                        </p>
                        <div id="tablesGrid" class="grid grid-cols-3 gap-1.5 max-h-36 overflow-y-auto">
                            {{-- يُملأ عبر JavaScript من البيانات المُمررة من الـ Controller --}}
                        </div>
                        <p id="tableError" class="hidden text-red-500 text-xs mt-1 flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            يجب اختيار طاولة للصالة
                        </p>
                        <p id="noTablesMsg" class="hidden text-xs text-gray-400 text-center py-2">
                            لا توجد طاولات نشطة. تواصل مع الإدارة.
                        </p>
                    </div>

                    {{-- ديليفري --}}
                    <div id="field_delivery" class="hidden space-y-2">
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid fa-phone text-xs"></i>
                            </span>
                            <input type="text" id="customer_phone"
                                class="w-full bg-white pr-8 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-none text-sm"
                                placeholder="رقم هاتف العميل">
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid fa-location-dot text-xs"></i>
                            </span>
                            <input type="text" id="delivery_address"
                                class="w-full bg-white pr-8 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-none text-sm"
                                placeholder="عنوان التوصيل">
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid fa-motorcycle text-xs"></i>
                            </span>
                            <input type="text" id="delivery_person"
                                class="w-full bg-white pr-8 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 outline-none text-sm"
                                placeholder="اسم المندوب (اختياري)">
                        </div>
                    </div>
                </div>

                {{-- ملاحظات الطلب --}}
                <div class="px-3 pb-2 shrink-0">
                    <input type="text" id="orderNotes"
                        class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-2 text-xs text-gray-600 focus:border-blue-300 outline-none transition placeholder-gray-400"
                        placeholder="💬 ملاحظة على الطلب (اختياري)">
                </div>

                {{-- السلة --}}
                <div class="flex-1 overflow-y-auto p-3 space-y-2 min-h-[100px]" id="invoiceItems">
                    {{-- تُضاف المنتجات هنا --}}
                </div>

                {{-- الإجماليات والدفع --}}
                <div class="p-3 bg-white border-t border-gray-100 shrink-0 space-y-3">

                    {{-- خصم (اختياري) --}}
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-bold text-gray-600 shrink-0">خصم:</label>
                        <div class="relative flex-1">
                            <input type="number" id="discountInput" min="0" step="0.5" value="0"
                                oninput="updateTotals()"
                                class="w-full bg-gray-50 border border-gray-100 rounded-xl px-3 py-1.5 text-sm text-gray-700 focus:border-blue-300 outline-none transition text-center font-bold">
                        </div>
                        <span class="text-xs text-gray-500 shrink-0">ج.م</span>
                    </div>

                    {{-- طريقة الدفع --}}
                    <div id="paymentMethodSection">
                        <p class="text-xs font-bold text-gray-600 mb-1.5">طريقة الدفع:</p>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button type="button" onclick="setPaymentMethod('cash')" id="pm_cash"
                                class="payment-method-btn bg-emerald-50 border border-emerald-200 text-emerald-700 font-bold text-xs py-2 rounded-xl transition-all">
                                💰 كاش
                            </button>
                            <button type="button" onclick="setPaymentMethod('InstaPay')" id="pm_InstaPay"
                                class="payment-method-btn bg-gray-50 border border-gray-200 text-gray-600 hover:bg-gray-100 font-bold text-xs py-2 rounded-xl transition-all">
                                📱 إنستا
                            </button>
                            <button type="button" onclick="setPaymentMethod('card')" id="pm_card"
                                class="payment-method-btn bg-gray-50 border border-gray-200 text-gray-600 hover:bg-gray-100 font-bold text-xs py-2 rounded-xl transition-all">
                                💳 كارد
                            </button>
                        </div>
                    </div>

                    {{-- الإجمالي --}}
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-3 space-y-1.5">
                        <div class="flex justify-between items-center text-xs text-gray-500">
                            <span>الإجمالي قبل الخصم:</span>
                            <span id="subtotalDisplay" class="font-bold text-gray-700">0.00 ج</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-gray-500">
                            <span>الخصم:</span>
                            <span id="discountDisplay" class="font-bold text-red-500">- 0.00 ج</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-200 pt-1.5 mt-1">
                            <span class="text-sm font-black text-gray-800">الإجمالي:</span>
                            <span id="grandTotal" class="text-2xl font-black text-emerald-600">0.00 ج</span>
                        </div>
                    </div>

                    {{-- زر الإتمام --}}
                    <button type="button" onclick="submitOrder()" id="submitBtn" disabled
                        class="w-full bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-white font-black py-3.5 rounded-2xl shadow-lg shadow-emerald-500/25 transition-all flex items-center justify-center gap-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fa-solid fa-cash-register"></i>
                        إتمام الطلب
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // ========== البيانات الأولية من الباك إند ==========
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    // الطاولات تُحمَّل مبدئياً من الـ Controller وتُحدَّث عبر AJAX عند اختيار "صالة"
    let tablesData = @json($activeTables);

    // ========== حالة الـ POS ==========
    let cart = {};
    let currentCategoryId = 'all';
    let currentOrderType = 'takeaway';
    let selectedTableId = null;
    let currentPaymentMethod = 'cash';

    // ========== البحث مع Debounce ==========
    let searchDebounceTimer = null;
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');

    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            filterItems();
            clearSearchBtn.classList.toggle('hidden', searchInput.value.trim() === '');
        }, 180);
    });

    function clearSearch() {
        searchInput.value = '';
        clearSearchBtn.classList.add('hidden');
        filterItems();
        searchInput.focus();
    }

    function filterItems() {
        const query = searchInput.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.menu-item-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const nameMatch = card.dataset.name.toLowerCase().includes(query);
            const catMatch  = currentCategoryId === 'all' || card.dataset.category === currentCategoryId;
            const show = nameMatch && catMatch;
            card.classList.toggle('hidden', !show);
            if (show) visibleCount++;
        });

        document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
    }

    // ========== الفئات ==========
    function selectCategory(id) {
        currentCategoryId = String(id);
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('bg-blue-600', 'text-white', 'shadow-sm', 'shadow-blue-500/20');
            tab.classList.add('bg-gray-50', 'text-gray-600', 'border-gray-100');
        });
        const active = document.getElementById(`tab-${id}`);
        if (active) {
            active.classList.remove('bg-gray-50', 'text-gray-600', 'border-gray-100');
            active.classList.add('bg-blue-600', 'text-white', 'shadow-sm', 'shadow-blue-500/20');
        }
        filterItems();
    }

    // ========== نوع الطلب ==========
    function setOrderType(type) {
        currentOrderType = type;
        selectedTableId = null;

        document.querySelectorAll('.order-type-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
            btn.classList.add('text-gray-500');
        });
        const activeBtn = document.getElementById(`type_${type}`);
        activeBtn.classList.remove('text-gray-500');
        activeBtn.classList.add('bg-white', 'shadow-sm', 'text-blue-600');

        const dynFields = document.getElementById('dynamicFields');
        const dineInFields = document.getElementById('field_dine_in');
        const deliveryFields = document.getElementById('field_delivery');

        dynFields.classList.add('hidden');
        dineInFields.classList.add('hidden');
        deliveryFields.classList.add('hidden');

        if (type === 'dine_in') {
            dynFields.classList.remove('hidden');
            dineInFields.classList.remove('hidden');
            renderTablesGrid();
        } else if (type === 'delivery') {
            dynFields.classList.remove('hidden');
            deliveryFields.classList.remove('hidden');
        }

        // طريقة الدفع: للصالة (dine_in) نخفيها لأن الدفع يتم لاحقاً
        const pmSection = document.getElementById('paymentMethodSection');
        if (type === 'dine_in') {
            pmSection.classList.add('hidden');
        } else {
            pmSection.classList.remove('hidden');
        }

        updateSubmitButton();
    }

    // ========== طريقة الدفع ==========
    function setPaymentMethod(method) {
        currentPaymentMethod = method;
        document.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.classList.remove('bg-emerald-50', 'border-emerald-200', 'text-emerald-700',
                                 'bg-blue-50', 'border-blue-200', 'text-blue-700');
            btn.classList.add('bg-gray-50', 'border-gray-200', 'text-gray-600');
        });
        const active = document.getElementById(`pm_${method}`);
        active.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-600');
        active.classList.add('bg-emerald-50', 'border-emerald-200', 'text-emerald-700');
    }
    // تهيئة الضغطة الافتراضية
    setPaymentMethod('cash');

    // ========== الطاولات في الـ POS ==========
    function renderTablesGrid() {
        const grid = document.getElementById('tablesGrid');
        const noTablesMsg = document.getElementById('noTablesMsg');

        grid.innerHTML = '';

        if (!tablesData || tablesData.length === 0) {
            noTablesMsg.classList.remove('hidden');
            return;
        }
        noTablesMsg.classList.add('hidden');

        tablesData.forEach(table => {
            const isOccupied = table.is_occupied;
            const isSelected = selectedTableId === table.id;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.tableId = table.id;
            btn.className = [
                'table-btn rounded-xl py-2 px-1 text-center transition-all border text-xs font-bold',
                isSelected
                    ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/30'
                    : isOccupied
                        ? 'bg-orange-50 text-orange-500 border-orange-200 cursor-not-allowed'
                        : 'bg-white text-gray-700 border-gray-200 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700',
            ].join(' ');

            btn.disabled = isOccupied;
            btn.title = isOccupied ? 'مشغولة' : table.name;
            btn.innerHTML = `
                <div class="font-black text-xs leading-tight">${table.name}</div>
                ${table.capacity ? `<div class="text-[9px] opacity-60 mt-0.5">${table.capacity}👤</div>` : ''}
                ${isOccupied ? '<div class="text-[9px] text-orange-400 mt-0.5">مشغولة</div>' : ''}
            `;

            btn.onclick = () => selectTable(table.id);
            grid.appendChild(btn);
        });
    }

    function selectTable(id) {
        selectedTableId = id;
        document.getElementById('tableError').classList.add('hidden');
        // نعيد رسم الـ grid عشان نحدّث الـ selected state
        renderTablesGrid();
    }

    // تحديث بيانات الطاولات عبر AJAX (عند الحاجة لتحديث حالة الشغل)
    function refreshTables() {
        fetch('{{ route('pos.tables') }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(r => r.json())
        .then(data => {
            tablesData = data.data;
            if (currentOrderType === 'dine_in') renderTablesGrid();
        })
        .catch(() => {}); // صامت – نبقى بالبيانات القديمة
    }

    // ========== السلة ==========
    function addItem(id, name, price) {
        id = String(id);
        if (cart[id]) {
            cart[id].quantity += 1;
        } else {
            cart[id] = { id, name, price: parseFloat(price), quantity: 1 };
        }
        renderCart();
        updateBadge(id);
        if (navigator.vibrate) navigator.vibrate(30);
    }

    function updateQuantity(id, delta, manualValue = null) {
        if (!cart[id]) return;
        if (manualValue !== null) {
            const val = parseInt(manualValue) || 0;
            cart[id].quantity = val;
        } else {
            cart[id].quantity += delta;
        }
        if (cart[id].quantity <= 0) {
            removeItem(id);
            return;
        }
        renderCart();
        updateBadge(id);
    }

    function removeItem(id) {
        delete cart[id];
        renderCart();
        updateBadge(id);
    }

    function clearCart() {
        if (!confirm('هل تريد مسح الطلب بالكامل؟')) return;
        cart = {};
        renderCart();
        document.querySelectorAll('[id^="badge-"]').forEach(b => b.classList.add('hidden'));
    }

    function updateBadge(id) {
        const badge = document.getElementById(`badge-${id}`);
        if (!badge) return;
        const qty = cart[id]?.quantity || 0;
        if (qty > 0) {
            badge.textContent = qty;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function renderCart() {
        const container = document.getElementById('invoiceItems');
        container.innerHTML = '';
        let subtotal = 0;
        let totalItems = 0;
        const hasItems = Object.keys(cart).length > 0;

        if (!hasItems) {
            container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-gray-400 py-8 space-y-2 opacity-60">
                    <i class="fa-solid fa-cart-arrow-down text-3xl"></i>
                    <p class="text-xs font-medium">اضغط على أي منتج لإضافته</p>
                </div>`;
        } else {
            for (const key in cart) {
                const item = cart[key];
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                totalItems += item.quantity;

                container.innerHTML += `
                <div id="cart-item-${item.id}" class="bg-white border border-gray-100 rounded-2xl p-2.5 shadow-sm">
                    <div class="flex justify-between items-start mb-1.5">
                        <span class="font-bold text-gray-800 text-xs leading-tight flex-1 pl-1">${item.name}</span>
                        <button onclick="removeItem('${item.id}')"
                            class="w-6 h-6 rounded-lg flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 transition shrink-0">
                            <i class="fa-solid fa-xmark text-[10px]"></i>
                        </button>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-[10px] text-gray-400">${item.price.toFixed(2)} ج × ${item.quantity}</div>
                            <div class="text-sm font-black text-blue-600">${itemTotal.toFixed(2)} ج</div>
                        </div>
                        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl">
                            <button onclick="updateQuantity('${item.id}', -1)"
                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-xl transition">
                                <i class="fa-solid fa-minus text-[9px]"></i>
                            </button>
                            <input type="number" value="${item.quantity}" min="1"
                                onchange="updateQuantity('${item.id}', 0, this.value)"
                                onclick="this.select()"
                                class="w-8 text-center bg-transparent font-black text-gray-800 outline-none border-none text-xs appearance-none">
                            <button onclick="updateQuantity('${item.id}', 1)"
                                class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-xl transition">
                                <i class="fa-solid fa-plus text-[9px]"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
            }
        }

        document.getElementById('itemsCountBadge').textContent = `${totalItems} أصناف`;
        document.getElementById('clearCartBtn').classList.toggle('hidden', !hasItems);
        updateTotalsDisplay(subtotal);
        updateSubmitButton();
    }

    function updateTotals() {
        // نحسب الـ subtotal من السلة الحالية
        let subtotal = 0;
        for (const key in cart) subtotal += cart[key].price * cart[key].quantity;
        updateTotalsDisplay(subtotal);
    }

    function updateTotalsDisplay(subtotal) {
        const discount = Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0);
        const total = Math.max(0, subtotal - discount);
        document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2) + ' ج';
        document.getElementById('discountDisplay').textContent = '- ' + discount.toFixed(2) + ' ج';
        document.getElementById('grandTotal').textContent = total.toFixed(2) + ' ج';
    }

    function updateSubmitButton() {
        const hasItems = Object.keys(cart).length > 0;
        const btn = document.getElementById('submitBtn');
        btn.disabled = !hasItems;
    }

    renderCart();

    // ========== إرسال الطلب ==========
    function submitOrder() {
        if (Object.keys(cart).length === 0) return;

        // تحقق من الطاولة للصالة
        if (currentOrderType === 'dine_in' && !selectedTableId) {
            document.getElementById('tableError').classList.remove('hidden');
            document.getElementById('field_dine_in').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return;
        }

        const subtotal = Object.values(cart).reduce((s, i) => s + i.price * i.quantity, 0);
        const discount = Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0);
        if (discount > subtotal) {
            showError('الخصم لا يمكن أن يتجاوز قيمة الطلب.');
            return;
        }

        const payload = {
            type: currentOrderType,
            notes: document.getElementById('orderNotes').value.trim() || null,
            discount: discount || null,
            items: Object.values(cart).map(item => ({
                menu_id: item.id,
                quantity: item.quantity,
            })),
        };

        if (currentOrderType === 'dine_in') {
            payload.table_id = selectedTableId;
        } else if (currentOrderType === 'delivery') {
            payload.phone           = document.getElementById('customer_phone').value.trim() || null;
            payload.delivery_address = document.getElementById('delivery_address').value.trim() || null;
            payload.delivery_person = document.getElementById('delivery_person').value.trim() || null;
            payload.payment_method  = currentPaymentMethod;
        } else {
            // takeaway
            payload.payment_method = currentPaymentMethod;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin ml-1"></i> جاري الحفظ...';

        fetch('{{ route('orders.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN,
            },
            body: JSON.stringify(payload),
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw data;
            return data;
        })
        .then(data => {
            // عرض نافذة النجاح
            document.getElementById('successOrderNumber').textContent = 'رقم الطلب: ' + (data.data?.order_number || '');
            showSuccessModal();

            // تفريغ كل شيء
            resetPOS();

            // تحديث حالة الطاولات بعد إنشاء الأوردر
            refreshTables();
        })
        .catch(err => {
            const msg = err?.message || err?.errors
                ? (Object.values(err.errors || {})[0]?.[0] || err.message)
                : 'حدث خطأ أثناء حفظ الطلب.';
            showError(msg);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cash-register ml-1"></i> إتمام الطلب';
            updateSubmitButton();
        });
    }

    function resetPOS() {
        cart = {};
        selectedTableId = null;
        renderCart();
        document.querySelectorAll('[id^="badge-"]').forEach(b => b.classList.add('hidden'));
        document.getElementById('orderNotes').value = '';
        document.getElementById('discountInput').value = '0';
        document.getElementById('customer_phone').value = '';
        document.getElementById('delivery_address').value = '';
        document.getElementById('delivery_person').value = '';
        document.getElementById('searchInput').value = '';
        clearSearchBtn.classList.add('hidden');
        selectCategory('all');
        setOrderType('takeaway');
        setPaymentMethod('cash');
    }

    // ========== المودال والـ Toasts ==========
    function showSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.classList.remove('hidden');
        setTimeout(() => hideSuccessModal(), 3000);
    }
    function hideSuccessModal() {
        document.getElementById('successModal').classList.add('hidden');
    }

    let errorTimer = null;
    function showError(msg) {
        const toast = document.getElementById('errorToast');
        document.getElementById('errorToastMsg').textContent = msg;
        toast.classList.remove('hidden');
        clearTimeout(errorTimer);
        errorTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    // ========== اختصارات الكيبورد ==========
    document.addEventListener('keydown', function(e) {
        const active = document.activeElement;
        const isTyping = active.tagName === 'INPUT' || active.tagName === 'TEXTAREA';

        // Escape: مسح البحث أو إغلاق المودال
        if (e.key === 'Escape') {
            if (!document.getElementById('successModal').classList.contains('hidden')) {
                hideSuccessModal();
                return;
            }
            if (searchInput.value) {
                clearSearch();
                return;
            }
        }

        // / أو F2: التركيز على البحث
        if ((e.key === '/' || e.key === 'F2') && !isTyping) {
            e.preventDefault();
            searchInput.focus();
            return;
        }

        // أي حرف عربي أو إنجليزي يُكتب: تركيز البحث وإضافته
        if (!isTyping && !e.ctrlKey && !e.altKey && !e.metaKey && e.key.length === 1) {
            searchInput.focus();
            // لا نُضيف الحرف يدوياً – المتصفح سيتولى ذلك تلقائياً
        }
    });
</script>
@endpush
