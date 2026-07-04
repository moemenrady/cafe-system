@extends('layouts.app')

@section('page_title', 'شاشة البيع السريع - POS')

@section('content')
<div class="container mx-auto px-4 relative" dir="rtl">
    
    <!-- النافذة المنبثقة للنجاح -->
    <div id="successModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full mx-4 text-center shadow-2xl transform scale-95 transition-transform duration-300 relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-b from-green-50/50 to-transparent pointer-events-none"></div>
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 text-green-500 text-2xl animate-bounce">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h4 class="text-lg font-bold text-gray-800 mb-1">تم حفظ العملية!</h4>
            <p class="text-xs text-gray-500">تم تسجيل الفاتورة وتحديث كميات المخزن تلقائياً.</p>
        </div>
    </div>

    <!-- شبكة العرض الرئيسية للـ POS -->
    <form id="posForm" action="{{ route('invoices.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        @csrf
        <input type="hidden" name="payment_method" value="cash">

        <!-- القسم الأيمن: أدوات الفلترة والمنيو والسلّة -->
        <div class="lg:col-span-2 space-y-4">
            
            <!-- لوحة التحكم السريع بالبحث والأقسام -->
            <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 space-y-4">
                <!-- 1. شريط البحث الفوري -->
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400">
                        <i class="fa-solid fa-magnifying-glass text-base"></i>
                    </span>
                    <input type="text" id="searchInput" oninput="filterItems()" 
                           class="w-full bg-gray-50 text-gray-800 pr-11 pl-4 py-3 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none font-medium text-sm transition-all" 
                           placeholder="ابحث عن أي منتج باسمه فوراً (مثال: قهوة، لاتيه)...">
                </div>

                <!-- 2. أزرار الأقسام السريعة -->
                <div class="flex flex-wrap gap-2 items-center border-t border-gray-50 pt-3">
                    <button type="button" onclick="selectCategory('all')" id="tab-all"
                            class="category-tab bg-blue-600 text-white px-4 py-2 rounded-xl text-xs font-bold transition-all shadow-sm">
                        📁 الكل ({{ $menus->count() }})
                    </button>
                    
                    @php
                        $uniqueCategories = $menus->pluck('category')->unique('id')->filter();
                    @endphp

                    @foreach($uniqueCategories as $category)
                        <button type="button" onclick="selectCategory('{{ $category->id }}')" id="tab-{{ $category->id }}"
                                class="category-tab bg-gray-50 text-gray-600 hover:bg-gray-100 border border-gray-100 px-4 py-2 rounded-xl text-xs font-bold transition-all">
                            📦 {{ $category->name }}
                        </button>
                    @endforeach
                </div>
            </div>
            
            <!-- شبكة عرض كروت المينيو الذكية الواضحة -->
            <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
    <div id="menuGrid" class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($menus as $menu)
            <button type="button" 
                    onclick="addItem('{{ $menu->id }}', '{{ $menu->name }}', {{ $menu->price }})"
                    data-id="{{ $menu->id }}"
                    data-name="{{ $menu->name }}"
                    data-category="{{ $menu->category_id }}"
                    class="menu-item-card bg-white hover:bg-blue-50/20 border border-gray-200 hover:border-blue-400 rounded-2xl flex flex-col overflow-hidden group transition-all duration-150 active:scale-95 shadow-sm hover:shadow-md h-48">
                
                @if($menu->image)
                    <div class="w-full h-24 bg-gray-100 overflow-hidden relative shrink-0">
                        <img src="{{ asset('storage/' . $menu->image) }}" 
                             alt="{{ $menu->name }}" 
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent"></div>
                    </div>

                    <div class="p-3 flex-1 flex flex-col justify-between items-start w-full text-right">
                        <span class="font-bold text-gray-800 text-xs sm:text-sm line-clamp-2 min-h-[2.5rem] w-full group-hover:text-blue-600 transition-colors">
                            {{ $menu->name }}
                        </span>
                        <span class="text-blue-600 font-black text-xs sm:text-sm bg-blue-50 px-2 py-1 rounded-lg block self-start">
                            {{ number_format($menu->price, 2) }} ج.م
                        </span>
                    </div>
                @else
                    <div class="p-4 flex-1 flex flex-col justify-center items-center w-full text-center space-y-3 bg-gray-50/40 group-hover:bg-blue-50/10 transition-colors">
                        <span class="font-bold text-gray-800 text-sm sm:text-base line-clamp-3 w-full group-hover:text-blue-700 transition-colors px-1 leading-snug">
                            {{ $menu->name }}
                        </span>
                        
                        <span class="text-blue-600 font-black text-xs sm:text-sm bg-blue-50 border border-blue-100/50 px-2.5 py-1 rounded-xl inline-block">
                            {{ number_format($menu->price, 2) }} ج.م
                        </span>
                    </div>
                @endif

            </button>
        @endforeach
    </div>

    <div id="noResults" class="hidden text-center py-12 text-gray-400 font-medium text-sm">
         لم نجد أي منتج يطابق بحثك الحالي..
    </div>
