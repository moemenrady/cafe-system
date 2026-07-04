@extends('layouts.app')

@section('page_title', 'سجل فواتير المبيعات')

@section('content')
    <div class="container mx-auto space-y-6 relative pb-12" dir="rtl">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 animate-fade-in">
            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:border-blue-400 transition-all duration-300">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">إجمالي مبيعات السجل</p>
                    <h3 class="text-xl font-black text-gray-800">{{ number_format($invoices->sum('total'), 2) }} ج.م</h3>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-500 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-money-bill-wave"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:border-blue-400 transition-all duration-300">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">عدد الفواتير المسجلة</p>
                    <h3 class="text-xl font-black text-gray-800">{{ $invoices->count() }} فاتورة</h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex items-center justify-between group hover:border-blue-400 transition-all duration-300">
                <div class="space-y-1">
                    <p class="text-xs font-semibold text-gray-400">متوسط قيمة الفاتورة</p>
                    <h3 class="text-xl font-black text-gray-800">
                        {{ number_format($invoices->count() > 0 ? $invoices->avg('total') : 0, 2) }} ج.م
                    </h3>
                </div>
                <div class="w-12 h-12 bg-purple-50 text-purple-500 rounded-xl flex items-center justify-center text-lg group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-calculator"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-5 border-b border-gray-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-gray-50/50">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-blue-500"></i> الحركات والفواتير الأخيرة
                    </h3>
                    <p class="text-xs text-gray-400 mt-0.5">تفاصيل العمليات المباعة مباشرة من شاشة البيع</p>
                </div>
                <a href="{{ url('/pos') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-1.5 transition-all shadow-sm">
                    <i class="fa-solid fa-plus text-[10px]"></i> شاشة بيع جديدة
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-400 text-xs font-bold border-b border-gray-100 uppercase tracking-wider">
                            <th class="p-4 w-28">رقم الفاتورة</th>
                            <th class="p-4 w-36">التاريخ والوقت</th>
                            <th class="p-4">الأصناف المباعة والكمية</th>
                            <th class="p-4 w-36">بواسطة (الكاشير)</th>
                            <th class="p-4 text-center w-28">طريقة الدفع</th>
                            <th class="p-4 text-center w-32">الإجمالي النهائي</th>
                            <th class="p-4 text-center w-28">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-xs font-medium text-gray-700">
                        @forelse($invoices as $invoice)
                            <tr class="hover:bg-blue-50/10 transition-colors duration-150">
                                <td class="p-4">
                                    <span class="font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded-lg border border-gray-200/60 cursor-help" 
                                          title="رقم الفاتورة الكامل: {{ $invoice->invoice_number }}">
                                        ...{{ substr($invoice->invoice_number, -4) }}
                                    </span>
                                </td>

                                <td class="p-4 text-gray-500">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-bold text-gray-700"><i class="fa-regular fa-calendar text-gray-400 ml-1"></i>{{ $invoice->created_at->format('Y-m-d') }}</span>
                                        <span class="text-[10px] text-gray-400"><i class="fa-regular fa-clock text-gray-300 ml-1"></i>{{ $invoice->created_at->format('g:i A') }}</span>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="flex flex-wrap gap-1.5 max-w-xl">
                                        @if($invoice->items && $invoice->items->count() > 0)
                                            @foreach($invoice->items as $item)
                                                <div class="inline-flex items-center bg-gray-50 text-gray-800 border border-gray-200 rounded-lg px-2 py-1 gap-1.5 shadow-2xs">
                                                    <span class="font-bold text-gray-700 text-[11px]">{{ $item->menu->name ?? 'صنف محذوف' }}</span>
                                                    <span class="bg-blue-100 text-blue-700 font-extrabold px-1.5 py-0.5 rounded text-[10px]">x{{ $item->quantity }}</span>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-gray-400 italic">لا توجد أصناف</span>
                                        @endif
                                    </div>
                                </td>

                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1.5 bg-gray-50 text-gray-700 border border-gray-200 rounded-lg px-2 py-1 font-bold text-[11px]">
                                        <i class="fa-solid fa-user-tie text-blue-500/80"></i>
                                        {{ $invoice->creator->name ?? 'كاشير عام' }}
                                    </span>
                                </td>

                                <td class="p-4 text-center">
                                    @if(($invoice->payment_method ?? 'cash') == 'cash')
                                        <span class="bg-emerald-50 text-emerald-600 border border-emerald-100 px-2 py-1 rounded-xl text-[11px] font-bold inline-block">💵 كاش</span>
                                    @else
                                        <span class="bg-blue-50 text-blue-600 border border-blue-100 px-2 py-1 rounded-xl text-[11px] font-bold inline-block">💳 فيزا</span>
                                    @endif
                                </td>

                                <td class="p-4 text-center font-black text-gray-900 text-sm bg-gray-50/30">
                                    {{ number_format($invoice->total, 2) }} ج.م
                                </td>

                                <td class="p-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" 
                                                onclick="openDetailsModal('{{ $invoice->invoice_number }}', '{{ $invoice->created_at->format('Y-m-d g:i A') }}', '{{ ($invoice->payment_method ?? 'cash') == 'cash' ? '💵 كاش' : '💳 فيزا / شيك' }}', '{{ number_format($invoice->discount, 2) }}', '{{ number_format($invoice->total, 2) }}', {{ json_encode($invoice->items->map(fn($i) => ['name' => $i->menu->name ?? 'صنف محذوف', 'qty' => $i->quantity, 'price' => number_format($i->price, 2), 'total' => number_format($i->price * $i->quantity, 2)])) }}, '{{ $invoice->creator->name ?? 'غير معروف' }}')" 
                                                class="text-emerald-600 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 w-8 h-8 rounded-lg flex items-center justify-center transition-colors shadow-2xs" 
                                                title="عرض كامل التفاصيل">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>

                                        <button type="button" 
                                                onclick="openEditModal('{{ $invoice->id }}', '{{ $invoice->invoice_number }}', '{{ $invoice->discount }}', {{ json_encode($invoice->items->map(fn($i) => ['menu_id' => $i->menu_id, 'name' => $i->menu->name ?? 'صنف محذوف', 'qty' => $i->quantity])) }})" 
                                                class="text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 w-8 h-8 rounded-lg flex items-center justify-center transition-colors shadow-2xs" 
                                                title="تعديل الفاتورة">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button" 
                                                onclick="deleteInvoice('{{ $invoice->id }}', '{{ $invoice->invoice_number }}')" 
                                                class="text-red-600 hover:text-red-800 bg-red-50 hover:bg-red-100 w-8 h-8 rounded-lg flex items-center justify-center transition-colors shadow-2xs" 
                                                title="إلغاء وحذف الفاتورة">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-gray-400 font-medium">سجل المبيعات فارغ.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200" dir="rtl">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 shadow-xl overflow-hidden transform scale-95 transition-transform duration-200">
            <div class="bg-gray-50 p-4 border-b border-gray-100 flex justify-between items-center">
                <h4 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-info text-emerald-500"></i> تفاصيل الفاتورة بالكامل
                </h4>
                <button onclick="closeDetailsModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>
            
            <div class="p-5 space-y-4 text-xs">
                <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <div class="col-span-2">
                        <span class="text-gray-400 block mb-0.5">رقم الفاتورة الكامل:</span>
                        <span id="detFullNumber" class="font-bold text-gray-900 tracking-wide select-all"></span>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-0.5">تاريخ الإصدار:</span>
                        <span id="detDate" class="font-semibold text-gray-700"></span>
                    </div>
                    <div>
                        <span class="text-gray-400 block mb-0.5">طريقة الدفع:</span>
                        <span id="detPayment" class="font-bold text-gray-800"></span>
                    </div>
                    <div class="col-span-2 border-t border-gray-200/60 pt-2 mt-1">
                        <span class="text-gray-400 inline-block mb-0.5">الكاشير المسؤول:</span>
                        <span id="detCashier" class="font-black text-blue-600 mr-1"></span>
                    </div>
                </div>

                <div>
                    <span class="font-bold text-gray-800 block mb-2">الأصناف والطلبات:</span>
                    <div class="border border-gray-100 rounded-xl overflow-hidden max-h-48 overflow-y-auto">
                        <table class="w-full text-right">
                            <tr class="bg-gray-50 text-gray-400 font-bold border-b border-gray-100">
                                <th class="p-2">الصنف</th>
                                <th class="p-2 text-center">الكمية</th>
                                <th class="p-2 text-left">السعر</th>
                            </tr>
                            <tbody id="detItemsBody" class="divide-y divide-gray-50 font-medium text-gray-700"></tbody>
                        </table>
                    </div>
                </div>

                <div class="border-t border-gray-100 pt-3 space-y-1.5 text-right">
                    <div class="flex justify-between text-gray-500">
                        <span>قيمة الخصم:</span>
                        <span id="detDiscount" class="font-bold text-red-500">0.00 ج.م</span>
                    </div>
                    <div class="flex justify-between text-sm font-black text-gray-900 bg-emerald-50 px-3 py-2 rounded-xl">
                        <span>الإجمالي النهائي الحقيقي:</span>
                        <span id="detTotal">0.00 ج.م</span>
                    </div>
                </div>
            </div>

            <div class="p-3 bg-gray-50 border-t border-gray-100 flex justify-end">
                <button onclick="closeDetailsModal()" class="bg-gray-800 hover:bg-gray-900 text-white font-bold px-4 py-1.5 rounded-xl transition-all">إغلاق النافذة
                </button>
            </div>
        </div>
    </div>

    <div id="editModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-200" dir="rtl">
        <div class="bg-white rounded-2xl max-w-md w-full mx-4 shadow-xl overflow-hidden transform scale-95 transition-transform duration-200">
            <div class="bg-gray-50 p-4 border-b border-gray-100 flex justify-between items-center">
                <h4 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-pen-to-square text-blue-500"></i> تعديل كميات وخصم الفاتورة
                </h4>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>
            
            <form id="editInvoiceForm" onsubmit="submitInvoiceEdit(event)" class="p-5 space-y-4 text-xs">
                <div>
                    <span class="text-gray-400 block mb-0.5">رقم الفاتورة الجاري تعديلها:</span>
                    <span id="editInvoiceNumber" class="font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded-lg border border-gray-200 inline-block"></span>
                </div>

                <div>
                    <span class="font-bold text-gray-800 block mb-2">الأصناف والكميات الحالية:</span>
                    <div id="editItemsContainer" class="space-y-2.5 max-h-52 overflow-y-auto p-0.5">
                        </div>
                </div>

                <div class="space-y-1">
                    <label class="font-bold text-gray-700 block">قيمة الخصم المطبق (ج.م):</label>
                    <input type="number" id="editDiscountInput" step="0.01" min="0" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-blue-500 font-bold text-red-500" required>
                </div>

                <div class="border-t border-gray-100 pt-3 flex justify-end gap-2 bg-gray-50 -mx-5 -mb-5 p-3">
                    <button type="button" onclick="closeEditModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-4 py-2 rounded-xl transition-all">
                        إلغاء الأمر
                    </button>
                    <button type="submit" id="saveEditBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl transition-all shadow-sm">
                        حفظ التعديلات الآن
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // دالة فتح مودال التفاصيل
        function openDetailsModal(fullNumber, date, payment, discount, total, items, cashierName) {
            document.getElementById('detFullNumber').innerText = fullNumber;
            document.getElementById('detDate').innerText = date;
            document.getElementById('detPayment').innerText = payment;
            document.getElementById('detDiscount').innerText = discount + ' ج.م';
            document.getElementById('detTotal').innerText = total + ' ج.م';
            document.getElementById('detCashier').innerText = cashierName;

            const tbody = document.getElementById('detItemsBody');
            tbody.innerHTML = '';
            items.forEach(item => {
                tbody.innerHTML += `
                    <tr>
                        <td class="p-2 font-bold text-gray-800">${item.name}</td>
                        <td class="p-2 text-center bg-blue-50/40 text-blue-600 font-black">x${item.qty}</td>
                        <td class="p-2 text-left font-semibold">${item.price} ج.م</td>
                    </tr>
                `;
            });

            const modal = document.getElementById('detailsModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('.transform').classList.remove('scale-95');
            }, 10);
        }
        function deleteInvoice(id, invoiceNumber) {
    if (confirm(`هل أنت متأكد تماماً من إلغاء وحذف الفاتورة رقم (${invoiceNumber})؟\nسيتم إعادة جميع المواد الخام المستهلكة إلى المخزن تلقائياً.`)) {
        
        fetch(`/invoices/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload(); // تحديث الصفحة لإخفاء الفاتورة المحذوفة
            } else {
                alert(data.error || 'حدث خطأ أثناء محاولة الحذف.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('حدث خطأ في الاتصال بالخادم.');
        });
    }
}

        function closeDetailsModal() {
            const modal = document.getElementById('detailsModal');
            modal.classList.add('opacity-0');
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        // ==========================================
        // 🔧 العمليات الخاصة بمودال التعديل الجديد المصلح
        // ==========================================
        let currentEditInvoiceId = null;

        function openEditModal(id, invoiceNumber, discount, items) {
            currentEditInvoiceId = id;
            document.getElementById('editInvoiceNumber').innerText = invoiceNumber;
            document.getElementById('editDiscountInput').value = discount;

            const container = document.getElementById('editItemsContainer');
            container.innerHTML = '';

            // بناء صفوف الأصناف مع إضافة الـ menu_id في الـ dataset
            items.forEach((item, index) => {
                container.innerHTML += `
                    <div class="flex items-center justify-between bg-gray-50 p-2.5 rounded-xl border border-gray-100 gap-4">
                        <span class="font-bold text-gray-800 flex-1">${item.name}</span>
                        <div class="flex items-center gap-1.5 w-24">
                            <span class="text-gray-400 font-semibold">الكمية:</span>
                            <input type="number" 
                                   value="${item.qty}" 
                                   min="1" 
                                   data-menu-id="${item.menu_id}"
                                   class="edit-item-qty w-full text-center border border-gray-200 rounded-lg py-1 font-black text-blue-600 focus:outline-none focus:border-blue-500" 
                                   required>
                        </div>
                    </div>
                `;
            });

            // إظهار المودال بأنيميشن سلس
            const modal = document.getElementById('editModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.querySelector('.transform').classList.remove('scale-95');
            }, 10);
        }

        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.add('opacity-0');
            modal.querySelector('.transform').classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        // دالة إرسال التحديث للسيرفر (PUT Request)
        function submitInvoiceEdit(e) {
            e.preventDefault();
            
            const id = currentEditInvoiceId;
            const discount = document.getElementById('editDiscountInput').value;
            const saveBtn = document.getElementById('saveEditBtn');
            
            // تجميع وتجهيز مصفوفة العناصر المطلوبة في الكنترولر (menu_id & quantity)
            const items = [];
            const qtyInputs = document.querySelectorAll('.edit-item-qty');
            qtyInputs.forEach(input => {
                items.push({
                    menu_id: input.getAttribute('data-menu-id'),
                    quantity: parseInt(input.value)
                });
            });

            // تغيير حالة الزر لمنع الضغط المتكرر
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري حفظ التعديلات...';

            // إرسال طلب التعديل بصيغة PUT المتوافقة مع الـ Router
            fetch(`/invoices/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    items: items,
                    discount: discount
                })
            })
            .then(response => response.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.innerText = 'حفظ التعديلات الآن';
                
                if (data.success) {
                    alert(data.message || 'تم تحديث الفاتورة والمخزون بنجاح.');
                    closeEditModal();
                    window.location.reload(); // تحديث الصفحة لرؤية الأرقام والأسعار الجديدة
                } else {
                    alert(data.error || 'حدث خطأ أثناء حفظ التعديلات');
                }
            })
            .catch(error => {
                saveBtn.disabled = false;
                saveBtn.innerText = 'حفظ التعديلات الآن';
                console.error('Error:', error);
                alert('حدث خطأ في الاتصال بالخادم، تأكد من تسجيل الدخول وإعدادات الراوتر.');
            });
        }
    </script>
@endpush