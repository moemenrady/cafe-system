@extends('layouts.app')

@section('title', 'لوحة تحكم الكافيه')
@section('page_title', 'الرئيسية والمراقبة الحية')

@section('content')
<div class="space-y-6 animate-slide-in">
    
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex justify-between items-start hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1" data-ar="طلبات اليوم" data-en="Today's Orders">طلبات اليوم</p>
                <h3 class="text-xl font-black text-gray-900">142 <span class="text-xs font-normal text-gray-500" data-ar="طلب" data-en="Orders">طلب</span></h3>
                <p class="text-[10px] text-green-600 mt-1 font-medium"><i class="fa-solid fa-arrow-trend-up"></i> +8%</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-xs text-gray-700">
                <i class="fa-solid fa-mug-saucer text-cafePrimary text-sm"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex justify-between items-start hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1" data-ar="إجمالي المبيعات" data-en="Total Revenues">إجمالي المبيعات</p>
                <h3 class="text-xl font-black text-gray-900">12,450 <span class="text-xs font-medium text-gray-400">EGP</span></h3>
                <p class="text-[10px] text-gray-400 mt-1" data-ar="شامل القيمة المضافة" data-en="VAT Included">شامل القيمة المضافة</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-xs text-gray-700">
                <i class="fa-solid fa-money-bill-wave text-cafePrimary text-sm"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex justify-between items-start hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1" data-ar="نواقص المخزن" data-en="Stock Shortages">نواقص المخزن</p>
                <h3 class="text-xl font-black text-red-600">3 <span class="text-xs font-normal text-gray-400" data-ar="مواد" data-en="Items">مواد</span></h3>
                <p class="text-[10px] text-red-400 mt-1" data-ar="بحاجة لتوريد فوري" data-en="Needs Supply">بحاجة لتوريد فوري</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-red-50 flex items-center justify-center text-xs text-red-500">
                <i class="fa-solid fa-triangle-exclamation text-sm"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm flex justify-between items-start hover:shadow-md transition-all duration-200">
            <div>
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1" data-ar="طاقم العمل الحالي" data-en="Active Staff">طاقم العمل الحالي</p>
                <h3 class="text-xl font-black text-gray-900">5 <span class="text-xs font-normal text-gray-400" data-ar="موظفين" data-en="Staff">موظفين</span></h3>
                <p class="text-[10px] text-gray-400 mt-1" data-ar="الشيفت الحالي: صباحي" data-en="Current: Morning Shift">الشيفت الحالي: صباحي</p>
            </div>
            <div class="w-9 h-9 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-xs text-gray-700">
                <i class="fa-solid fa-user-clock text-cafePrimary text-sm"></i>
            </div>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden flex flex-col">
        <div class="p-4 border-b border-gray-50 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-gradient-to-r from-gray-50/50 to-transparent">
            <div class="flex items-center gap-2 bg-sidebar text-white px-3 py-1.5 rounded-xl font-bold text-[10px] w-max shadow-sm">
                <i class="fa-solid fa-circle text-[5px] text-green-400 animate-pulse"></i>
                <span data-ar="مراقبة الطلبات الحية" data-en="Live Orders Tracker">مراقبة الطلبات الحية</span>
            </div>
            <div class="relative w-full sm:w-72">
                <i class="fa-solid fa-magnifying-glass absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" data-ar-placeholder="البحث برقم الطاولة أو الطلب..." data-en-placeholder="Search by order or table..." placeholder="البحث برقم الطاولة أو الطلب..." class="w-full pr-9 pl-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-cafePrimary transition-colors duration-200">
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-right whitespace-nowrap">
                <thead class="text-[10px] text-gray-400 font-bold bg-gray-50/70 border-b border-gray-100 uppercase">
                    <tr>
                        <th class="px-5 py-3.5" data-ar="رقم الطلب" data-en="Order ID">رقم الطلب</th>
                        <th class="px-5 py-3.5" data-ar="الطاولة" data-en="Table / Captain">الطاولة</th>
                        <th class="px-5 py-3.5" data-ar="العناصر" data-en="Items Ordered">العناصر</th>
                        <th class="px-5 py-3.5" data-ar="السعر الإجمالي" data-en="Total Price">السعر الإجمالي</th>
                        <th class="px-5 py-3.5" data-ar="طريقة الدفع" data-en="Payment Method">طريقة الدفع</th>
                        <th class="px-5 py-3.5" data-ar="الحالة" data-en="Status">الحالة</th>
                        <th class="px-5 py-3.5 text-center" data-ar="خيارات" data-en="Actions">خيارات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <tr class="hover:bg-bodyBg/40 transition-colors duration-150">
                        <td class="px-5 py-4 font-medium text-gray-600">#CAF-4902</td>
                        <td class="px-5 py-4 font-bold text-gray-900" data-ar="طاولة 4 (تيك أواي)" data-en="Table 4 (Takeaway)">طاولة 4 (تيك أواي)</td>
                        <td class="px-5 py-4 text-gray-500">2x Spanish Latte (M) + 1x Croissant Cheese</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">240 EGP</td>
                        <td class="px-5 py-4"><span class="bg-green-50 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded border border-green-100" data-ar="كاش" data-en="Cash">كاش</span></td>
                        <td class="px-5 py-4"><span class="bg-yellow-50 text-yellow-600 text-[10px] font-bold px-2 py-0.5 rounded border border-yellow-100" data-ar="قيد التحضير" data-en="Preparing">قيد التحضير</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                    <tr class="hover:bg-bodyBg/40 transition-colors duration-150">
                        <td class="px-5 py-4 font-medium text-gray-600">#CAF-4903</td>
                        <td class="px-5 py-4 font-bold text-gray-900" data-ar="طاولة 12 (صالة)" data-en="Table 12 (Hall)">طاولة 12 (صالة)</td>
                        <td class="px-5 py-4 text-gray-500">1x Iced V60 (Ethiopia) + 1x San Sebastian Cake</td>
                        <td class="px-5 py-4 font-semibold text-gray-800">195 EGP</td>
                        <td class="px-5 py-4"><span class="bg-blue-50 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded border border-blue-100">InstaPay</span></td>
                        <td class="px-5 py-4"><span class="bg-green-50 text-green-700 text-[10px] font-bold px-2 py-0.5 rounded border border-green-100" data-ar="تم التوصيل" data-en="Served">تم التوصيل</span></td>
                        <td class="px-5 py-4 text-center text-gray-400 cursor-pointer hover:text-gray-700"><i class="fa-solid fa-ellipsis"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // كود إضافي مخصص لتبديل الـ Placeholders للبحث حسب اللغة الحالية تلقائياً دون التأثير على الأداء
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.querySelector('input[data-ar-placeholder]');
        const html = document.getElementById("htmlRoot");
        
        function updatePlaceholder() {
            if(input) {
                input.placeholder = html.lang === 'ar' ? input.dataset.arPlaceholder : input.dataset.enPlaceholder;
            }
        }
        
        updatePlaceholder();
        document.getElementById("languageBtn").addEventListener('click', () => {
            setTimeout(updatePlaceholder, 50);
        });
    });
</script>
@endpush