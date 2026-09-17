@extends('layouts.app')

@section('page_title', 'شاشة الكاشير السريعة - POS')

@section('content')
    <div class="container mx-auto px-4 relative pb-20 lg:pb-8" dir="rtl">

        <!-- نافذة النجاح -->
        <div id="successModal"
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
            <div
                class="bg-white rounded-3xl p-6 max-w-sm w-full mx-4 text-center shadow-2xl transform scale-95 transition-transform duration-300 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-emerald-50/50 to-transparent pointer-events-none"></div>
                <div
                    class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-3 text-emerald-500 text-2xl animate-bounce">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h4 class="text-lg font-bold text-gray-800 mb-1">تم إتمام الطلب بنجاح!</h4>
                <p id="successOrderNumber" class="text-sm font-bold text-blue-600 mb-2"></p>
                <p class="text-xs text-gray-500">تم إرسال الأوردر للمطبخ وتحديث المخزون.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- القسم الأيمن: المنتجات والبحث -->
            <div class="lg:col-span-8 space-y-4">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-4 sticky top-4 z-10">
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-base"></i>
                        </span>
                        <input type="text" id="searchInput" oninput="filterItems()"
                            class="w-full bg-gray-50 text-gray-800 pr-11 pl-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none font-medium text-sm transition-all shadow-inner"
                            placeholder="ابحث عن مشروب أو منتج بسرعة...">
                    </div>

                    <div class="flex flex-wrap gap-2 items-center border-t border-gray-50 pt-3">
                        <button type="button" onclick="selectCategory('all')" id="tab-all"
                            class="category-tab bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-md shadow-blue-500/20">
                            📁 الكل ({{ $menus->count() }})
                        </button>

                        @php
                            $uniqueCategories = $menus->pluck('category')->unique('id')->filter();
                        @endphp

                        @foreach ($uniqueCategories as $category)
                            <button type="button" onclick="selectCategory('{{ $category->id }}')"
                                id="tab-{{ $category->id }}"
                                class="category-tab bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100 px-4 py-2 rounded-xl text-xs font-bold transition-all">
                                📦 {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 min-h-[50vh]">
                    <div id="menuGrid" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach ($menus as $menu)
                            <button type="button"
                                onclick="addItem('{{ $menu->id }}', '{{ $menu->name }}', {{ $menu->price }})"
                                data-id="{{ $menu->id }}" data-name="{{ $menu->name }}"
                                data-category="{{ $menu->category_id }}"
                                class="menu-item-card bg-white hover:bg-blue-50/30 border border-gray-100 hover:border-blue-300 rounded-2xl flex flex-col overflow-hidden group transition-all duration-200 active:scale-95 shadow-sm hover:shadow-md h-40 relative">

                                @if ($menu->image)
                                    <div class="w-full h-20 bg-gray-100 overflow-hidden relative shrink-0">
                                        <img src="{{ asset('images/products/' . $menu->image) }}" alt="{{ $menu->name }}"
                                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                                    </div>
                                    <div
                                        class="p-3 flex-1 flex flex-col justify-between items-start w-full text-right bg-gradient-to-b from-white to-gray-50/50">
                                        <span
                                            class="font-bold text-gray-800 text-xs line-clamp-2 w-full group-hover:text-blue-600 transition-colors">{{ $menu->name }}</span>
                                        <span
                                            class="text-blue-600 font-black text-xs bg-blue-50 px-2 py-1 rounded-lg">{{ number_format($menu->price, 2) }}
                                            ج.م</span>
                                    </div>
                                @else
                                    <div
                                        class="p-3 flex-1 flex flex-col justify-center items-center w-full text-center space-y-2 bg-gray-50/40 group-hover:bg-blue-50/20 transition-colors">
                                        <span
                                            class="font-bold text-gray-800 text-sm line-clamp-3 w-full group-hover:text-blue-700 transition-colors">{{ $menu->name }}</span>
                                        <span
                                            class="text-blue-600 font-black text-xs bg-white border border-blue-100 px-2.5 py-1 rounded-xl shadow-sm">{{ number_format($menu->price, 2) }}
                                            ج.م</span>
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    <div id="noResults"
                        class="hidden text-center py-16 text-gray-400 font-medium text-sm flex-col items-center gap-3">
                        <i class="fa-solid fa-box-open text-4xl text-gray-200"></i>
                        <span>لم نجد أي منتج يطابق بحثك الحالي..</span>
                    </div>
                </div>
            </div>

            <!-- القسم الأيسر: تفاصيل الطلب والحساب -->
            <div class="lg:col-span-4 lg:sticky lg:top-4 lg:h-[calc(100vh-2rem)] flex flex-col gap-4">
                <div
                    class="bg-white rounded-3xl shadow-lg shadow-gray-200/50 border border-gray-100 flex flex-col h-full overflow-hidden">

                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center shrink-0">
                        <h3 class="text-base font-black text-gray-800 flex items-center gap-2">
                            <i class="fa-solid fa-basket-shopping text-blue-500"></i> الطلب الحالي
                        </h3>
                        <span id="itemsCountBadge"
                            class="bg-blue-100 text-blue-700 text-xs font-bold px-2.5 py-1 rounded-xl">0 أصناف</span>
                    </div>

                    <!-- نوع الطلب -->
                    <div class="p-3 border-b border-gray-100 shrink-0">
                        <div class="grid grid-cols-3 gap-2 bg-gray-100 p-1 rounded-xl">
                            <button type="button" onclick="setOrderType('takeaway')" id="type_takeaway"
                                class="order-type-btn bg-white shadow-sm text-blue-600 font-bold text-xs py-2 rounded-lg transition-all">تيك
                                أواي</button>
                            <button type="button" onclick="setOrderType('dine_in')" id="type_dine_in"
                                class="order-type-btn text-gray-500 hover:text-gray-700 font-bold text-xs py-2 rounded-lg transition-all">صالة</button>
                            <button type="button" onclick="setOrderType('delivery')" id="type_delivery"
                                class="order-type-btn text-gray-500 hover:text-gray-700 font-bold text-xs py-2 rounded-lg transition-all">ديليفري</button>
                        </div>
                    </div>

                    <!-- الحقول الديناميكية حسب نوع الطلب -->
                    <div id="dynamicFields" class="px-4 pt-3 pb-1 shrink-0 space-y-3 bg-blue-50/30 hidden">
                        <!-- حقل الصالة -->
                        <div id="field_dine_in" class="hidden">
                            <div class="relative">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"><i
                                        class="fa-solid fa-utensils"></i></span>
                                <input type="number" id="table_number" min="1"
                                    class="w-full bg-white pr-9 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-100 outline-none text-sm"
                                    placeholder="رقم الترابيزة (مطلوب للصالة)">
                            </div>
                        </div>

                        <!-- حقول الديليفري -->
                        <div id="field_delivery" class="hidden space-y-2">
                            <div class="relative">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"><i
                                        class="fa-solid fa-phone"></i></span>
                                <input type="text" id="customer_phone"
                                    class="w-full bg-white pr-9 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-100 outline-none text-sm"
                                    placeholder="رقم هاتف العميل">
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"><i
                                        class="fa-solid fa-map-location-dot"></i></span>
                                <input type="text" id="delivery_address"
                                    class="w-full bg-white pr-9 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-100 outline-none text-sm"
                                    placeholder="عنوان التوصيل بالكامل">
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400"><i
                                        class="fa-solid fa-motorcycle"></i></span>
                                <input type="text" id="delivery_person"
                                    class="w-full bg-white pr-9 pl-3 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-100 outline-none text-sm"
                                    placeholder="اسم المندوب (اختياري)">
                            </div>
                        </div>
                    </div>

                    <!-- السلة -->
                    <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50/30 min-h-[150px]" id="invoiceItems">
                        <!-- يتم إضافة المنتجات هنا عبر الجافاسكربت -->
                    </div>

                    <!-- الإجماليات وزر الدفع -->
                    <div class="p-4 bg-white border-t border-gray-100 shrink-0 space-y-4">
                        <div class="space-y-2 bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-black text-gray-800">الإجمالي المطلوب:</span>
                                <span id="grandTotal" class="text-2xl font-black text-emerald-600 drop-shadow-sm">0.00
                                    ج.م</span>
                            </div>
                        </div>

                        <button type="button" onclick="submitOrder()" id="submitBtn" disabled
                            class="w-full bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-500/30 transition-all text-center flex items-center justify-center gap-2 text-base group disabled:opacity-50 disabled:cursor-not-allowed">
                            <i
                                class="fa-solid fa-cash-register group-hover:-translate-y-1 transition-transform duration-300"></i>
                            إتمام الطلب وطباعة الفاتورة
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let cart = {};
        let currentCategoryId = 'all';
        let currentOrderType = 'takeaway'; // الافتراضي تيك أواي

        // إعداد توكن الـ CSRF للـ Fetch API
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
            '{{ csrf_token() }}';

        // ------------------ واجهة المستخدم والبحث ------------------
        function filterItems() {
            const searchInput = document.getElementById('searchInput').value.toLowerCase().trim();
            const items = document.querySelectorAll('.menu-item-card');
            let visibleCount = 0;

            items.forEach(item => {
                const name = item.getAttribute('data-name').toLowerCase();
                const category = item.getAttribute('data-category');
                const matchesCategory = (currentCategoryId === 'all' || category === currentCategoryId);
                const matchesSearch = (searchInput === '' || name.includes(searchInput));

                if (matchesCategory && matchesSearch) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            document.getElementById('noResults').classList.toggle('hidden', visibleCount > 0);
        }

        function selectCategory(categoryId) {
            currentCategoryId = categoryId;
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.remove('bg-blue-600', 'text-white', 'shadow-md', 'shadow-blue-500/20');
                tab.classList.add('bg-gray-50', 'text-gray-600', 'border-gray-100');
            });
            const activeTab = document.getElementById(`tab-${categoryId}`);
            if (activeTab) {
                activeTab.classList.remove('bg-gray-50', 'text-gray-600', 'border-gray-100');
                activeTab.classList.add('bg-blue-600', 'text-white', 'shadow-md', 'shadow-blue-500/20');
            }
            filterItems();
        }

        // ------------------ إدارة حالة الطلب (Order Type) ------------------
        function setOrderType(type) {
            currentOrderType = type;

            // تصفير الأزرار
            document.querySelectorAll('.order-type-btn').forEach(btn => {
                btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-600');
                btn.classList.add('text-gray-500', 'hover:text-gray-700');
            });

            // تفعيل الزر المختار
            const activeBtn = document.getElementById(`type_${type}`);
            activeBtn.classList.remove('text-gray-500', 'hover:text-gray-700');
            activeBtn.classList.add('bg-white', 'shadow-sm', 'text-blue-600');

            // التحكم في إظهار وإخفاء الحقول الديناميكية
            const dynamicContainer = document.getElementById('dynamicFields');
            const dineInFields = document.getElementById('field_dine_in');
            const deliveryFields = document.getElementById('field_delivery');

            dineInFields.classList.add('hidden');
            deliveryFields.classList.add('hidden');
            dynamicContainer.classList.add('hidden');

            if (type === 'dine_in') {
                dynamicContainer.classList.remove('hidden');
                dineInFields.classList.remove('hidden');
            } else if (type === 'delivery') {
                dynamicContainer.classList.remove('hidden');
                deliveryFields.classList.remove('hidden');
            }
        }

        // ------------------ إدارة السلة (Cart) ------------------
        function addItem(id, name, price) {
            if (cart[id]) {
                cart[id].quantity += 1;
            } else {
                cart[id] = {
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1
                };
            }
            renderCart();
            if (navigator.vibrate) navigator.vibrate(50);
        }

        function updateQuantity(id, changeType, manualValue = null) {
            if (!cart[id]) return;
            if (manualValue !== null) {
                cart[id].quantity = parseInt(manualValue) || 1;
            } else {
                cart[id].quantity += changeType;
            }

            if (cart[id].quantity <= 0) {
                removeItem(id);
            } else {
                renderCart();
            }
        }

        function removeItem(id) {
            const el = document.getElementById(`cart-item-${id}`);
            if (el) {
                el.classList.add('opacity-0', '-translate-x-4');
                setTimeout(() => {
                    delete cart[id];
                    renderCart();
                }, 200);
            }
        }

        function renderCart() {
            const container = document.getElementById('invoiceItems');
            container.innerHTML = '';
            let subtotal = 0;
            let totalItems = 0;

            for (let key in cart) {
                const item = cart[key];
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                totalItems += item.quantity;

                container.innerHTML += `
                <div id="cart-item-${item.id}" class="bg-white p-3 rounded-2xl border border-gray-100 shadow-sm flex flex-col gap-2 transition-all duration-200">
                    <div class="flex justify-between items-start">
                        <span class="font-bold text-gray-800 text-sm leading-tight pr-1">${item.name}</span>
                        <button type="button" onclick="removeItem('${item.id}')" class="text-gray-300 hover:text-red-500 hover:bg-red-50 w-7 h-7 rounded-lg flex items-center justify-center transition-colors shrink-0">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-400 font-semibold">${item.price.toFixed(2)} ج</span>
                            <span class="text-sm font-black text-blue-600">${itemTotal.toFixed(2)} ج</span>
                        </div>
                        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-xl p-1">
                            <button type="button" onclick="updateQuantity('${item.id}', -1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-lg transition-all"><i class="fa-solid fa-minus text-[10px]"></i></button>
                            <input type="number" value="${item.quantity}" min="1" onchange="updateQuantity('${item.id}', null, this.value)" class="w-8 text-center bg-transparent font-black text-gray-800 outline-none border-none p-0 text-sm appearance-none">
                            <button type="button" onclick="updateQuantity('${item.id}', 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-lg transition-all"><i class="fa-solid fa-plus text-[10px]"></i></button>
                        </div>
                    </div>
                </div>`;
            }

            if (Object.keys(cart).length === 0) {
                container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-gray-400 py-10 space-y-3 opacity-60">
                    <i class="fa-solid fa-cart-arrow-down text-4xl"></i>
                    <p class="text-sm font-medium">السلة فارغة، ابدأ بإضافة الأصناف</p>
                </div>`;
            }

            document.getElementById('itemsCountBadge').innerText = `${totalItems} عناصر`;
            document.getElementById('grandTotal').innerText = subtotal.toFixed(2) + ' ج.م';
            document.getElementById('submitBtn').disabled = Object.keys(cart).length === 0;
        }

        renderCart();

        // ------------------ إرسال الطلب للباك إند (Backend Integration) ------------------
        function submitOrder() {
            if (Object.keys(cart).length === 0) return;

            // تجهيز البيانات بناءً على OrderRequest في الباك إند
            const payload = {
                type: currentOrderType,
                items: Object.values(cart).map(item => ({
                    menu_id: item.id,
                    quantity: item.quantity
                }))
            };

            // إضافة الحقول الإضافية بناءً على الحالة
            if (currentOrderType === 'dine_in') {
                const tableNum = document.getElementById('table_number').value;
                if (!tableNum) {
                    alert('يرجى إدخال رقم الترابيزة');
                    return;
                }
                payload.table_number = parseInt(tableNum);
            } else if (currentOrderType === 'delivery') {
                payload.phone = document.getElementById('customer_phone').value;
                payload.delivery_address = document.getElementById('delivery_address').value;
                payload.delivery_person = document.getElementById('delivery_person').value;
            }

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري حفظ الأوردر...';

            fetch('{{ route('orders.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(payload)
                })
                .then(response => {
                    if (!response.ok) throw response;
                    return response.json();
                })
                .then(data => {
                    // إظهار نافذة النجاح مع رقم الأوردر القادم من الباك إند
                    document.getElementById('successOrderNumber').innerText = 'رقم الطلب: ' + (data.data
                        ?.order_number || '');
                    showSuccessModal();

                    // تفريغ السلة والحقول
                    cart = {};
                    renderCart();
                    document.getElementById('table_number').value = '';
                    document.getElementById('customer_phone').value = '';
                    document.getElementById('delivery_address').value = '';
                    document.getElementById('delivery_person').value = '';
                    document.getElementById('searchInput').value = '';
                    selectCategory('all');
                    setOrderType('takeaway'); // العودة للحالة الافتراضية
                })
                .catch(async error => {
                    let errorMsg = 'حدث خطأ أثناء حفظ الأوردر.';
                    if (error.json) {
                        const errData = await error.json();
                        errorMsg = errData.message || errorMsg;
                    }
                    alert(errorMsg);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML =
                        '<i class="fa-solid fa-cash-register group-hover:-translate-y-1 transition-transform duration-300"></i> إتمام الطلب وطباعة الفاتورة';
                });
        }

        // إظهار وإخفاء نافذة النجاح
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('opacity-100', 'scale-100'), 10);
            setTimeout(() => {
                modal.classList.remove('opacity-100', 'scale-100');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }, 2000);
        }

        // اختصارات الكيبورد السريعة للبحث
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.ctrlKey || e.altKey || e.metaKey) return;
            const active = document.activeElement;
            if (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA') return;

            if (e.key.length === 1) {
                const search = document.getElementById('searchInput');
                search.focus();
                search.value += e.key;
                filterItems();
                e.preventDefault();
            }
        });
    </script>
@endpush
