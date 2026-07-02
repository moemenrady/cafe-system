@extends('layouts.app')

@section('page_title', 'سجل فواتير المبيعات')

@section('content')
    <div class="container mx-auto space-y-8 relative pb-12" dir="rtl">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-fade-in">
            <div
                class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:border-cafePrimary transition-all duration-300">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">إجمالي المبيعات الحالية</p>
                    <h3 class="text-xl font-bold text-gray-800">{{ number_format($invoices->sum('total'), 2) }} ج.م</h3>
                </div>
                <div
                    class="w-12 h-12 bg-green-50 text-green-500 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>

            <div
                class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:border-cafePrimary transition-all duration-300">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">عدد الفواتير المصدرة</p>
                    <h3 class="text-xl font-bold text-gray-800">{{ $invoices->total() }} فاتورة</h3>
                </div>
                <div
                    class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>

            <div
                class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:border-cafePrimary transition-all duration-300">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">متوسط قيمة الفاتورة</p>
                    <h3 class="text-xl font-bold text-gray-800">
                        {{ $invoices->count() > 0 ? number_format($invoices->sum('total') / $invoices->count(), 2) : '0.00' }}
                        ج.م
                    </h3>
                </div>
                <div
                    class="w-12 h-12 bg-purple-50 text-purple-500 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-calculator"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden animate-fade-in"
            style="animation-delay: 0.1s;">
            <div
                class="p-6 border-b border-gray-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice-dollar text-cafePrimary"></i> أرشيف المبيعات
                    </h3>
                    <p class="text-xs text-gray-400 mt-1">تتبع كافة العمليات المالية المصدرة من شاشات الكاشير</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50/70 text-gray-500 text-xs font-bold border-b border-gray-100">
                            <th class="p-4 text-center w-24">رقم الفاتورة</th>
                            <th class="p-4">مسؤول البيع (الكاشير)</th>
                            <th class="p-4 text-center">تاريخ ووقت العملية</th>
                            <th class="p-4 text-center">قيمة الفاتورة</th>
                            <th class="p-4 text-center">حالة الدفع</th>
                            <th class="p-4 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($invoices as $invoice)
                            <tr class="text-sm hover:bg-gray-50/50 transition-all duration-200 group">
                                <td
                                    class="p-4 text-center font-bold text-gray-600 bg-gray-50/30 group-hover:bg-cafeSecondary/10 rounded-l-md">
                                    {{ $invoice->invoice_number }}
                                </td>
                                <td class="p-4 font-semibold text-gray-800">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-8 h-8 bg-sidebar text-cafePrimary rounded-xl flex items-center justify-center text-xs shadow-sm">
                                            <i class="fa-solid fa-user-tie"></i>
                                        </div>
                                        <div class="flex flex-col">
                                            <span>{{ $invoice->creator->name ?? 'غير معروف' }}</span>
                                            <span class="text-[10px] text-gray-400 font-normal">كاشير</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-center text-gray-500 text-xs font-medium">
                                    <i
                                        class="fa-regular fa-clock ml-1 text-gray-400"></i>{{ $invoice->created_at->format('Y-m-d - h:i A') }}
                                </td>
                                <td class="p-4 text-center font-bold text-gray-700">
                                    {{ number_format($invoice->total, 2) }} ج.م
                                </td>
                                <td class="p-4 text-center">
                                    <span
                                        class="px-2.5 py-1 bg-green-50 text-green-600 text-[11px] font-bold rounded-lg border border-green-100 inline-flex items-center gap-1">
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block animate-pulse"></span>
                                        {{ ucfirst($invoice->payment_method) }}
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" onclick="viewInvoiceDetails({{ $invoice->id }})"
                                            class="px-3 py-2 bg-gray-50 hover:bg-cafePrimary hover:text-sidebar rounded-xl text-xs font-bold text-gray-600 transition-all duration-200 flex items-center gap-1.5 active:scale-95 border border-gray-100 shadow-sm">
                                            <i class="fa-solid fa-receipt text-xs"></i> عرض
                                        </button>

                                        @if (auth()->user() && in_array(auth()->user()->role, ['admin', 'supervisor']))
                                            <button type="button" onclick="openEditModal({{ $invoice->id }})"
                                                class="px-3 py-2 bg-amber-50 hover:bg-amber-500 hover:text-white rounded-xl text-xs font-bold text-amber-600 transition-all duration-200 flex items-center gap-1.5 active:scale-95 border border-amber-100 shadow-sm">
                                                <i class="fa-solid fa-pen-to-square text-xs"></i> تعديل
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-gray-400 bg-gray-50/20">
                                    <div
                                        class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-3 text-xl">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </div>
                                    <p class="font-bold text-gray-700 text-sm">سجل المبيعات فارغ</p>
                                    <p class="text-xs text-gray-400 mt-1">لم يتم إجراء أي فواتير بيع من شاشة الـ POS حتى
                                        اللحظة.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-gray-50 bg-gray-50/30">
                {{ $invoices->links() }}
            </div>
        </div>

        <div id="invoiceModal"
            class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl max-w-md w-full mx-4 shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden animate-slide-in relative">

                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                    <h4 class="text-md font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-cafePrimary"></i> فاتورة بيع <span id="modalInvoiceId"
                            class="font-black text-cafePrimary"></span>
                    </h4>
                    <button onclick="closeInvoiceModal()"
                        class="text-gray-400 hover:text-gray-600 p-1.5 bg-white rounded-full shadow-xs border border-gray-100 transition active:scale-90">
                        <i class="fa-solid fa-xmark text-md"></i>
                    </button>
                </div>

                <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                    <div class="text-center space-y-1">
                        <h5 class="font-black text-lg text-sidebar">ENTERPRISE CAFE</h5>
                        <p class="text-[11px] text-gray-400 font-medium">مرحباً بك في نظام إدارة الكافيه الذكي</p>
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 text-xs text-gray-500 bg-gray-50/50 p-3.5 rounded-2xl border border-gray-100/60 font-medium">
                        <div class="space-y-1">
                            <p class="text-gray-400">بواسطة الموظف:</p>
                            <p class="font-bold text-gray-700" id="modalCashier"></p>
                        </div>
                        <div class="space-y-1 text-left">
                            <p class="text-gray-400">تاريخ المعاملة:</p>
                            <p class="font-bold text-gray-700" id="modalDate" dir="ltr"></p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-bold text-gray-400">تفاصيل المشروبات والطلبات:</p>
                        <div class="border border-gray-100 rounded-2xl overflow-hidden bg-white shadow-xs">
                            <table class="w-full text-right text-xs">
                                <thead class="bg-gray-50/80 text-gray-500 font-bold border-b border-gray-100">
                                    <tr>
                                        <th class="p-3">العنصر</th>
                                        <th class="p-3 text-center w-16">الكمية</th>
                                        <th class="p-3 text-center">السعر</th>
                                        <th class="p-3 text-left pl-4">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody id="modalInvoiceItems" class="divide-y divide-gray-50 text-gray-700 font-medium">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 space-y-2.5 text-xs">
                        <div class="flex justify-between items-center text-gray-500">
                            <span>إجمالي الفاتورة الصافي:</span>
                            <span id="modalTotalPrice" class="font-bold text-gray-700"></span>
                        </div>
                        <div class="flex justify-between items-center text-gray-500">
                            <span>طريقة الدفع:</span>
                            <span id="modalPaymentMethod" class="font-bold text-gray-700"></span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-gray-50/50 border-t border-gray-100 text-center">
                    <p class="text-[10px] font-bold text-gray-400 tracking-wide">شكراً لاستخدامك نظامنا الذكي المتكامل</p>
                </div>
            </div>
        </div>

        <div id="editInvoiceModal"
            class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300 backdrop-blur-xs">
            <div
                class="bg-white rounded-3xl max-w-lg w-full mx-4 shadow-2xl transform scale-95 transition-transform duration-300 overflow-hidden relative">
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-amber-50/50">
                    <h4 class="text-md font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-amber-500"></i> تعديل الفاتورة <span
                            id="editModalInvoiceNum" class="font-black text-amber-600"></span>
                    </h4>
                    <button onclick="closeEditModal()"
                        class="text-gray-400 hover:text-gray-600 p-1.5 bg-white rounded-full shadow-xs border border-gray-100 transition">
                        <i class="fa-solid fa-xmark text-md"></i>
                    </button>
                </div>

                <form id="editInvoiceForm" onsubmit="submitInvoiceEdit(event)" class="p-6 space-y-4">
                    <input type="hidden" id="editInvoiceId">

                    <div class="max-h-[40vh] overflow-y-auto space-y-3 pr-1" id="editInvoiceItemsContainer">
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-gray-500">الخصم المطبق (ج.م)</label>
                        <input type="number" id="editInvoiceDiscount" min="0" step="0.5"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-amber-500 font-bold text-gray-700">
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex justify-end gap-2">
                        <button type="button" onclick="closeEditModal()"
                            class="px-4 py-2.5 bg-gray-100 text-gray-600 text-xs font-bold rounded-xl hover:bg-gray-200 transition">إلغاء</button>
                        <button type="submit"
                            class="px-5 py-2.5 bg-amber-500 text-white text-xs font-bold rounded-xl hover:bg-amber-600 transition shadow-md shadow-amber-500/20">حفظ
                            التعديلات الذكية</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        // جلب تفاصيل الفاتورة للعرض
        function viewInvoiceDetails(id) {
            fetch(`/invoices/${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalInvoiceId').innerText = `#${data.invoice_number}`;
                    document.getElementById('modalCashier').innerText = data.creator ? data.creator.name : 'غير معروف';
                    document.getElementById('modalDate').innerText = data.created_at;
                    document.getElementById('modalTotalPrice').innerText = `${data.total} ج.م`;
                    document.getElementById('modalPaymentMethod').innerText = data.payment_method;

                    const tbody = document.getElementById('modalInvoiceItems');
                    tbody.innerHTML = '';

                    data.items.forEach(item => {
                        tbody.innerHTML += `
                        <tr class="hover:bg-gray-50/50 transition duration-150">
                            <td class="p-3 font-semibold text-gray-800">${item.menu ? item.menu.name : 'منتج محذوف'}</td>
                            <td class="p-3 text-center font-bold text-sidebar bg-gray-50/30">${item.quantity}</td>
                            <td class="p-3 text-center text-gray-400">${item.item_price} ج.م</td>
                            <td class="p-3 text-left pl-4 font-bold text-gray-700">${parseFloat(item.total).toFixed(2)} ج.م</td>
                        </tr>
                    `;
                    });

                    const modal = document.getElementById('invoiceModal');
                    modal.classList.remove('hidden');
                    setTimeout(() => modal.classList.add('opacity-100'), 10);
                });
        }

        function closeInvoiceModal() {
            const modal = document.getElementById('invoiceModal');
            modal.classList.remove('opacity-100');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // 🌟 فتح مودال التعديل وجلب بيانات الفاتورة الحالية
        function openEditModal(id) {
            fetch(`/invoices/${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editInvoiceId').value = id;
                    document.getElementById('editModalInvoiceNum').innerText = `#${data.invoice_number}`;

                    // جلب الخصم من الداتا الأساسية إذا كانت ممررة أو نضع 0 كافتراضي
                    document.getElementById('editInvoiceDiscount').value = data.discount || 0;

                    const container = document.getElementById('editInvoiceItemsContainer');
                    container.innerHTML = '';

                    data.items.forEach((item, index) => {
                        container.innerHTML += `
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100 gap-4">
                            <div class="flex-1">
                                <p class="text-xs font-bold text-gray-800">${item.menu ? item.menu.name : 'منتج محذوف'}</p>
                                <p class="text-[10px] text-gray-400 font-medium">سعر الوحدة: ${item.item_price} ج.م</p>
                                <input type="hidden" name="items[${index}][menu_id]" value="${item.menu_id}">
                            </div>
                            <div class="w-28">
                                <input type="number" name="items[${index}][quantity]" value="${item.quantity}" min="1" 
                                       class="w-full text-center border border-gray-200 rounded-lg py-1.5 text-xs font-bold focus:outline-none focus:border-amber-500 edit-item-qty">
                            </div>
                        </div>
                    `;
                    });

                    const modal = document.getElementById('editInvoiceModal');
                    modal.classList.remove('hidden');
                    setTimeout(() => modal.classList.add('opacity-100'), 10);
                });
        }

        function closeEditModal() {
            const modal = document.getElementById('editInvoiceModal');
            modal.classList.remove('opacity-100');
            setTimeout(() => modal.classList.add('hidden'), 300);
        }

        // 🌟 إرسال التعديلات الجديدة إلى الـ Backend عبر AJAX وحفظ الحركة الرقابية تلقائياً
        function submitInvoiceEdit(event) {
            event.preventDefault();
            const id = document.getElementById('editInvoiceId').value;
            const discount = document.getElementById('editInvoiceDiscount').value;

            // تجميع العناصر المعدلة من الفورم
            const items = [];
            const qtyInputs = document.querySelectorAll('.edit-item-qty');

            qtyInputs.forEach((input, index) => {
                const menuIdInput = document.getElementsByName(`items[${index}][menu_id]`)[0];
                if (menuIdInput) {
                    items.push({
                        menu_id: menuIdInput.value,
                        quantity: input.value
                    });
                }
            });

            // إرسال البيانات بطلب PUT للكنترولر
            fetch(`/invoices/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        items: items,
                        discount: discount
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        closeEditModal();
                        window.location.reload(); // عمل ريفريش لتحديث الأرقام في الجدول
                    } else {
                        alert(data.error || 'حدث خطأ أثناء تعديل الفاتورة');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في الاتصال بالخادم.');
                    alert(error);

                });
        }
    </script>
@endpush
