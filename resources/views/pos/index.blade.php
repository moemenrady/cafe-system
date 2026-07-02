@extends('layouts.app')

@section('page_title', 'شاشة البيع السريع - POS')

@section('content')
<div class="container mx-auto relative" dir="rtl">
    
    <div id="successModal" class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full mx-4 text-center shadow-2xl transform scale-95 transition-transform duration-300 relative overflow-hidden animate-slide-in">
            <div class="absolute inset-0 bg-gradient-to-b from-green-50/50 to-transparent pointer-events-none"></div>
            
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 text-green-500 text-2xl animate-bounce">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            
            <h4 class="text-lg font-bold text-gray-800 mb-1">تم حفظ العملية!</h4>
            <p class="text-xs text-gray-500">تم تسجيل الفاتورة وتحديث كميات المخزن تلقائياً.</p>
        </div>
    </div>

    <form id="posForm" action="{{ route('invoices.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        @csrf
        
        <input type="hidden" name="payment_method" value="cash">

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-mug-hot text-cafePrimary"></i> قائمة المينيو المتاحة
                </h3>
                
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                    @foreach($menus as $menu)
                        <button type="button" 
                                onclick="addItem('{{ $menu->id }}', '{{ $menu->name }}', {{ $menu->price }})"
                                class="bg-gray-50 hover:bg-cafeSecondary/10 border border-gray-100 hover:border-cafePrimary rounded-2xl text-right flex flex-col overflow-hidden group transition-all duration-200 active:scale-95 transform hover:-translate-y-0.5 hover:shadow-md h-40">
                            
                            <div class="w-full h-24 bg-gray-200 overflow-hidden relative shrink-0">
                                <img src="{{ $menu->image ? asset('storage/' . $menu->image) : 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?q=80&w=400&auto=format&fit=crop' }}" 
                                     alt="{{ $menu->name }}" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </div>

                            <div class="p-3 flex-1 flex flex-col justify-between w-full bg-white border-t border-gray-50">
                                <span class="font-bold text-gray-700 group-hover:text-gray-900 text-xs truncate block w-full">{{ $menu->name }}</span>
                                <span class="text-cafePrimary font-bold text-[11px] bg-sidebar text-white px-2 py-0.5 rounded-lg self-start mt-1 transition-colors group-hover:bg-cafePrimary group-hover:text-sidebar">{{ $menu->price }} ج.م</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-50">
                    <h3 class="text-lg font-bold text-gray-800">العناصر المطلوبة</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-right border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs font-semibold uppercase border-b border-gray-100">
                                <th class="p-4">المنتج</th>
                                <th class="p-4 text-center">الكمية</th>
                                <th class="p-4 text-center">السعر</th>
                                <th class="p-4 text-center">الإجمالي</th>
                                <th class="p-4 text-center">إجراء</th>
                            </tr>
                        </thead>
                        <tbody id="invoiceItems">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-1">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 space-y-6 sticky top-6">
                <h3 class="text-lg font-bold text-gray-800 border-b pb-3">إجمالي الحساب</h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-500">
                        <span>إجمالي الطلبات:</span>
                        <span id="subtotal">0.00 ج.م</span>
                    </div>
                    <hr class="border-gray-100">
                    <div class="flex justify-between text-lg font-bold text-gray-800">
                        <span>المطلوب دفعه:</span>
                        <span id="grandTotal" class="text-green-600">0.00 ج.م</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">المبلغ المدفوع كاش</label>
                        <input type="number" id="paidAmount" name="paid_amount" oninput="calculateChange()" class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-lg font-bold focus:outline-none focus:border-cafePrimary text-center transition-all focus:bg-white" placeholder="0.00" required>
                    </div>
                    <div class="bg-yellow-50/50 border border-yellow-100 rounded-xl p-3 flex justify-between text-sm">
                        <span class="text-gray-600">المتبقي للعميل (الباقي):</span>
                        <span id="changeAmount" class="font-bold text-yellow-700">0.00 ج.م</span>
                    </div>
                </div>

                <button type="submit" id="submitBtn" class="w-full bg-green-500 hover:bg-green-600 active:scale-[0.99] text-white font-bold py-4 rounded-xl shadow-lg shadow-green-500/20 transition-all text-center flex items-center justify-center gap-2 text-md group">
                    <i class="fa-solid fa-cash-register group-hover:animate-pulse"></i> إتمام العملية وطباعة الفاتورة
                </button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let cart = {};

    function addItem(id, name, price) {
        if (cart[id]) {
            cart[id].quantity += 1;
        } else {
            cart[id] = { id: id, name: name, price: price, quantity: 1 };
        }
        renderCart();
    }

    function updateQuantity(id, qty) {
        if (qty <= 0) {
            delete cart[id];
        } else {
            cart[id].quantity = parseInt(qty);
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
            }, 200);
        }
    }

    function renderCart() {
        const tbody = document.getElementById('invoiceItems');
        tbody.innerHTML = '';
        let total = 0;
        let index = 0;

        for (let key in cart) {
            const item = cart[key];
            const itemTotal = item.price * item.quantity;
            total += itemTotal;

            tbody.innerHTML += `
                <tr id="row-${item.id}" class="border-b border-gray-50 text-sm hover:bg-gray-50/50 transition-all duration-300 transform animate-fade-in">
                    <td class="p-4 font-medium text-gray-800">
                        ${item.name}
                        <input type="hidden" name="items[${index}][menu_id]" value="${item.id}">
                    </td>
                    <td class="p-4 text-center">
                        <input type="number" name="items[${index}][quantity]" value="${item.quantity}" min="1" 
                               onchange="updateQuantity('${item.id}', this.value)"
                               class="w-16 border rounded-lg p-1 text-center bg-white font-semibold focus:border-cafePrimary outline-none">
                    </td>
                    <td class="p-4 text-center text-gray-500">${item.price.toFixed(2)} ج.م</td>
                    <td class="p-4 text-center font-bold text-gray-700">${itemTotal.toFixed(2)} ج.م</td>
                    <td class="p-4 text-center">
                        <button type="button" onclick="removeItem('${item.id}')" class="text-red-400 hover:text-red-600 p-1 transition transform hover:scale-110">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
            index++;
        }

        document.getElementById('subtotal').innerText = total.toFixed(2) + ' ج.م';
        document.getElementById('grandTotal').innerText = total.toFixed(2) + ' ج.م';
        calculateChange();
    }

    function calculateChange() {
        const totalText = document.getElementById('grandTotal').innerText;
        const total = parseFloat(totalText) || 0;
        const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
        const change = paid - total;
        
        document.getElementById('changeAmount').innerText = (change > 0 ? change.toFixed(2) : '0.00') + ' ج.م';
    }

    function showSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.classList.remove('hidden');
        setTimeout(() => {
            modal.classList.add('opacity-100');
        }, 10);

        setTimeout(() => {
            closeSuccessModal();
        }, 1200); 
    }

    function closeSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.classList.remove('opacity-100');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    document.getElementById('posForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (Object.keys(cart).length === 0) {
            alert('الرجاء إضافة منتجات أولاً قبل إتمام البيع');
            return;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري حفظ الفاتورة...';

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
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cash-register"></i> إتمام العملية وطباعة الفاتورة';
            
            if (data.success) { // 🌟 التعديل هنا ليتوافق مع الـ JSON response الجديد
                showSuccessModal(); 
                cart = {};
                renderCart();
                document.getElementById('paidAmount').value = '';
            } else if (data.error) {
                alert(data.error);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-cash-register"></i> إتمام العملية وطباعة الفاتورة';
            alert('حدث خطأ غير متوقع أثناء الحفظ.');
        });
    });
</script>
@endpush