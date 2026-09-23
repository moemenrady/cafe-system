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

    {{-- ========== نافذة تنبيه تعذر اتصال الطابعة (Production Warning Modal) ========== --}}
    <div id="printerWarningModal"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden"
        onclick="hidePrinterWarningModal()">
        <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-md w-full mx-4 shadow-2xl border border-amber-200 animate-slide-in relative text-right" onclick="event.stopPropagation()">
            
            {{-- الأيقونة والعنوان --}}
            <div class="flex flex-col items-center text-center mb-4">
                <div class="w-16 h-16 bg-amber-50 border-2 border-amber-100 rounded-2xl flex items-center justify-center text-amber-500 text-2xl shadow-inner mb-3 relative">
                    <i class="fa-solid fa-print"></i>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-amber-500 text-white rounded-full flex items-center justify-center text-[10px] font-black border-2 border-white">!</span>
                </div>
                <h4 class="text-lg font-black text-gray-900 mb-1">تنبيه: الطابعة غير متصلة</h4>
                <p id="printerWarningMsg" class="text-xs font-semibold text-gray-600 leading-relaxed max-w-sm">تعذر إتمام الطلب: برنامج الطباعة غير متصل بالجهاز حالياً أو الطابعة غير متصلة بالشبكة.</p>
            </div>

            {{-- صندوق تفاصيل حالة الطابعات --}}
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 mb-4 space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-cash-register text-gray-400"></i> طابعة الكاشير:</span>
                    <span id="warnPrinterCashierStatus" class="px-2 py-0.5 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold">غير متصلة</span>
                </div>
                <div class="flex items-center justify-between text-xs font-bold text-gray-700">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-mug-hot text-gray-400"></i> طابعة الباريستا / المطبخ:</span>
                    <span id="warnPrinterBaristaStatus" class="px-2 py-0.5 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold">غير متصلة</span>
                </div>
                <div class="pt-2 border-t border-slate-200 text-[11px] text-amber-800 flex items-start gap-1.5 font-medium leading-normal">
                    <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 shrink-0"></i>
                    <span>إذا اخترت <b>المتابعة</b>، سيتم تسجيل الطلب واعتماده في النظام وتحديث المخزون، ولكن <b>لن تتم طباعة الفاتورة ورقياً</b>.</span>
                </div>
            </div>

            {{-- زرا الإجراءات --}}
            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" id="confirmForceSubmitBtn" onclick="confirmForceSubmitOrder()"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-3 rounded-xl text-xs sm:text-sm shadow-md shadow-amber-500/20 transition-all flex items-center justify-center gap-1.5 active:scale-95">
                    <i class="fa-solid fa-check"></i>
                    متابعة وحفظ الطلب
                </button>
                <button type="button" onclick="hidePrinterWarningModal()"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 px-3 rounded-xl text-xs sm:text-sm transition-all active:scale-95">
                    إلغاء والتراجع
                </button>
            </div>
        </div>
    </div>

    {{-- ========== نافذة إدارة الطاولة المشغولة (Active Table Modal) ========== --}}
    <div id="activeTableModal"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden"
        onclick="closeActiveTableModal()">
        <div class="bg-white rounded-3xl max-w-lg w-full mx-4 shadow-2xl border border-gray-100 overflow-hidden text-right flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
            
            {{-- Header --}}
            <div class="bg-amber-50 p-4 border-b border-amber-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <span class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-black text-lg shadow-sm">
                        <i class="fa-solid fa-chair"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="font-black text-gray-900 text-base sm:text-lg" id="atModalTableName">طاولة</h3>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900">مشغولة</span>
                        </div>
                        <p class="text-xs font-mono text-gray-500" id="atModalOrderNumber">#ORD-...</p>
                    </div>
                </div>
                <button type="button" onclick="closeActiveTableModal()" class="text-gray-400 hover:text-gray-600 text-xl p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            {{-- Metadata Info Bar --}}
            <div class="bg-gray-50/80 px-4 py-2 border-b border-gray-100 flex justify-between items-center text-xs text-gray-600 shrink-0">
                <div class="flex items-center gap-1.5">
                    <i class="fa-regular fa-clock text-gray-400"></i>
                    <span>وقت الفتح: <b id="atModalOpenedAt" class="font-mono text-gray-800">-</b></span>
                </div>
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-user-tie text-gray-400"></i>
                    <span>الموظف: <b id="atModalEmployee" class="text-gray-800">-</b></span>
                </div>
            </div>

            {{-- Body Scrollable --}}
            <div class="p-4 overflow-y-auto space-y-3.5 flex-1">
                
                {{-- قائمة الأصناف --}}
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <h4 class="text-xs font-bold text-gray-700 flex items-center gap-1.5">
                            <i class="fa-solid fa-utensils text-blue-500"></i>
                            أصناف الطلب:
                        </h4>
                        <span id="atModalItemsCount" class="text-[11px] font-bold text-gray-400">0 أصناف</span>
                    </div>
                    <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-xs">
                        <table class="w-full text-xs text-right">
                            <thead class="bg-gray-50 text-gray-500 font-bold border-b border-gray-100">
                                <tr>
                                    <th class="p-2">الصنف</th>
                                    <th class="p-2 text-center">الكمية</th>
                                    <th class="p-2">السعر</th>
                                    <th class="p-2 text-left">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody id="atModalItemsTable" class="divide-y divide-gray-50">
                                {{-- تُملأ عبر JS --}}
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- تفاصيل العميل --}}
                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-2.5 text-xs space-y-1.5">
                    <p class="font-bold text-gray-700 flex items-center gap-1.5">
                        <i class="fa-solid fa-user text-cafePrimary"></i>
                        بيانات العميل:
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="relative">
                            <input type="text" id="atModalCustomerPhone"
                                oninput="searchCustomer(this.value, 'modal')"
                                class="w-full bg-white px-2.5 py-1.5 rounded-xl border border-gray-200 text-xs outline-none focus:border-blue-400 font-mono"
                                placeholder="رقم الهاتف">
                            <div id="atModalCustomerSuggestions" class="absolute right-0 left-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-20 hidden max-h-32 overflow-y-auto"></div>
                        </div>
                        <input type="text" id="atModalCustomerName"
                            class="w-full bg-white px-2.5 py-1.5 rounded-xl border border-gray-200 text-xs outline-none focus:border-blue-400"
                            placeholder="اسم العميل">
                    </div>
                </div>

                {{-- الحساب المالي (Subtotal, Discount, VAT 14%, Grand Total) --}}
                <div class="bg-gray-50 border border-gray-200 rounded-2xl p-3 space-y-1.5 text-xs">
                    <div class="flex justify-between items-center text-gray-600">
                        <span>المجموع الفرعي:</span>
                        <span class="font-bold text-gray-800" id="atModalSubtotal">0.00 ج</span>
                    </div>
                    
                    {{-- تعديل الخصم --}}
                    <div class="flex justify-between items-center text-gray-600">
                        <span class="flex items-center gap-1">الخصم:</span>
                        <div class="flex items-center gap-1">
                            <input type="number" id="atModalDiscount" min="0" step="0.5" value="0"
                                oninput="recalcModalTotals()"
                                class="w-16 bg-white border border-gray-200 rounded-lg px-2 py-0.5 text-center font-bold text-red-500 outline-none text-xs">
                            <span class="text-[10px] text-gray-400">ج.م</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-gray-600">
                        <span>ضريبة القيمة المضافة (14%):</span>
                        <span class="font-bold text-amber-600" id="atModalVat">0.00 ج</span>
                    </div>

                    <div class="flex justify-between items-center border-t border-gray-200 pt-1.5">
                        <span class="text-sm font-black text-gray-900">الإجمالي النهائي:</span>
                        <span class="text-xl font-black text-emerald-600" id="atModalGrandTotal">0.00 ج</span>
                    </div>
                </div>

                {{-- طريقة الدفع للإغلاق --}}
                <div>
                    <p class="text-xs font-bold text-gray-600 mb-1">طريقة الدفع عند الإغلاق:</p>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" onclick="setModalPaymentMethod('cash')" id="modal_pm_cash"
                            class="modal-pm-btn bg-emerald-50 border border-emerald-300 text-emerald-700 font-bold text-xs py-1.5 rounded-xl transition-all">
                            💰 كاش
                        </button>
                        <button type="button" onclick="setModalPaymentMethod('InstaPay')" id="modal_pm_InstaPay"
                            class="modal-pm-btn bg-gray-50 border border-gray-200 text-gray-600 hover:bg-gray-100 font-bold text-xs py-1.5 rounded-xl transition-all">
                            📱 إنستا
                        </button>
                        <button type="button" onclick="setModalPaymentMethod('card')" id="modal_pm_card"
                            class="modal-pm-btn bg-gray-50 border border-gray-200 text-gray-600 hover:bg-gray-100 font-bold text-xs py-1.5 rounded-xl transition-all">
                            💳 كارد
                        </button>
                    </div>
                </div>
            </div>

            {{-- Actions Footer --}}
            <div class="p-3 bg-gray-50 border-t border-gray-100 grid grid-cols-3 gap-2 shrink-0">
                <button type="button" onclick="startAddonMode()"
                    class="bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold py-2.5 px-2 rounded-xl text-xs transition-all flex items-center justify-center gap-1 shadow-sm">
                    <i class="fa-solid fa-plus"></i>
                    إضافة أصناف
                </button>

                <button type="button" onclick="printActiveTableInvoice()" id="atModalPrintBtn"
                    class="bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-bold py-2.5 px-2 rounded-xl text-xs transition-all flex items-center justify-center gap-1 shadow-sm shadow-amber-500/20">
                    <i class="fa-solid fa-print"></i>
                    طباعة الفاتورة
                </button>

                <button type="button" onclick="closeActiveTable()" id="atModalCloseTableBtn"
                    class="bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-bold py-2.5 px-2 rounded-xl text-xs transition-all flex items-center justify-center gap-1 shadow-sm shadow-emerald-600/20">
                    <i class="fa-solid fa-check-double"></i>
                    إغلاق الطاولة
                </button>
            </div>
        </div>
    </div>

    {{-- ========== نافذة تفاصيل الطابعات للتشخيص ========== --}}
    <div id="printerDetailsModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center hidden"
        onclick="togglePrinterDetailsModal()">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full mx-4 shadow-2xl border border-gray-100 relative text-right" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-sm font-bold">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-gray-800">حالة نظام الطباعة</h4>
                        <p id="diagDeviceUuid" class="text-[11px] font-mono text-gray-400">الجهاز: pos-cashier-01</p>
                    </div>
                </div>
                <button type="button" onclick="togglePrinterDetailsModal()" class="text-gray-400 hover:text-gray-600 text-sm p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-2.5 mb-4">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="flex items-center gap-2">
                        <span id="diagAgentDot" class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                        <span class="text-xs font-bold text-gray-700">برنامج الطباعة (Agent):</span>
                    </div>
                    <span id="diagAgentStatus" class="text-xs font-bold text-gray-600">جاري الفحص...</span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="flex items-center gap-2">
                        <span id="diagCashierDot" class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                        <span class="text-xs font-bold text-gray-700">طابعة الكاشير:</span>
                    </div>
                    <span id="diagCashierStatus" class="text-xs font-bold text-gray-600">-</span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="flex items-center gap-2">
                        <span id="diagBaristaDot" class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                        <span class="text-xs font-bold text-gray-700">طابعة الباريستا / المطبخ:</span>
                    </div>
                    <span id="diagBaristaStatus" class="text-xs font-bold text-gray-600">-</span>
                </div>

                <div class="text-[11px] text-gray-400 text-left font-mono pt-1" id="diagLastSeen">
                    آخر نبضة: -
                </div>
            </div>

            <button type="button" onclick="fetchPrinterStatus(true)"
                class="w-full bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-2.5 rounded-xl text-xs transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-rotate"></i> فحص الحالة الآن
            </button>
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

            {{-- شريط البحث والفئات وحالة الطابعة --}}
            <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 space-y-3 sticky top-2 z-10">

                {{-- البحث ومؤشر الطابعة --}}
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
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

                    {{-- زر حالة الطابعة المباشر --}}
                    <button type="button" id="posPrinterStatusBtn" onclick="togglePrinterDetailsModal()"
                        class="shrink-0 flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-bold transition-all bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100"
                        title="انقر لعرض تفاصيل اتصال برنامج الطباعة">
                        <span id="posPrinterDot" class="w-2.5 h-2.5 rounded-full bg-gray-400 animate-pulse"></span>
                        <i class="fa-solid fa-print"></i>
                        <span id="posPrinterText" class="hidden sm:inline">فحص الطابعة...</span>
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
                        <div class="flex items-center justify-between mb-1.5">
                            <p class="text-xs font-bold text-gray-600 flex items-center gap-1.5">
                                <i class="fa-solid fa-chair text-cafePrimary"></i>
                                اختيار الطاولة <span class="text-red-500">*</span>
                            </p>
                            <button type="button" onclick="refreshTables()" class="text-[11px] text-blue-600 hover:text-blue-800 flex items-center gap-1 font-bold">
                                <i class="fa-solid fa-rotate text-[10px]"></i> تحديث
                            </button>
                        </div>
                        <div id="tablesGrid" class="grid grid-cols-3 gap-1.5 max-h-40 overflow-y-auto p-0.5">
                            {{-- يُملأ عبر JavaScript من البيانات المُمررة من الـ Controller --}}
                        </div>
                        <p id="tableError" class="hidden text-red-500 text-xs mt-1 flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            يجب اختيار طاولة متاحة للصالة
                        </p>
                        <p id="noTablesMsg" class="hidden text-xs text-gray-400 text-center py-2">
                            لا توجد طاولات نشطة. تواصل مع الإدارة.
                        </p>

                        {{-- بيانات العميل للصالة (اختياري) --}}
                        <div class="mt-2 pt-2 border-t border-gray-100 space-y-1">
                            <p class="text-[11px] font-bold text-gray-500">بيانات العميل (اختياري):</p>
                            <div class="flex gap-1.5">
                                <div class="relative flex-1">
                                    <input type="text" id="dine_customer_phone"
                                        oninput="searchCustomer(this.value, 'dine')"
                                        class="w-full bg-white px-2.5 py-1.5 rounded-xl border border-gray-200 text-xs outline-none focus:border-blue-400 font-mono"
                                        placeholder="هاتف العميل">
                                    <div id="dine_customer_suggestions" class="absolute right-0 left-0 top-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-20 hidden max-h-32 overflow-y-auto"></div>
                                </div>
                                <input type="text" id="dine_customer_name"
                                    class="w-1/2 bg-white px-2.5 py-1.5 rounded-xl border border-gray-200 text-xs outline-none focus:border-blue-400"
                                    placeholder="اسم العميل">
                            </div>
                        </div>
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

                {{-- تنبيه وضع إضافة الأصناف لطاولة مفتوحة --}}
                <div id="addonBanner" class="hidden mx-3 mt-1 mb-2 bg-blue-50 border border-blue-200 text-blue-700 px-3 py-2 rounded-xl text-xs font-bold flex justify-between items-center shrink-0">
                    <span class="flex items-center gap-1.5">
                        <i class="fa-solid fa-plus-circle text-blue-600"></i>
                        <span>إضافة أصناف إلى: <b id="addonTableName" class="text-blue-900"></b></span>
                    </span>
                    <button type="button" onclick="cancelAddonMode()" class="text-red-500 hover:text-red-700 bg-white px-2 py-0.5 rounded-lg border border-red-200 text-[10px]">
                        إلغاء
                    </button>
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
                        <div class="flex justify-between items-center text-xs text-gray-500">
                            <span id="vatLabel">ضريبة القيمة المضافة (0%):</span>
                            <span id="vatDisplay" class="font-bold text-amber-600">0.00 ج</span>
                        </div>
                        <div class="flex justify-between items-center border-t border-gray-200 pt-1.5 mt-1">
                            <span class="text-sm font-black text-gray-800">الإجمالي النهائي:</span>
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

    // حالة إدارة الطاولات المفتوحة وإضافة الأصناف والعملاء
    let addonOrderId = null;
    let addonTableId = null;
    let activeModalOrder = null;
    let modalPaymentMethod = 'cash';
    let customerSearchTimer = null;

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

        updateTotals();
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

            if (isOccupied) {
                // الطاولة المشغولة: كليك لفتح تفاصيل الطلب الحالي (PHASE 2 & 3)
                btn.className = 'table-btn rounded-xl py-2 px-1 text-center transition-all border text-xs font-bold bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100 hover:border-amber-400 cursor-pointer shadow-xs active:scale-95';
                btn.title = `طاولة مشغولة: ${table.name} (اضغط لعرض الطلب والحساب)`;
                btn.innerHTML = `
                    <div class="font-black text-xs leading-tight text-amber-950">${table.name}</div>
                    <div class="text-[9px] font-bold text-amber-700 mt-0.5 flex items-center justify-center gap-0.5">
                        <i class="fa-solid fa-clipboard-list text-[8px]"></i>
                        <span>مشغولة</span>
                    </div>
                    ${table.order?.total ? `<div class="text-[9px] font-black text-emerald-700 mt-0.5">${Number(table.order.total).toFixed(0)}ج</div>` : ''}
                `;
                btn.onclick = () => openActiveTableModal(table.id);
            } else {
                // الطاولة الفارغة المتاحة: كليك لاختيارها لفتح طلب جديد
                btn.className = [
                    'table-btn rounded-xl py-2 px-1 text-center transition-all border text-xs font-bold cursor-pointer active:scale-95',
                    isSelected
                        ? 'bg-blue-600 text-white border-blue-600 shadow-md shadow-blue-500/30 ring-2 ring-blue-300'
                        : 'bg-white text-gray-700 border-gray-200 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700',
                ].join(' ');
                btn.title = `طاولة متاحة: ${table.name}`;
                btn.innerHTML = `
                    <div class="font-black text-xs leading-tight">${table.name}</div>
                    ${table.capacity ? `<div class="text-[9px] opacity-60 mt-0.5">${table.capacity}👤</div>` : ''}
                    <div class="text-[9px] text-emerald-600 font-bold mt-0.5">متاحة</div>
                `;
                btn.onclick = () => selectTable(table.id);
            }

            grid.appendChild(btn);
        });
    }

    function selectTable(id) {
        selectedTableId = id;
        document.getElementById('tableError').classList.add('hidden');
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
        const taxable = Math.max(0, subtotal - discount);

        // الصالة 14% - التيك أواي 0% - الديليفري 0%
        const isDineIn = (currentOrderType === 'dine_in');
        const vatRate = isDineIn ? 0.14 : 0.0;
        const vat = Math.round(taxable * vatRate * 100) / 100;
        const total = Math.round((taxable + vat) * 100) / 100;

        document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2) + ' ج';
        document.getElementById('discountDisplay').textContent = '- ' + discount.toFixed(2) + ' ج';
        const vatLabel = document.getElementById('vatLabel');
        if (vatLabel) {
            vatLabel.textContent = isDineIn ? 'ضريبة القيمة المضافة (14%):' : 'ضريبة القيمة المضافة (0%):';
        }
        const vatEl = document.getElementById('vatDisplay');
        if (vatEl) vatEl.textContent = vat.toFixed(2) + ' ج';
        document.getElementById('grandTotal').textContent = total.toFixed(2) + ' ج';
    }

    function updateSubmitButton() {
        const hasItems = Object.keys(cart).length > 0;
        const btn = document.getElementById('submitBtn');
        btn.disabled = !hasItems;
    }

    renderCart();

    // ========== إرسال الطلب ==========
    function submitOrder(force = false) {
        if (Object.keys(cart).length === 0) return;

        // في حال كنا في وضع إضافة أصناف لطاولة مفتوحة
        if (addonOrderId) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin ml-1"></i> جاري إضافة الأصناف...';

            const payload = {
                items: Object.values(cart).map(item => ({
                    menu_id: item.id,
                    quantity: item.quantity,
                })),
                force: force,
                device_uuid: 'pos-cashier-01',
            };

            fetch(`/orders/${addonOrderId}/add-items`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Device-UUID': 'pos-cashier-01',
                },
                body: JSON.stringify(payload),
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) throw data;
                return data;
            })
            .then(data => {
                hidePrinterWarningModal();
                const savedTableId = addonTableId;
                cancelAddonMode();
                refreshTables();
                if (savedTableId) openActiveTableModal(savedTableId);
            })
            .catch(err => {
                if (err?.printer_warning) {
                    showPrinterWarningModal(err);
                    return;
                }
                const msg = err?.message || 'حدث خطأ أثناء إضافة الأصناف للطاولة.';
                showError(msg);
            })
            .finally(() => {
                btn.disabled = false;
                updateSubmitButton();
            });
            return;
        }

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
            force: force,
            device_uuid: 'pos-cashier-01',
            items: Object.values(cart).map(item => ({
                menu_id: item.id,
                quantity: item.quantity,
            })),
        };

        if (currentOrderType === 'dine_in') {
            payload.table_id = selectedTableId;
            payload.customer_phone = document.getElementById('dine_customer_phone')?.value.trim() || null;
            payload.customer_name  = document.getElementById('dine_customer_name')?.value.trim() || null;
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
                'X-Device-UUID': 'pos-cashier-01',
            },
            body: JSON.stringify(payload),
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw data;
            return data;
        })
        .then(data => {
            hidePrinterWarningModal();

            // عرض نافذة النجاح
            document.getElementById('successOrderNumber').textContent = 'رقم الطلب: ' + (data.data?.order_number || '');
            showSuccessModal();

            // تفريغ كل شيء
            resetPOS();

            // تحديث حالة الطاولات بعد إنشاء الأوردر
            refreshTables();
        })
        .catch(err => {
            if (err?.printer_warning) {
                showPrinterWarningModal(err);
                return;
            }

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

    // ========== إدارة نافذة الطاولة المشغولة (Active Table Modal) ==========
    async function openActiveTableModal(tableId) {
        const table = tablesData.find(t => t.id === tableId);
        if (!table) return;

        let order = table.order;

        if (!order || !order.items) {
            try {
                const res = await fetch(`/orders/table/${tableId}/active`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                if (json.success && json.data) {
                    order = json.data;
                } else {
                    showError(json.message || 'تعذر جلب تفاصيل طلب الطاولة.');
                    return;
                }
            } catch (e) {
                showError('تعذر الاتصال بالسيرفر لجلب بيانات الطاولة.');
                return;
            }
        }

        activeModalOrder = order;
        activeModalOrder.table_id = table.id;
        activeModalOrder.table_name = table.name;

        // ملء عناصر المودال
        document.getElementById('atModalTableName').textContent = table.name;
        document.getElementById('atModalOrderNumber').textContent = `#${order.order_number || order.id}`;
        document.getElementById('atModalOpenedAt').textContent = order.opened_at || (order.created_at ? order.created_at.substring(0, 16) : '-');
        document.getElementById('atModalEmployee').textContent = order.employee || order.creator?.name || 'الكاشير';

        // الأصناف
        const items = order.items || [];
        document.getElementById('atModalItemsCount').textContent = `${items.length} أصناف`;
        const tbody = document.getElementById('atModalItemsTable');
        tbody.innerHTML = '';

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="p-3 text-center text-gray-400">لا توجد أصناف في الطلب.</td></tr>';
        } else {
            items.forEach(it => {
                const tr = document.createElement('tr');
                const name = it.name || it.menu?.name || 'صنف';
                const total = Number(it.total || (it.price * it.quantity)).toFixed(2);
                tr.innerHTML = `
                    <td class="p-2 font-bold text-gray-800">${name} ${it.notes ? `<span class="block text-[10px] text-gray-400">(${it.notes})</span>` : ''}</td>
                    <td class="p-2 text-center font-bold text-blue-600">${it.quantity}</td>
                    <td class="p-2 text-gray-600">${Number(it.price).toFixed(2)} ج</td>
                    <td class="p-2 text-left font-bold text-gray-900">${total} ج</td>
                `;
                tbody.appendChild(tr);
            });
        }

        // العميل
        document.getElementById('atModalCustomerName').value = order.customer_name || order.customer?.name || '';
        document.getElementById('atModalCustomerPhone').value = order.customer_phone || order.customer?.phone || order.phone || '';

        // الخصم
        document.getElementById('atModalDiscount').value = Number(order.discount || 0);

        // الحسابات المالية
        recalcModalTotals();

        // طريقة الدفع
        setModalPaymentMethod(order.invoice?.payment_method || 'cash');

        // إظهار المودال
        document.getElementById('activeTableModal').classList.remove('hidden');
    }

    function closeActiveTableModal() {
        document.getElementById('activeTableModal').classList.add('hidden');
        activeModalOrder = null;
    }

    function recalcModalTotals() {
        if (!activeModalOrder) return;
        const subtotal = Number(activeModalOrder.subtotal || 0);
        const discountInput = parseFloat(document.getElementById('atModalDiscount').value) || 0;
        const discount = Math.min(subtotal, Math.max(0, discountInput));
        const taxable = Math.max(0, subtotal - discount);
        const isDineIn = (!activeModalOrder.type || activeModalOrder.type === 'dine_in');
        const vatRate = isDineIn ? 0.14 : 0.0;
        const vat = Math.round(taxable * vatRate * 100) / 100;
        const grandTotal = Math.round((taxable + vat) * 100) / 100;

        document.getElementById('atModalSubtotal').textContent = subtotal.toFixed(2) + ' ج';
        document.getElementById('atModalVat').textContent = vat.toFixed(2) + ' ج';
        document.getElementById('atModalGrandTotal').textContent = grandTotal.toFixed(2) + ' ج';
    }

    function setModalPaymentMethod(method) {
        modalPaymentMethod = method;
        document.querySelectorAll('.modal-pm-btn').forEach(btn => {
            btn.classList.remove('bg-emerald-50', 'border-emerald-300', 'text-emerald-700');
            btn.classList.add('bg-gray-50', 'border-gray-200', 'text-gray-600');
        });
        const active = document.getElementById(`modal_pm_${method}`);
        if (active) {
            active.classList.remove('bg-gray-50', 'border-gray-200', 'text-gray-600');
            active.classList.add('bg-emerald-50', 'border-emerald-300', 'text-emerald-700');
        }
    }

    // 1. طباعة الفاتورة دون إغلاق الطاولة
    async function printActiveTableInvoice() {
        if (!activeModalOrder) return;
        const btn = document.getElementById('atModalPrintBtn');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري الإرسال...';

        try {
            const res = await fetch(`/orders/${activeModalOrder.id}/print-invoice`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Device-UUID': 'pos-cashier-01'
                },
                body: JSON.stringify({ force: false })
            });
            const data = await res.json();
            if (!res.ok) throw data;

            showError('تم إرسال أمر طباعة الفاتورة إلى الطابعة بنجاح.');
            const toast = document.getElementById('errorToast');
            toast.className = toast.className.replace('bg-red-600', 'bg-emerald-600');
            setTimeout(() => {
                toast.className = toast.className.replace('bg-emerald-600', 'bg-red-600');
            }, 3000);
        } catch (err) {
            if (err?.printer_warning) {
                showPrinterWarningModal(err);
                return;
            }
            showError(err?.message || 'تعذر إرسال أمر طباعة الفاتورة.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    // 2. إغلاق الطاولة وإتمام التحصيل
    async function closeActiveTable() {
        if (!activeModalOrder) return;

        if (!confirm(`هل أنت متأكد من إغلاق طاولة (${activeModalOrder.table_name}) وتحصيل الحساب؟`)) {
            return;
        }

        const btn = document.getElementById('atModalCloseTableBtn');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> جاري الإغلاق...';

        const payload = {
            payment_method: modalPaymentMethod,
            customer_name: document.getElementById('atModalCustomerName').value.trim() || null,
            customer_phone: document.getElementById('atModalCustomerPhone').value.trim() || null,
            discount: parseFloat(document.getElementById('atModalDiscount').value) || 0,
            force: false,
            device_uuid: 'pos-cashier-01'
        };

        try {
            const res = await fetch(`/orders/${activeModalOrder.id}/close-table`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'X-Device-UUID': 'pos-cashier-01'
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok) throw data;

            closeActiveTableModal();
            refreshTables();

            document.getElementById('successOrderNumber').textContent = 'تم إغلاق طلب: ' + (data.data?.order_number || '');
            showSuccessModal();
        } catch (err) {
            if (err?.printer_warning) {
                showPrinterWarningModal(err);
                return;
            }
            showError(err?.message || 'تعذر إغلاق الطاولة.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    // 3. وضع إضافة الأصناف لطاولة مفتوحة
    function startAddonMode() {
        if (!activeModalOrder) return;

        addonOrderId = activeModalOrder.id;
        addonTableId = activeModalOrder.table_id;
        const tableName = activeModalOrder.table_name;

        closeActiveTableModal();

        // إفراغ سلة الـ POS لاستقبال الأصناف الإضافية الجديدة فقط
        cart = {};
        renderCart();

        document.getElementById('addonTableName').textContent = tableName;
        document.getElementById('addonBanner').classList.remove('hidden');

        // ضبط نوع الطلب كصالة
        setOrderType('dine_in');
        selectedTableId = addonTableId;

        // تحديث زر الإرسال
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fa-solid fa-plus ml-1"></i> تأكيد إضافة الأصناف للطاولة';
    }

    function cancelAddonMode() {
        addonOrderId = null;
        addonTableId = null;
        document.getElementById('addonBanner').classList.add('hidden');
        cart = {};
        renderCart();
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.innerHTML = '<i class="fa-solid fa-cash-register ml-1"></i> إتمام الطلب';
        updateSubmitButton();
    }

    // 4. البحث التلقائي عن العملاء
    function searchCustomer(query, context) {
        clearTimeout(customerSearchTimer);
        const container = context === 'modal' 
            ? document.getElementById('atModalCustomerSuggestions') 
            : document.getElementById('dine_customer_suggestions');

        if (!container) return;
        query = query.trim();
        if (query.length < 3) {
            container.classList.add('hidden');
            return;
        }

        customerSearchTimer = setTimeout(async () => {
            try {
                const res = await fetch(`/customers/search?phone=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const json = await res.json();
                const customers = json.data || [];

                if (customers.length === 0) {
                    container.classList.add('hidden');
                    return;
                }

                container.innerHTML = '';
                customers.forEach(c => {
                    const item = document.createElement('div');
                    item.className = 'px-3 py-1.5 hover:bg-blue-50 cursor-pointer flex justify-between items-center text-xs border-b border-gray-50 last:border-none';
                    item.innerHTML = `
                        <span class="font-bold text-gray-800">${c.name}</span>
                        <span class="font-mono text-gray-400 text-[11px]">${c.phone || ''}</span>
                    `;
                    item.onclick = () => {
                        if (context === 'modal') {
                            document.getElementById('atModalCustomerName').value = c.name;
                            document.getElementById('atModalCustomerPhone').value = c.phone || '';
                        } else {
                            document.getElementById('dine_customer_name').value = c.name;
                            document.getElementById('dine_customer_phone').value = c.phone || '';
                        }
                        container.classList.add('hidden');
                    };
                    container.appendChild(item);
                });
                container.classList.remove('hidden');
            } catch (e) {
                container.classList.add('hidden');
            }
        }, 200);
    }

    // ========== إدارة نافذة تحذير الطابعة ==========
    function showPrinterWarningModal(err) {
        document.getElementById('printerWarningMsg').textContent = err?.message || 'تعذر الاتصال ببرنامج الطباعة أو الطابعة غير متصلة بالشبكة.';

        const cashierEl = document.getElementById('warnPrinterCashierStatus');
        const baristaEl = document.getElementById('warnPrinterBaristaStatus');
        const printers = err?.printers;

        if (printers?.cashier) {
            const isOnline = printers.cashier.status === 'online';
            cashierEl.textContent = isOnline ? 'متصلة (' + (printers.cashier.latency_ms ?? 0) + 'ms)' : 'غير متصلة';
            cashierEl.className = isOnline 
                ? 'px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700 text-[11px] font-bold' 
                : 'px-2 py-0.5 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold';
        } else {
            cashierEl.textContent = 'برنامج الطباعة متوقف';
            cashierEl.className = 'px-2 py-0.5 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold';
        }

        if (printers?.barista) {
            const isOnline = printers.barista.status === 'online';
            baristaEl.textContent = isOnline ? 'متصلة (' + (printers.barista.latency_ms ?? 0) + 'ms)' : 'غير متصلة';
            baristaEl.className = isOnline 
                ? 'px-2 py-0.5 rounded-lg bg-emerald-100 text-emerald-700 text-[11px] font-bold' 
                : 'px-2 py-0.5 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold';
        } else {
            baristaEl.textContent = 'برنامج الطباعة متوقف';
            baristaEl.className = 'px-2 py-0.5 rounded-lg bg-red-100 text-red-700 text-[11px] font-bold';
        }

        document.getElementById('printerWarningModal').classList.remove('hidden');
    }

    function hidePrinterWarningModal() {
        document.getElementById('printerWarningModal').classList.add('hidden');
    }

    function confirmForceSubmitOrder() {
        hidePrinterWarningModal();
        submitOrder(true);
    }

    // ========== فحص وتشخيص حالة الطابعة المباشر ==========
    let cachedPrinterHealth = null;

    async function fetchPrinterStatus(isManual = false) {
        const btn = document.getElementById('posPrinterStatusBtn');
        const dot = document.getElementById('posPrinterDot');
        const text = document.getElementById('posPrinterText');

        if (isManual && text) {
            text.textContent = 'جاري الفحص...';
        }

        try {
            const res = await fetch('/api/pos/printer-status?device_uuid=pos-cashier-01', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            cachedPrinterHealth = data;

            if (data.connected && data.is_ready) {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50';
                btn.className = 'shrink-0 flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-bold transition-all bg-emerald-50/80 border-emerald-200 text-emerald-700 hover:bg-emerald-100/70';
                text.textContent = 'الطابعات جاهزة';
            } else if (data.connected && !data.is_ready) {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse';
                btn.className = 'shrink-0 flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-bold transition-all bg-amber-50/80 border-amber-200 text-amber-700 hover:bg-amber-100/70';
                text.textContent = 'تنبيه طابعة';
            } else {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-red-500';
                btn.className = 'shrink-0 flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-bold transition-all bg-red-50/80 border-red-200 text-red-700 hover:bg-red-100/70';
                text.textContent = 'الطابعة غير متصلة';
            }

            updatePrinterDetailsUI(data);
        } catch (e) {
            if (dot && btn && text) {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-400';
                btn.className = 'shrink-0 flex items-center gap-2 px-3 py-2.5 rounded-xl border text-xs font-bold transition-all bg-gray-50 border-gray-200 text-gray-600';
                text.textContent = 'تعذر الاتصال';
            }
        }
    }

    function togglePrinterDetailsModal() {
        const modal = document.getElementById('printerDetailsModal');
        const isHidden = modal.classList.contains('hidden');
        if (isHidden) {
            modal.classList.remove('hidden');
            fetchPrinterStatus(false);
        } else {
            modal.classList.add('hidden');
        }
    }

    function updatePrinterDetailsUI(data) {
        if (!data) return;
        const deviceEl = document.getElementById('diagDeviceUuid');
        if (deviceEl) deviceEl.textContent = 'الجهاز: ' + (data.device_uuid || 'pos-cashier-01');
        
        const agentStatus = document.getElementById('diagAgentStatus');
        const agentDot = document.getElementById('diagAgentDot');
        if (agentStatus && agentDot) {
            if (data.connected) {
                agentStatus.textContent = 'متصل (جاهز)';
                agentStatus.className = 'text-xs font-bold text-emerald-600';
                agentDot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500';
            } else {
                agentStatus.textContent = 'غير متصل';
                agentStatus.className = 'text-xs font-bold text-red-600';
                agentDot.className = 'w-2.5 h-2.5 rounded-full bg-red-500';
            }
        }

        const cashierStatus = document.getElementById('diagCashierStatus');
        const cashierDot = document.getElementById('diagCashierDot');
        if (cashierStatus && cashierDot) {
            if (data.printers?.cashier) {
                const isUp = data.printers.cashier.status === 'online';
                cashierStatus.textContent = isUp ? `متصلة (${data.printers.cashier.latency_ms ?? 0}ms)` : 'غير متصلة';
                cashierStatus.className = isUp ? 'text-xs font-bold text-emerald-600' : 'text-xs font-bold text-red-600';
                cashierDot.className = isUp ? 'w-2.5 h-2.5 rounded-full bg-emerald-500' : 'w-2.5 h-2.5 rounded-full bg-red-500';
            } else {
                cashierStatus.textContent = 'غير معرفة';
                cashierStatus.className = 'text-xs font-bold text-gray-400';
                cashierDot.className = 'w-2.5 h-2.5 rounded-full bg-gray-300';
            }
        }

        const baristaStatus = document.getElementById('diagBaristaStatus');
        const baristaDot = document.getElementById('diagBaristaDot');
        if (baristaStatus && baristaDot) {
            if (data.printers?.barista) {
                const isUp = data.printers.barista.status === 'online';
                baristaStatus.textContent = isUp ? `متصلة (${data.printers.barista.latency_ms ?? 0}ms)` : 'غير متصلة';
                baristaStatus.className = isUp ? 'text-xs font-bold text-emerald-600' : 'text-xs font-bold text-red-600';
                baristaDot.className = isUp ? 'w-2.5 h-2.5 rounded-full bg-emerald-500' : 'w-2.5 h-2.5 rounded-full bg-red-500';
            } else {
                baristaStatus.textContent = 'غير معرفة';
                baristaStatus.className = 'text-xs font-bold text-gray-400';
                baristaDot.className = 'w-2.5 h-2.5 rounded-full bg-gray-300';
            }
        }

        const lastSeenEl = document.getElementById('diagLastSeen');
        if (lastSeenEl) {
            lastSeenEl.textContent = data.last_seen 
                ? 'آخر نبضة: ' + new Date(data.last_seen).toLocaleTimeString('ar-EG')
                : 'آخر نبضة: لا توجد بيانات';
        }
    }

    // بدء المراقبة الدورية لحالة الطابعة
    fetchPrinterStatus();
    setInterval(() => fetchPrinterStatus(), 20000);

    function resetPOS() {
        cart = {};
        selectedTableId = null;
        if (addonOrderId) cancelAddonMode();
        renderCart();
        document.querySelectorAll('[id^="badge-"]').forEach(b => b.classList.add('hidden'));
        document.getElementById('orderNotes').value = '';
        document.getElementById('discountInput').value = '0';
        document.getElementById('customer_phone').value = '';
        if (document.getElementById('dine_customer_phone')) document.getElementById('dine_customer_phone').value = '';
        if (document.getElementById('dine_customer_name')) document.getElementById('dine_customer_name').value = '';
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
            if (!document.getElementById('printerWarningModal').classList.contains('hidden')) {
                hidePrinterWarningModal();
                return;
            }
            if (!document.getElementById('printerDetailsModal').classList.contains('hidden')) {
                togglePrinterDetailsModal();
                return;
            }
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
