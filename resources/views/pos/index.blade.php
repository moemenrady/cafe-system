@extends('layouts.app')

@section('page_title', 'شاشة البيع السريع - POS')

@section('content')
    <div class="container mx-auto px-4 relative pb-20 lg:pb-8" dir="rtl">

        <div id="successModal"
            class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
            <div
                class="bg-white rounded-3xl p-6 max-w-sm w-full mx-4 text-center shadow-2xl transform scale-95 transition-transform duration-300 relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-emerald-50/50 to-transparent pointer-events-none"></div>
                <div
                    class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-3 text-emerald-500 text-2xl animate-bounce">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h4 class="text-lg font-bold text-gray-800 mb-1">تم حفظ العملية!</h4>
                <p class="text-xs text-gray-500">تم تسجيل الفاتورة وتحديث كميات المخزن بنجاح.</p>
            </div>
        </div>

        <form id="posForm" action="{{ route('invoices.store') }}" method="POST"
            class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            @csrf
            <input type="hidden" name="payment_method" value="cash">

            <div class="lg:col-span-8 space-y-4">
                <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-4 sticky top-4 z-10">
                    <div class="relative">
                        <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                            <i class="fa-solid fa-magnifying-glass text-base"></i>
                        </span>
                        <input type="text" id="searchInput" oninput="filterItems()"
                            class="w-full bg-gray-50 text-gray-800 pr-11 pl-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none font-medium text-sm transition-all shadow-inner"
                            placeholder="ابحث عن أي منتج باسمه فوراً...">
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
                                class="menu-item-card bg-white hover:bg-blue-50/30 border border-gray-100 hover:border-blue-300 rounded-2xl flex flex-col overflow-hidden group transition-all duration-200 active:scale-95 shadow-sm hover:shadow-md h-40">

                                @if ($menu->image)
                                    <div class="w-full h-20 bg-gray-100 overflow-hidden relative shrink-0">
                                        <img src="{{ asset('storage/' . $menu->image) }}" alt="{{ $menu->name }}"
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

                    <div class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50/30" id="invoiceItems">
                    </div>

                    <div class="p-4 bg-white border-t border-gray-100 shrink-0 space-y-4">

                        <div>
                            <label class="text-xs font-bold text-gray-500 mb-1.5 block">قيمة الخصم الإضافي (ج.م)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-red-400">
                                    <i class="fa-solid fa-tags"></i>
                                </span>
                                <input type="number" name="discount" id="discountInput" value="0" min="0"
                                    step="0.5" oninput="renderCart()"
                                    class="w-full bg-red-50/30 text-red-600 font-bold pr-9 pl-4 py-2.5 rounded-xl border border-red-100 focus:border-red-400 focus:ring-2 focus:ring-red-100 outline-none text-sm transition-all"
                                    placeholder="أدخل قيمة الخصم...">
                            </div>
                        </div>

                        <div class="space-y-2 bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                            <div class="flex justify-between text-sm text-gray-500 font-medium">
                                <span>الإجمالي قبل الخصم:</span>
                                <span id="subtotal" class="font-bold text-gray-700">0.00 ج.م</span>
                            </div>
                            <div class="flex justify-between items-center border-t border-gray-200/60 pt-2 mt-2">
                                <span class="text-sm font-black text-gray-800">المطلوب سداده:</span>
                                <span id="grandTotal" class="text-2xl font-black text-emerald-600 drop-shadow-sm">0.00
                                    ج.م</span>
                            </div>
                        </div>

                        <button type="submit" id="submitBtn"
                            class="w-full bg-emerald-500 hover:bg-emerald-600 active:scale-[0.98] text-white font-black py-4 rounded-2xl shadow-lg shadow-emerald-500/30 transition-all text-center flex items-center justify-center gap-2 text-base group disabled:opacity-50 disabled:cursor-not-allowed">
                            <i
                                class="fa-solid fa-cash-register group-hover:-translate-y-1 transition-transform duration-300"></i>
                            تأكيد وطباعة الفاتورة
                        </button>
                    </div>
                </div>

            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        let cart = {};
        let currentCategoryId = 'all';

        // وظيفة البحث وفلترة الأقسام
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

        // اختيار القسم
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

        // إضافة منتج للسلة
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
            // تشغيل تأثير صوتي أو هابتيك (اختياري)
            if (navigator.vibrate) navigator.vibrate(50);
        }

        // تعديل الكمية (بواسطة الأزرار + و - أو الإدخال اليدوي)
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

        // حذف منتج
        function removeItem(id) {
            const el = document.getElementById(`cart-item-${id}`);
            if (el) {
                el.classList.add('opacity-0', '-translate-x-4');
                setTimeout(() => {
                    delete cart[id];
                    renderCart();
                }, 200); // إعطاء وقت للأنيميشن
            }
        }

        // تحديث وعرض السلة وملخص الحساب
        function renderCart() {
            const container = document.getElementById('invoiceItems');
            container.innerHTML = '';

            let subtotal = 0;
            let index = 0;
            let totalItems = 0;

            // جلب قيمة الخصم من الحقل
            const discountInput = document.getElementById('discountInput').value;
            const discount = parseFloat(discountInput) || 0;

            for (let key in cart) {
                const item = cart[key];
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                totalItems += item.quantity;

                // تصميم عصري ومدمج لعنصر السلة
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
                            <button type="button" onclick="updateQuantity('${item.id}', -1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-lg transition-all">
                                <i class="fa-solid fa-minus text-[10px]"></i>
                            </button>
                            
                            <input type="number" name="items[${index}][quantity]" value="${item.quantity}" min="1" 
                                   onchange="updateQuantity('${item.id}', null, this.value)"
                                   class="w-8 text-center bg-transparent font-black text-gray-800 outline-none border-none p-0 text-sm appearance-none">
                            <input type="hidden" name="items[${index}][menu_id]" value="${item.id}">
                            
                            <button type="button" onclick="updateQuantity('${item.id}', 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:bg-white hover:shadow-sm rounded-lg transition-all">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>`;
                index++;
            }

            if (Object.keys(cart).length === 0) {
                container.innerHTML = `
                <div class="h-full flex flex-col items-center justify-center text-gray-400 py-10 space-y-3 opacity-60">
                    <i class="fa-solid fa-cart-arrow-down text-4xl"></i>
                    <p class="text-sm font-medium">السلة فارغة، ابدأ بإضافة الأصناف</p>
                </div>`;
            }

            // حساب الإجمالي النهائي والتأكد من أنه لا يكون بالسالب
            const grandTotal = Math.max(0, subtotal - discount);

            // تحديث الواجهة
            document.getElementById('itemsCountBadge').innerText = `${totalItems} عناصر`;
            document.getElementById('subtotal').innerText = subtotal.toFixed(2) + ' ج.م';
            document.getElementById('grandTotal').innerText = grandTotal.toFixed(2) + ' ج.م';

            // تفعيل/تعطيل زر الإرسال بناءً على السلة
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = Object.keys(cart).length === 0;
        }

        renderCart();

        // إظهار نافذة النجاح
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('hidden');
            setTimeout(() => modal.classList.add('opacity-100', 'scale-100'), 10);
            setTimeout(() => closeSuccessModal(), 1500);
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.remove('opacity-100', 'scale-100');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // معالجة الإرسال
        document.getElementById('posForm').addEventListener('submit', function(e) {
            e.preventDefault();

            if (Object.keys(cart).length === 0) return;

            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري إصدار الفاتورة...';

            const formData = new FormData(this);

            fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessModal();
                        cart = {};
                        document.getElementById('discountInput').value = '0';
                        renderCart();
                        document.getElementById('searchInput').value = '';
                        selectCategory('all');
                    } else if (data.error) {
                        alert(data.error);
                    }
                })
                .catch(error => {
                    alert('حدث خطأ غير متوقع أثناء الحفظ.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-cash-register"></i> تأكيد وطباعة الفاتورة';
                });
        });
        document.addEventListener('keydown', function(e) {
            // تجاهل Enter
            if (e.key === 'Enter') return;

            // تجاهل Ctrl و Alt و Meta
            if (e.ctrlKey || e.altKey || e.metaKey) return;

            // لو المستخدم بيكتب بالفعل في input أو textarea سيبه
            const active = document.activeElement;
            if (
                active.tagName === 'INPUT' ||
                active.tagName === 'TEXTAREA' ||
                active.isContentEditable
            ) {
                return;
            }

            // لو المفتاح حرف أو رقم أو رمز
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