</div>

            <!-- السلّة الحالية -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-4 border-b border-gray-50 bg-gray-50/50">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-basket-shopping text-blue-500"></i> الأصناف المطلوبة بالفاتورة
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-400 text-xs font-bold border-b border-gray-100">
                                <th class="p-3">المنتج</th>
                                <th class="p-3 text-center w-24">الكمية</th>
                                <th class="p-3 text-center">السعر</th>
                                <th class="p-3 text-center">الإجمالي</th>
                                <th class="p-3 text-center w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="invoiceItems" class="divide-y divide-gray-50">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- القسم الأيسر: ملخص الحساب والإتمام السريع -->
        <div class="lg:col-span-1 sticky top-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="text-lg font-bold text-gray-800">ملخص الحساب</h3>
                    <p class="text-xs text-gray-400 mt-0.5">مراجعة وتأكيد المبيعات</p>
                </div>
                
                <div class="space-y-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="flex justify-between text-sm text-gray-500 font-medium">
                        <span>عدد الأصناف:</span>
                        <span id="itemsCount" class="font-bold text-gray-700">0</span>
                    </div>
                    <div class="flex justify-between text-sm text-gray-500 font-medium">
                        <span>الإجمالي الأساسي:</span>
                        <span id="subtotal" class="font-bold text-gray-700">0.00 ج.م</span>
                    </div>
                    <hr class="border-gray-200/60">
                    <div class="flex justify-between items-center">
                        <span class="text-base font-bold text-gray-800">المطلب سداده:</span>
                        <span id="grandTotal" class="text-2xl font-black text-emerald-600">0.00 ج.م</span>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="w-full bg-emerald-500 hover:bg-emerald-600 active:scale-[0.99] text-white font-bold py-4 rounded-xl shadow-lg shadow-emerald-500/20 transition-all text-center flex items-center justify-center gap-2 text-base group">
                    <i class="fa-solid fa-cash-register group-hover:scale-110 transition-transform"></i> إتمام العملية وحفظ الفاتورة
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let cart = {};
    let currentCategoryId = 'all';

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

        const noResults = document.getElementById('noResults');
        if (visibleCount === 0) {
            noResults.classList.remove('hidden');
        } else {
            noResults.classList.add('hidden');
        }
    }

    function selectCategory(categoryId) {
        currentCategoryId = categoryId;
        
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('bg-blue-600', 'text-white', 'shadow-sm');
            tab.classList.add('bg-gray-50', 'text-gray-600', 'border-gray-100');
        });

        const activeTab = document.getElementById(`tab-${categoryId}`);
        if(activeTab) {
            activeTab.classList.remove('bg-gray-50', 'text-gray-600', 'border-gray-100');
            activeTab.classList.add('bg-blue-600', 'text-white', 'shadow-sm');
        }

        filterItems();
    }

    function addItem(id, name, price) {
        if (cart[id]) {
            cart[id].quantity += 1;
        } else {
            cart[id] = { id: id, name: name, price: price, quantity: 1 };
        }
        renderCart();
    }

    // دالة التركيز التلقائي على شريط البحث بعد إضافة أي منتج لتسريع الإدخال المتتالي
    function focusSearch() {
        document.getElementById('searchInput').focus();
    }

    function updateQuantity(id, qty) {
        if (qty <= 0) {
            delete cart[id];
        } else {
            cart[id].quantity = parseInt(qty) || 1;
        }
        renderCart();
    }

    function removeItem(id) {
        const row = document.getElementById(`row-${id}`);
        if(row) {
            row.classList.add('opacity-0', 'translate-x-4');
            setTimeout(() => {
                delete cart[id];
                renderCart();
            }, 120);
        }
    }

    function renderCart() {
        const tbody = document.getElementById('invoiceItems');
        tbody.innerHTML = '';
        let total = 0;
        let index = 0;
        let uniqueItemsCount = 0;

        for (let key in cart) {
            const item = cart[key];
            const itemTotal = item.price * item.quantity;
            total += itemTotal;
            uniqueItemsCount++;

            tbody.innerHTML += `
                <tr id="row-${item.id}" class="text-xs hover:bg-gray-50/60 transition-all duration-150">
                    <td class="p-3 font-semibold text-gray-800">
                        ${item.name}
                        <input type="hidden" name="items[${index}][menu_id]" value="${item.id}">
                    </td>
                    <td class="p-3 text-center">
                        <div class="flex items-center justify-center border border-gray-200 rounded-lg overflow-hidden bg-white">
                            <input type="number" name="items[${index}][quantity]" value="${item.quantity}" min="1" 
                                   onchange="updateQuantity('${item.id}', this.value)"
                                   class="w-12 p-1 text-center bg-white font-bold text-gray-800 outline-none border-none">
                        </div>
                    </td>
                    <td class="p-3 text-center text-gray-400">${item.price.toFixed(2)} ج</td>
                    <td class="p-3 text-center font-bold text-gray-700 bg-gray-50/30">${itemTotal.toFixed(2)} ج</td>
                    <td class="p-3 text-center">
                        <button type="button" onclick="removeItem('${item.id}')" class="text-gray-400 hover:text-red-500 p-1 transition-colors">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
            index++;
        }

        if (uniqueItemsCount === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-6 text-center text-gray-400 font-medium">السلة فارغة. اضغط على أي صنف بالأعلى لإضافته.</td>
                </tr>
            `;
        }

        document.getElementById('itemsCount').innerText = uniqueItemsCount;
        document.getElementById('subtotal').innerText = total.toFixed(2) + ' ج.م';
        document.getElementById('grandTotal').innerText = total.toFixed(2) + ' ج.م';
    }

    renderCart();

    function showSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.add('opacity-100'), 10);
        setTimeout(() => closeSuccessModal(), 1200); 
    }

    function closeSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.classList.remove('opacity-100');
        setTimeout(() => modal.classList.add('hidden'), 300);
    }

    document.getElementById('posForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (Object.keys(cart).length === 0) {
            alert('الرجاء إضافة منتجات أولاً قبل إتمام البيع');
            return;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري الحفظ...';

        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cash-register"></i> إتمام العملية وحفظ الفاتورة';
            
            if (data.success) {
                showSuccessModal(); 
                cart = {};
                renderCart();
                document.getElementById('searchInput').value = '';
                selectCategory('all');
                focusSearch();
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cash-register"></i> إتمام العملية وحفظ الفاتورة';
            alert('حدث خطأ غير متوقع أثناء الحفظ.');
        });
    });
</script>
@endpush