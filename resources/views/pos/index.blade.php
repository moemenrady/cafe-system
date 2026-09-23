@extends('layouts.app')

@section('page_title', 'شاشة الكاشير - POS')

@push('styles')
<style>
    /* تطبيق ارتفاع كامل لشاشة الكاشير داخل إطار النظام */
    body.pos-page {
        overflow: hidden !important;
    }

    body.pos-page main > div.overflow-y-auto {
        padding: 0.5rem !important;
        overflow: hidden !important;
        display: flex;
        flex-direction: column;
        height: calc(100vh - 65px) !important;
        max-height: calc(100vh - 65px) !important;
    }

    @media (min-width: 768px) {
        body.pos-page main > div.overflow-y-auto {
            padding: 0.75rem !important;
        }
    }

    /* وضع الشاشة الكاملة (Fullscreen Mode) - إخفاء الهيدر والسلايدر */
    body.pos-fullscreen #sidebar,
    body.pos-fullscreen #mobileOverlay,
    body.pos-fullscreen main > header {
        display: none !important;
    }

    body.pos-fullscreen main {
        width: 100vw !important;
        height: 100vh !important;
        max-width: 100vw !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    body.pos-fullscreen main > div.overflow-y-auto {
        padding: 0.5rem !important;
        margin: 0 !important;
        height: 100vh !important;
        max-height: 100vh !important;
        border-radius: 0 !important;
    }

    /* شريط التمرير المخصص اللطيف */
    .pos-scrollbar::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }
    .pos-scrollbar::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 8px;
    }
    .pos-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 8px;
    }
    .pos-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* تأثيرات البطاقات */
    .card-touch {
        user-select: none;
        -webkit-tap-highlight-color: transparent;
    }
</style>
@endpush

@section('content')
<div id="posMainContainer" class="flex flex-col h-full w-full select-none" dir="rtl">

    {{-- ==================== 1. شريط الأدوات العلوي للكاشير ==================== --}}
    <header class="bg-white rounded-2xl border border-gray-200/80 shadow-xs px-3 py-2 sm:px-4 sm:py-2.5 mb-2 shrink-0 flex items-center justify-between gap-2 sm:gap-4 z-20">
        {{-- الجانب الأيمن: الشعار واسم الكاشير والتوقيت --}}
        <div class="flex items-center gap-3 shrink-0">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-center text-lg shadow-sm shadow-blue-500/30">
                <i class="fa-solid fa-cash-register"></i>
            </div>
            <div class="hidden sm:block">
                <div class="flex items-center gap-2">
                    <h2 class="text-sm font-black text-gray-800 leading-tight">شاشة البيع السريع</h2>
                    <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold border border-blue-100">
                        {{ auth()->user()->name ?? 'كاشير' }}
                    </span>
                </div>
                <div class="text-[11px] font-mono text-gray-500 flex items-center gap-1.5 mt-0.5">
                    <i class="fa-regular fa-clock text-[10px] text-gray-400"></i>
                    <span id="posLiveClock">--:--:--</span>
                </div>
            </div>
        </div>

        {{-- المنتصف: مربع البحث السريع --}}
        <div class="relative flex-1 max-w-lg">
            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" id="searchInput"
                class="w-full bg-gray-50 hover:bg-gray-100/70 focus:bg-white text-gray-800 pr-9 pl-16 py-2 rounded-xl border border-gray-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-hidden font-medium text-xs sm:text-sm transition-all placeholder:text-gray-400"
                placeholder="ابحث عن صنف بالاسم أو الرمز... (F2 أو /)">
            <div class="absolute inset-y-0 left-0 flex items-center pl-2 gap-1">
                <button id="clearSearch" onclick="clearSearch()"
                    class="hidden w-6 h-6 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-200/60 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
                <kbd class="hidden md:inline-block px-1.5 py-0.5 text-[10px] font-mono font-bold text-gray-400 bg-gray-200/80 rounded border border-gray-300">F2</kbd>
            </div>
        </div>

        {{-- الجانب الأيسر: زر ملء الشاشة، حالة الطابعة، والأصوات --}}
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
            {{-- زر فحص وحالة الطابعة المباشر --}}
            <button type="button" id="posPrinterStatusBtn" onclick="togglePrinterDetailsModal()"
                class="flex items-center gap-2 px-2.5 sm:px-3 py-2 rounded-xl border text-xs font-bold transition-all bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 active:scale-95"
                title="حالة الطابعات وبرنامج الطباعة">
                <span id="posPrinterDot" class="w-2.5 h-2.5 rounded-full bg-gray-400 animate-pulse"></span>
                <i class="fa-solid fa-print text-xs"></i>
                <span id="posPrinterText" class="hidden md:inline text-[11px]">الطابعة...</span>
            </button>

            {{-- زر تبديل ملء الشاشة (Fullscreen Toggle) --}}
            <button type="button" id="fullscreenToggleBtn" onclick="togglePosFullscreen()"
                class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-indigo-200 bg-indigo-50/80 hover:bg-indigo-100 text-indigo-700 font-bold text-xs transition-all active:scale-95 shadow-xs shadow-indigo-500/10"
                title="تفعيل / إلغاء ملء الشاشة بدون هيدر أو سلايدر (F11)">
                <i id="fullscreenIcon" class="fa-solid fa-expand text-xs"></i>
                <span id="fullscreenText" class="hidden sm:inline">شاشة كاملة</span>
            </button>

            {{-- زر كتم / تفعيل الصوت للكاشير --}}
            <button type="button" id="soundToggleBtn" onclick="toggleSound()"
                class="w-9 h-9 rounded-xl border border-gray-200 bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center text-xs transition-all active:scale-95"
                title="صوت النقر والتأكيد">
                <i id="soundIcon" class="fa-solid fa-volume-high"></i>
            </button>
        </div>
    </header>

    {{-- ==================== 2. منطقة العمل الرئيسية (منتجات + سلة) ==================== --}}
    <div class="flex-1 flex flex-col lg:flex-row gap-2.5 sm:gap-3 overflow-hidden min-h-0">

        {{-- ==================== الجانب الأيمن / الأوسع: كتالوج المنتجات ==================== --}}
        <div class="flex-1 flex flex-col min-h-0 bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
            
            {{-- شريط تصنيفات المنتجات (Categories Pill Bar) --}}
            <div class="p-2 sm:p-2.5 border-b border-gray-100 bg-slate-50/70 shrink-0">
                <div class="flex items-center gap-1.5 overflow-x-auto pos-scrollbar pb-0.5" id="categoriesBar">
                    <button type="button" onclick="selectCategory('all')" id="tab-all"
                        class="category-tab shrink-0 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all bg-blue-600 text-white shadow-xs shadow-blue-500/30 flex items-center gap-1.5">
                        <i class="fa-solid fa-layer-group text-[11px]"></i>
                        <span>الكل</span>
                        <span class="bg-white/20 text-white text-[10px] font-black px-1.5 py-0.2 rounded-md">
                            {{ $menus->count() }}
                        </span>
                    </button>
                    @foreach($categories as $category)
                        <button type="button" onclick="selectCategory('{{ $category->id }}')"
                            id="tab-{{ $category->id }}"
                            class="category-tab shrink-0 px-3 py-1.5 rounded-xl text-xs font-bold transition-all bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 hover:border-gray-300 flex items-center gap-1.5">
                            <span>{{ $category->name }}</span>
                            <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-1.5 py-0.2 rounded-md">
                                {{ $menus->where('category_id', $category->id)->count() }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- شبكة بطاقات المنتجات القابلة للتمرير --}}
            <div class="flex-1 overflow-y-auto p-2.5 sm:p-3 pos-scrollbar min-h-0">
                <div id="menuGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-5 gap-2.5">
                    @foreach ($menus as $menu)
                        <button type="button"
                            onclick='addItem("{{ $menu->id }}", @json($menu->name), {{ $menu->price }})'
                            data-id="{{ $menu->id }}"
                            data-name="{{ $menu->name }}"
                            data-category="{{ $menu->category_id }}"
                            class="menu-item-card card-touch bg-white hover:bg-blue-50/30 border border-gray-200/80 hover:border-blue-400 rounded-2xl flex flex-col overflow-hidden group transition-all duration-150 active:scale-95 shadow-xs hover:shadow-md relative text-right">

                            {{-- الصورة أو أيقونة بديلة أنيقة --}}
                            @if ($menu->image_url)
                                <div class="w-full h-20 sm:h-24 bg-gray-100 overflow-hidden shrink-0 relative">
                                    <img src="{{ $menu->image_url }}"
                                        alt="{{ $menu->name }}"
                                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                                        loading="lazy"
                                        onerror="this.style.display='none'">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent pointer-events-none"></div>
                                    <span class="absolute bottom-1.5 right-2 text-white font-black text-xs drop-shadow-md">
                                        {{ number_format($menu->price, 2) }} ج
                                    </span>
                                </div>
                                <div class="p-2 sm:p-2.5 flex-1 flex flex-col justify-between">
                                    <span class="font-bold text-gray-800 text-xs sm:text-sm line-clamp-2 group-hover:text-blue-600 transition-colors">
                                        {{ $menu->name }}
                                    </span>
                                    <span class="text-[11px] text-gray-400 mt-1 font-medium">
                                        {{ $menu->category->name ?? '' }}
                                    </span>
                                </div>
                            @else
                                <div class="p-3 flex-1 flex flex-col justify-between min-h-[95px] sm:min-h-[110px]">
                                    <div class="flex items-start justify-between gap-1">
                                        <span class="font-bold text-gray-800 text-xs sm:text-sm line-clamp-2 group-hover:text-blue-700 transition-colors leading-snug">
                                            {{ $menu->name }}
                                        </span>
                                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                            <i class="fa-solid fa-mug-hot"></i>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between mt-2 pt-1 border-t border-gray-100">
                                        <span class="text-[10px] text-gray-400 font-medium truncate">
                                            {{ $menu->category->name ?? '' }}
                                        </span>
                                        <span class="text-blue-600 font-black text-xs bg-blue-50 border border-blue-100/70 px-2 py-0.5 rounded-lg shadow-2xs">
                                            {{ number_format($menu->price, 2) }} ج
                                        </span>
                                    </div>
                                </div>
                            @endif

                            {{-- بادج الكمية المضافة في السلة (يظهر فوراً عند إضافة الصنف) --}}
                            <span id="badge-{{ $menu->id }}"
                                class="hidden absolute top-1.5 left-1.5 min-w-[22px] h-[22px] px-1.5 bg-blue-600 text-white text-[11px] font-black rounded-full flex items-center justify-center shadow-md border-2 border-white animate-scale-in">
                            </span>
                        </button>
                    @endforeach
                </div>

                {{-- رسالة عند عدم وجود نتائج بحث --}}
                <div id="noResults" class="hidden text-center py-16 text-gray-400 font-medium text-sm">
                    <div class="w-16 h-16 rounded-2xl bg-gray-100 text-gray-300 flex items-center justify-center mx-auto mb-3 text-2xl">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <p class="font-bold text-gray-600 mb-1">لم يتم العثور على أي صنف مطابِق</p>
                    <p class="text-xs text-gray-400 mb-3">تأكد من كتابة الاسم بشكل صحيح أو امسح البحث</p>
                    <button type="button" onclick="clearSearch()" class="px-4 py-1.5 rounded-xl bg-blue-50 text-blue-600 font-bold text-xs hover:bg-blue-100 transition">
                        مسح البحث وعرض الكل
                    </button>
                </div>
            </div>
        </div>

        {{-- ==================== الجانب الأيسر: لوحة السلة وإعدادات الطلب ==================== --}}
        <div class="w-full lg:w-96 xl:w-[410px] 2xl:w-[440px] shrink-0 flex flex-col min-h-0 bg-white rounded-2xl border border-gray-200/80 shadow-xs overflow-hidden">
            
            {{-- رأس السلة: أنواع الطلب (تيك أواي / صالة / ديليفري) + زر مسح السلة --}}
            <div class="p-2.5 sm:p-3 border-b border-gray-100 bg-slate-50/70 shrink-0 space-y-2">
                {{-- أزرار نوع الطلب --}}
                <div class="grid grid-cols-3 gap-1.5 bg-gray-200/70 p-1 rounded-xl">
                    <button type="button" onclick="setOrderType('takeaway')" id="type_takeaway"
                        class="order-type-btn bg-white shadow-xs text-blue-700 font-black text-xs py-2 rounded-lg transition-all flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-mug-saucer text-[11px]"></i>
                        <span>تيك أواي</span>
                    </button>
                    <button type="button" onclick="setOrderType('dine_in')" id="type_dine_in"
                        class="order-type-btn text-gray-600 hover:text-gray-800 font-bold text-xs py-2 rounded-lg transition-all flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-chair text-[11px]"></i>
                        <span>صالة</span>
                    </button>
                    <button type="button" onclick="setOrderType('delivery')" id="type_delivery"
                        class="order-type-btn text-gray-600 hover:text-gray-800 font-bold text-xs py-2 rounded-lg transition-all flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-motorcycle text-[11px]"></i>
                        <span>ديليفري</span>
                    </button>
                </div>

                {{-- الحقول الديناميكية للصالة والديليفري --}}
                <div id="dynamicFields" class="hidden space-y-2 pt-1 border-t border-gray-200/50">
                    {{-- صالة: اختيار الطاولة مع إمكانية التحديث --}}
                    <div id="field_dine_in" class="hidden">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-black text-gray-700 flex items-center gap-1">
                                <i class="fa-solid fa-table-cells-large text-blue-600"></i>
                                اختر الطاولة <span class="text-red-500">*</span>
                            </span>
                            <button type="button" onclick="refreshTables()" class="text-[11px] text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                                <i class="fa-solid fa-rotate text-[10px]"></i> تحديث
                            </button>
                        </div>
                        <div id="tablesGrid" class="grid grid-cols-4 gap-1.5 max-h-32 overflow-y-auto pos-scrollbar p-1 bg-gray-50 rounded-xl border border-gray-200">
                            {{-- يتم تعبئتها ديناميكياً --}}
                        </div>
                        <p id="tableError" class="hidden text-red-500 text-[11px] font-bold mt-1 flex items-center gap-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            يرجى اختيار طاولة لطلب الصالة.
                        </p>
                        <p id="noTablesMsg" class="hidden text-xs text-gray-400 text-center py-2">
                            لا توجد طاولات متاحة حالياً.
                        </p>
                    </div>

                    {{-- ديليفري: هاتف وعنوان ومندوب --}}
                    <div id="field_delivery" class="hidden space-y-1.5">
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid fa-phone text-xs"></i>
                            </span>
                            <input type="text" id="customer_phone"
                                class="w-full bg-white pr-8 pl-3 py-1.5 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-medium"
                                placeholder="رقم هاتف العميل">
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid fa-location-dot text-xs"></i>
                            </span>
                            <input type="text" id="delivery_address"
                                class="w-full bg-white pr-8 pl-3 py-1.5 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-medium"
                                placeholder="عنوان التوصيل بالتفصيل">
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                                <i class="fa-solid fa-id-badge text-xs"></i>
                            </span>
                            <input type="text" id="delivery_person"
                                class="w-full bg-white pr-8 pl-3 py-1.5 rounded-xl border border-gray-200 focus:border-blue-500 outline-hidden text-xs font-medium"
                                placeholder="اسم المندوب (اختياري)">
                        </div>
                    </div>
                </div>

                {{-- عنوان السلة مع زر الحذف الفوري لكامل السلة (بنقرة واحدة بدون تأكيد) --}}
                <div class="flex items-center justify-between pt-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-black text-gray-800 flex items-center gap-1.5">
                            <i class="fa-solid fa-bag-shopping text-blue-600"></i>
                            محتويات الطلب
                        </span>
                        <span id="itemsCountBadge" class="bg-blue-100 text-blue-800 text-[11px] font-black px-2 py-0.5 rounded-lg">
                            0 أصناف
                        </span>
                    </div>

                    {{-- زر تفريغ السلة بنقرة واحدة مباشرة بدون أي تأكيد --}}
                    <button type="button" id="clearCartBtn" onclick="clearCart()"
                        class="hidden text-xs font-bold text-red-600 hover:text-red-700 bg-red-50 hover:bg-red-100 px-2.5 py-1 rounded-lg transition-all flex items-center gap-1 active:scale-95"
                        title="حذف جميع الأصناف من السلة فوراً بنقرة واحدة">
                        <i class="fa-solid fa-trash-can text-[11px]"></i>
                        <span>إفراغ السلة</span>
                    </button>
                </div>
            </div>

            {{-- قائمة أصناف السلة القابلة للتمرير (تعديل الكميات والحذف الفوري بنقرة واحدة) --}}
            <div class="flex-1 overflow-y-auto p-2 sm:p-2.5 space-y-2 pos-scrollbar min-h-0" id="invoiceItems">
                {{-- يتم حقن الأصناف هنا عبر جافاسكريبت --}}
            </div>

            {{-- إدخال الملاحظات والخصم السريع في أسفل لوحة السلة --}}
            <div class="p-2 sm:p-2.5 border-t border-gray-100 bg-slate-50/50 shrink-0 space-y-2">
                {{-- ملاحظة سريعة --}}
                <div class="relative">
                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400">
                        <i class="fa-regular fa-comment-dots text-xs"></i>
                    </span>
                    <input type="text" id="orderNotes"
                        class="w-full bg-white border border-gray-200 rounded-xl pr-8 pl-3 py-1.5 text-xs text-gray-700 focus:border-blue-400 outline-hidden transition placeholder:text-gray-400"
                        placeholder="ملاحظات على الطلب (بدون سكر، زيادة ثلج...)">
                </div>

                {{-- الخصم السريع مع أزرار جاهزة --}}
                <div class="flex items-center gap-2">
                    <span class="text-xs font-bold text-gray-600 shrink-0">الخصم:</span>
                    <div class="relative flex-1">
                        <input type="number" id="discountInput" min="0" step="1" value="0"
                            oninput="updateTotals()"
                            class="w-full bg-white border border-gray-200 rounded-xl px-3 py-1 text-xs text-center font-black text-gray-800 focus:border-blue-400 outline-hidden">
                    </div>
                    <span class="text-xs font-bold text-gray-400">ج.م</span>
                    <div class="flex items-center gap-1">
                        <button type="button" onclick="setQuickDiscount(0)" class="px-2 py-0.8 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-[10px] font-bold">0</button>
                        <button type="button" onclick="setQuickDiscount(5)" class="px-2 py-0.8 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-[10px] font-bold">5</button>
                        <button type="button" onclick="setQuickDiscount(10)" class="px-2 py-0.8 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-[10px] font-bold">10</button>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- ==================== 3. الشريط السفلي الثابت (غير متأثر بالاسكرول) ==================== --}}
    <footer class="mt-2 shrink-0 bg-white border border-gray-200/90 rounded-2xl shadow-xl px-3 py-2 sm:px-4 sm:py-2.5 z-30">
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            
            {{-- الجانب الأيمن: طريقة الدفع (أزرار واضحة بنقرة واحدة) --}}
            <div class="flex items-center gap-2" id="paymentMethodSection">
                <span class="text-xs font-black text-gray-700 shrink-0 hidden sm:inline">طريقة الدفع:</span>
                <div class="grid grid-cols-3 gap-1.5 flex-1 sm:flex-initial">
                    <button type="button" onclick="setPaymentMethod('cash')" id="pm_cash"
                        class="payment-method-btn px-3 py-2 rounded-xl text-xs font-black transition-all bg-emerald-600 text-white shadow-xs shadow-emerald-600/30 flex items-center justify-center gap-1.5 active:scale-95">
                        <i class="fa-solid fa-money-bill-wave text-xs"></i>
                        <span>كاش</span>
                    </button>
                    <button type="button" onclick="setPaymentMethod('InstaPay')" id="pm_InstaPay"
                        class="payment-method-btn px-3 py-2 rounded-xl text-xs font-bold transition-all bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 flex items-center justify-center gap-1.5 active:scale-95">
                        <i class="fa-solid fa-mobile-screen-button text-xs"></i>
                        <span>إنستا باي</span>
                    </button>
                    <button type="button" onclick="setPaymentMethod('card')" id="pm_card"
                        class="payment-method-btn px-3 py-2 rounded-xl text-xs font-bold transition-all bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200 flex items-center justify-center gap-1.5 active:scale-95">
                        <i class="fa-regular fa-credit-card text-xs"></i>
                        <span>فيزا / كارد</span>
                    </button>
                </div>
            </div>

            {{-- المنتصف: ملخص الحساب والإجمالي الواضح --}}
            <div class="flex items-center justify-between md:justify-center gap-4 px-3 py-1.5 bg-slate-50 border border-slate-200/70 rounded-xl">
                <div class="text-right">
                    <div class="text-[10px] text-gray-500 font-bold">المجموع الفرعي:</div>
                    <div id="subtotalDisplay" class="text-xs font-black text-gray-700">0.00 ج</div>
                </div>
                <div class="text-right">
                    <div class="text-[10px] text-gray-500 font-bold">الخصم:</div>
                    <div id="discountDisplay" class="text-xs font-black text-red-500">- 0.00 ج</div>
                </div>
                <div class="h-6 w-px bg-gray-200"></div>
                <div class="text-right">
                    <div class="text-[10px] text-gray-500 font-black uppercase tracking-wider">الإجمالي النهائي:</div>
                    <div id="grandTotal" class="text-xl sm:text-2xl font-black text-emerald-600 leading-tight">
                        0.00 <span class="text-xs font-bold">ج.م</span>
                    </div>
                </div>
            </div>

            {{-- الجانب الأيسر: زر إتمام الطلب الرئيسي --}}
            <div class="flex items-center gap-2">
                <button type="button" onclick="submitOrder()" id="submitBtn" disabled
                    class="w-full md:w-auto px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:scale-95 text-white font-black text-sm sm:text-base shadow-lg shadow-emerald-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed disabled:shadow-none">
                    <i class="fa-solid fa-circle-check text-base"></i>
                    <span>إتمام الطلب</span>
                    <span class="hidden xl:inline text-[11px] font-mono opacity-80 bg-emerald-700 px-1.5 py-0.5 rounded-md">[F9]</span>
                </button>
            </div>

        </div>
    </footer>

    {{-- ==================== نافذة النجاح (Success Modal) ==================== --}}
    <div id="successModal"
        class="fixed inset-0 bg-black/50 backdrop-blur-xs z-50 flex items-center justify-center hidden"
        onclick="hideSuccessModal()">
        <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-xs w-full mx-4 text-center shadow-2xl animate-scale-in" onclick="event.stopPropagation()">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3 text-3xl shadow-inner">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h4 class="text-lg font-black text-gray-800 mb-1">تم إتمام الطلب بنجاح!</h4>
            <p id="successOrderNumber" class="text-sm font-black text-blue-600 mb-2 font-mono"></p>
            <p class="text-xs text-gray-500 mb-4">تم حفظ الطلب وإرساله إلى نقطة الإعداد وتحديث المخزن.</p>
            <button type="button" onclick="hideSuccessModal()" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 rounded-xl text-xs transition">
                متابعة طلب جديد
            </button>
        </div>
    </div>

    {{-- ==================== نافذة تنبيه تعذر اتصال الطابعة ==================== --}}
    <div id="printerWarningModal"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center hidden"
        onclick="hidePrinterWarningModal()">
        <div class="bg-white rounded-3xl p-6 sm:p-7 max-w-md w-full mx-4 shadow-2xl border border-amber-200 animate-slide-in relative text-right" onclick="event.stopPropagation()">
            <div class="flex flex-col items-center text-center mb-4">
                <div class="w-14 h-14 bg-amber-50 border-2 border-amber-100 rounded-2xl flex items-center justify-center text-amber-500 text-2xl shadow-inner mb-2.5 relative">
                    <i class="fa-solid fa-print"></i>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-amber-500 text-white rounded-full flex items-center justify-center text-[10px] font-black border-2 border-white">!</span>
                </div>
                <h4 class="text-lg font-black text-gray-900 mb-1">تنبيه: تعذر اتصال الطابعة</h4>
                <p id="printerWarningMsg" class="text-xs font-semibold text-gray-600 leading-relaxed max-w-sm">
                    تعذر الاتصال ببرنامج الطباعة (Agent) أو أن الطابعات غير متصلة بالشبكة.
                </p>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3 mb-4 space-y-2">
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

            <div class="grid grid-cols-2 gap-2.5">
                <button type="button" id="confirmForceSubmitBtn" onclick="confirmForceSubmitOrder()"
                    class="w-full bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-3 rounded-xl text-xs sm:text-sm shadow-md shadow-amber-500/20 transition-all flex items-center justify-center gap-1.5 active:scale-95">
                    <i class="fa-solid fa-check"></i> متابعة وحفظ الطلب
                </button>
                <button type="button" onclick="hidePrinterWarningModal()"
                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 px-3 rounded-xl text-xs sm:text-sm transition-all active:scale-95">
                    إلغاء والتراجع
                </button>
            </div>
        </div>
    </div>

    {{-- ==================== نافذة تشخيص الطابعات المباشر ==================== --}}
    <div id="printerDetailsModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 flex items-center justify-center hidden"
        onclick="togglePrinterDetailsModal()">
        <div class="bg-white rounded-3xl p-5 max-w-sm w-full mx-4 shadow-2xl border border-gray-100 relative text-right" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-3">
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

            <div class="space-y-2 mb-4">
                <div class="flex items-center justify-between p-2 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="flex items-center gap-2">
                        <span id="diagAgentDot" class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                        <span class="text-xs font-bold text-gray-700">برنامج الطباعة (Agent):</span>
                    </div>
                    <span id="diagAgentStatus" class="text-xs font-bold text-gray-600">جاري الفحص...</span>
                </div>

                <div class="flex items-center justify-between p-2 rounded-xl bg-gray-50 border border-gray-100">
                    <div class="flex items-center gap-2">
                        <span id="diagCashierDot" class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                        <span class="text-xs font-bold text-gray-700">طابعة الكاشير:</span>
                    </div>
                    <span id="diagCashierStatus" class="text-xs font-bold text-gray-600">-</span>
                </div>

                <div class="flex items-center justify-between p-2 rounded-xl bg-gray-50 border border-gray-100">
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
                class="w-full bg-blue-50 hover:bg-blue-100 text-blue-600 font-bold py-2 rounded-xl text-xs transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-rotate"></i> فحص الحالة الآن
            </button>
        </div>
    </div>

    {{-- ==================== Toast التنبيهات والأخطاء ==================== --}}
    <div id="errorToast"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 hidden bg-red-600 text-white text-xs sm:text-sm font-bold px-4 py-2.5 rounded-2xl shadow-xl flex items-center gap-2 max-w-sm text-center animate-slide-in">
        <i class="fa-solid fa-triangle-exclamation shrink-0"></i>
        <span id="errorToastMsg">حدث خطأ</span>
    </div>

    <div id="infoToast"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 hidden bg-slate-800 text-white text-xs sm:text-sm font-bold px-4 py-2.5 rounded-2xl shadow-xl flex items-center gap-2 max-w-sm text-center animate-slide-in">
        <i class="fa-solid fa-circle-info text-blue-400 shrink-0"></i>
        <span id="infoToastMsg">إشعار</span>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // ========== 1. البيانات الأولية والثوابت ==========
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    let tablesData = @json($activeTables);

    // إضافة كلاس الصفحة على الـ body لضبط الأبعاد
    document.body.classList.add('pos-page');

    // ========== 2. حالة الـ POS ==========
    let cart = {};
    let currentCategoryId = 'all';
    let currentOrderType = 'takeaway';
    let selectedTableId = null;
    let currentPaymentMethod = 'cash';
    let isSoundEnabled = true;

    // ========== 3. نظام الصوتيات للكاشير (Web Audio API Synthesizer) ==========
    const audioCtx = (typeof window !== 'undefined' && (window.AudioContext || window.webkitAudioContext))
        ? new (window.AudioContext || window.webkitAudioContext)()
        : null;

    function playTone(freq = 600, type = 'sine', duration = 0.05, gainValue = 0.1) {
        if (!isSoundEnabled || !audioCtx) return;
        try {
            if (audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
            gain.gain.setValueAtTime(gainValue, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + duration);
        } catch (e) {}
    }

    function soundAdd() { playTone(880, 'sine', 0.06, 0.12); }
    function soundRemove() { playTone(350, 'triangle', 0.08, 0.15); }
    function soundClear() { playTone(240, 'sawtooth', 0.12, 0.15); }
    function soundSuccess() {
        playTone(523, 'sine', 0.1, 0.15);
        setTimeout(() => playTone(659, 'sine', 0.1, 0.15), 90);
        setTimeout(() => playTone(784, 'sine', 0.2, 0.15), 180);
    }

    function toggleSound() {
        isSoundEnabled = !isSoundEnabled;
        const icon = document.getElementById('soundIcon');
        const btn = document.getElementById('soundToggleBtn');
        if (isSoundEnabled) {
            icon.className = 'fa-solid fa-volume-high';
            btn.classList.remove('text-gray-400');
            btn.classList.add('text-gray-600');
            playTone(800, 'sine', 0.05, 0.1);
        } else {
            icon.className = 'fa-solid fa-volume-xmark';
            btn.classList.remove('text-gray-600');
            btn.classList.add('text-gray-400');
        }
    }

    // ========== 4. ساعة التوقيت الحية ==========
    function updateClock() {
        const clockEl = document.getElementById('posLiveClock');
        if (clockEl) {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('ar-EG', { hour12: true });
        }
    }
    setInterval(updateClock, 1000);
    updateClock();

    // ========== 5. وضع الشاشة الكاملة (Fullscreen Mode) ==========
    function togglePosFullscreen() {
        const body = document.body;
        const isCurrentlyFullscreen = body.classList.contains('pos-fullscreen') || !!document.fullscreenElement;

        if (!isCurrentlyFullscreen) {
            // تفعيل الشاشة الكاملة
            body.classList.add('pos-fullscreen');
            if (document.documentElement.requestFullscreen) {
                document.documentElement.requestFullscreen().catch(() => {});
            }
            updateFullscreenUI(true);
        } else {
            // الخروج من الشاشة الكاملة
            body.classList.remove('pos-fullscreen');
            if (document.fullscreenElement && document.exitFullscreen) {
                document.exitFullscreen().catch(() => {});
            }
            updateFullscreenUI(false);
        }
    }

    function updateFullscreenUI(isFullscreen) {
        const icon = document.getElementById('fullscreenIcon');
        const text = document.getElementById('fullscreenText');
        const btn = document.getElementById('fullscreenToggleBtn');

        if (isFullscreen) {
            if (icon) icon.className = 'fa-solid fa-compress text-xs';
            if (text) text.textContent = 'خروج من الشاشة';
            if (btn) {
                btn.classList.remove('bg-indigo-50/80', 'text-indigo-700', 'border-indigo-200');
                btn.classList.add('bg-amber-500', 'text-white', 'border-amber-600');
            }
        } else {
            if (icon) icon.className = 'fa-solid fa-expand text-xs';
            if (text) text.textContent = 'شاشة كاملة';
            if (btn) {
                btn.classList.add('bg-indigo-50/80', 'text-indigo-700', 'border-indigo-200');
                btn.classList.remove('bg-amber-500', 'text-white', 'border-amber-600');
            }
        }
    }

    // مزامنة حالة الشاشة الكاملة عند الضغط على Esc أو F11 من المتصفح
    document.addEventListener('fullscreenchange', () => {
        const isFs = !!document.fullscreenElement;
        if (isFs) {
            document.body.classList.add('pos-fullscreen');
            updateFullscreenUI(true);
        } else {
            document.body.classList.remove('pos-fullscreen');
            updateFullscreenUI(false);
        }
    });

    // ========== 6. البحث السريع والفلترة ==========
    let searchDebounceTimer = null;
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearch');

    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            filterItems();
            clearSearchBtn.classList.toggle('hidden', searchInput.value.trim() === '');
        }, 150);
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

    // ========== 7. تبديل الأقسام ==========
    function selectCategory(id) {
        currentCategoryId = String(id);
        document.querySelectorAll('.category-tab').forEach(tab => {
            tab.classList.remove('bg-blue-600', 'text-white', 'shadow-xs', 'shadow-blue-500/30');
            tab.classList.add('bg-white', 'text-gray-700', 'border-gray-200');
        });
        const active = document.getElementById(`tab-${id}`);
        if (active) {
            active.classList.remove('bg-white', 'text-gray-700', 'border-gray-200');
            active.classList.add('bg-blue-600', 'text-white', 'shadow-xs', 'shadow-blue-500/30');
        }
        filterItems();
    }

    // ========== 8. نوع الطلب والطاولات ==========
    function setOrderType(type) {
        currentOrderType = type;
        selectedTableId = null;

        document.querySelectorAll('.order-type-btn').forEach(btn => {
            btn.classList.remove('bg-white', 'shadow-xs', 'text-blue-700', 'font-black');
            btn.classList.add('text-gray-600', 'font-bold');
        });
        const activeBtn = document.getElementById(`type_${type}`);
        if (activeBtn) {
            activeBtn.classList.remove('text-gray-600', 'font-bold');
            activeBtn.classList.add('bg-white', 'shadow-xs', 'text-blue-700', 'font-black');
        }

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

        updateSubmitButton();
    }

    function renderTablesGrid() {
        const grid = document.getElementById('tablesGrid');
        const noTablesMsg = document.getElementById('noTablesMsg');
        if (!grid) return;

        grid.innerHTML = '';
        if (!tablesData || tablesData.length === 0) {
            if (noTablesMsg) noTablesMsg.classList.remove('hidden');
            return;
        }
        if (noTablesMsg) noTablesMsg.classList.add('hidden');

        tablesData.forEach(table => {
            const isOccupied = table.is_occupied;
            const isSelected = selectedTableId === table.id;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.dataset.tableId = table.id;
            btn.className = [
                'table-btn rounded-xl py-2 px-1 text-center transition-all border text-xs font-bold select-none',
                isSelected
                    ? 'bg-blue-600 text-white border-blue-600 shadow-sm shadow-blue-500/30 ring-2 ring-blue-300'
                    : isOccupied
                        ? 'bg-orange-50 text-orange-600 border-orange-200 cursor-not-allowed opacity-75'
                        : 'bg-white text-gray-700 border-gray-200 hover:border-blue-400 hover:bg-blue-50 hover:text-blue-700',
            ].join(' ');

            btn.disabled = isOccupied;
            btn.title = isOccupied ? 'مشغولة حالياً' : table.name;
            btn.innerHTML = `
                <div class="font-black text-xs truncate">${table.name}</div>
                ${table.capacity ? `<div class="text-[9px] opacity-70 mt-0.5">${table.capacity}👤</div>` : ''}
                ${isOccupied ? '<div class="text-[9px] font-black text-orange-500 mt-0.5">مشغولة</div>' : ''}
            `;

            btn.onclick = () => selectTable(table.id);
            grid.appendChild(btn);
        });
    }

    function selectTable(id) {
        selectedTableId = id;
        document.getElementById('tableError').classList.add('hidden');
        renderTablesGrid();
        soundAdd();
    }

    function refreshTables() {
        fetch('{{ route('pos.tables') }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN }
        })
        .then(r => r.json())
        .then(data => {
            tablesData = data.data;
            if (currentOrderType === 'dine_in') renderTablesGrid();
        })
        .catch(() => {});
    }

    // ========== 9. طريقة الدفع ==========
    function setPaymentMethod(method) {
        currentPaymentMethod = method;
        document.querySelectorAll('.payment-method-btn').forEach(btn => {
            btn.classList.remove('bg-emerald-600', 'text-white', 'shadow-xs', 'shadow-emerald-600/30', 'font-black');
            btn.classList.add('bg-gray-50', 'text-gray-700', 'border', 'border-gray-200', 'font-bold');
        });
        const active = document.getElementById(`pm_${method}`);
        if (active) {
            active.classList.remove('bg-gray-50', 'text-gray-700', 'border', 'border-gray-200', 'font-bold');
            active.classList.add('bg-emerald-600', 'text-white', 'shadow-xs', 'shadow-emerald-600/30', 'font-black');
        }
    }
    setPaymentMethod('cash');

    // ========== 10. التعامل الفوري مع السلة (بدون أي تأكيد) ==========
    
    // إضافة منتج بنقرة واحدة
    function addItem(id, name, price) {
        id = String(id);
        if (cart[id]) {
            cart[id].quantity += 1;
        } else {
            cart[id] = { id, name, price: parseFloat(price), quantity: 1 };
        }
        renderCart();
        updateBadge(id);
        soundAdd();
        if (navigator.vibrate) navigator.vibrate(25);
    }

    // زيادة أو تقليل الكمية بنقرة واحدة (وحذف مباشر إذا أصبحت 0)
    function updateQuantity(id, delta, manualValue = null) {
        id = String(id);
        if (!cart[id]) return;

        if (manualValue !== null) {
            const val = parseInt(manualValue) || 0;
            cart[id].quantity = val;
        } else {
            cart[id].quantity += delta;
        }

        // إذا أصبحت الكمية صفر أو أقل: يحذف الصنف فوراً بدون أي تأكيد
        if (cart[id].quantity <= 0) {
            removeItem(id);
            return;
        }

        renderCart();
        updateBadge(id);
        soundAdd();
    }

    // حذف صنف فردي بنقرة واحدة بدون أي تأكيد
    function removeItem(id) {
        id = String(id);
        if (cart[id]) {
            delete cart[id];
            renderCart();
            updateBadge(id);
            soundRemove();
        }
    }

    // مسح وحذف السلة بالكامل بنقرة واحدة بدون أي تأكيد إطلاقاً
    function clearCart() {
        cart = {};
        document.getElementById('discountInput').value = '0';
        renderCart();
        document.querySelectorAll('[id^="badge-"]').forEach(b => b.classList.add('hidden'));
        soundClear();
        showInfoToast('تم إفراغ السلة بنجاح.');
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

    function setQuickDiscount(val) {
        document.getElementById('discountInput').value = val;
        updateTotals();
        soundAdd();
    }

    function renderCart() {
        const container = document.getElementById('invoiceItems');
        container.innerHTML = '';
        let subtotal = 0;
        let totalItems = 0;
        const hasItems = Object.keys(cart).length > 0;

        if (!hasItems) {
            container.innerHTML = `
                <div class="h-full min-h-[140px] flex flex-col items-center justify-center text-gray-400 py-6 space-y-2 opacity-70">
                    <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center text-gray-300 text-xl">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <p class="text-xs font-bold text-gray-500">السلة فارغة حالياً</p>
                    <p class="text-[11px] text-gray-400">انقر على أي صنف لإضافته للطلب فوراً</p>
                </div>`;
        } else {
            for (const key in cart) {
                const item = cart[key];
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                totalItems += item.quantity;

                const itemRow = document.createElement('div');
                itemRow.id = `cart-item-${item.id}`;
                itemRow.className = 'bg-white border border-gray-200/80 rounded-2xl p-2.5 shadow-2xs hover:border-blue-300 transition-all';
                itemRow.innerHTML = `
                    <div class="flex justify-between items-start mb-1.5 gap-2">
                        <span class="font-bold text-gray-800 text-xs sm:text-sm leading-snug flex-1">${item.name}</span>
                        <button type="button" onclick="removeItem('${item.id}')"
                            class="w-6 h-6 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center transition shrink-0"
                            title="حذف الصنف بنقرة واحدة">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </div>
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="text-[10px] text-gray-400 font-bold">${item.price.toFixed(2)} ج × ${item.quantity}</div>
                            <div class="text-xs sm:text-sm font-black text-blue-600">${itemTotal.toFixed(2)} ج.م</div>
                        </div>
                        <div class="flex items-center bg-gray-100/80 border border-gray-200 rounded-xl p-0.5">
                            <button type="button" onclick="updateQuantity('${item.id}', -1)"
                                class="w-7 h-7 flex items-center justify-center text-gray-600 hover:bg-white hover:text-red-600 hover:shadow-2xs rounded-lg transition active:scale-95">
                                <i class="fa-solid fa-minus text-[10px]"></i>
                            </button>
                            <input type="number" value="${item.quantity}" min="1"
                                onchange="updateQuantity('${item.id}', 0, this.value)"
                                onclick="this.select()"
                                class="w-8 text-center bg-transparent font-black text-gray-800 outline-hidden border-none text-xs">
                            <button type="button" onclick="updateQuantity('${item.id}', 1)"
                                class="w-7 h-7 flex items-center justify-center text-gray-600 hover:bg-white hover:text-blue-600 hover:shadow-2xs rounded-lg transition active:scale-95">
                                <i class="fa-solid fa-plus text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(itemRow);
            }
        }

        document.getElementById('itemsCountBadge').textContent = `${totalItems} أصناف`;
        const clearBtn = document.getElementById('clearCartBtn');
        if (clearBtn) clearBtn.classList.toggle('hidden', !hasItems);

        updateTotalsDisplay(subtotal);
        updateSubmitButton();
    }

    function updateTotals() {
        let subtotal = 0;
        for (const key in cart) subtotal += cart[key].price * cart[key].quantity;
        updateTotalsDisplay(subtotal);
    }

    function updateTotalsDisplay(subtotal) {
        const discountInput = document.getElementById('discountInput');
        const discount = Math.max(0, parseFloat(discountInput.value) || 0);
        const total = Math.max(0, subtotal - discount);

        document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2) + ' ج';
        document.getElementById('discountDisplay').textContent = '- ' + discount.toFixed(2) + ' ج';
        document.getElementById('grandTotal').innerHTML = `${total.toFixed(2)} <span class="text-xs font-bold">ج.م</span>`;
    }

    function updateSubmitButton() {
        const hasItems = Object.keys(cart).length > 0;
        const btn = document.getElementById('submitBtn');
        if (btn) btn.disabled = !hasItems;
    }

    renderCart();

    // ========== 11. إرسال وحفظ الطلب ==========
    function submitOrder(force = false) {
        if (Object.keys(cart).length === 0) return;

        // التحقق من الطاولة للصالة
        if (currentOrderType === 'dine_in' && !selectedTableId) {
            document.getElementById('tableError').classList.remove('hidden');
            document.getElementById('field_dine_in').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            showError('يرجى اختيار طاولة لطلب الصالة.');
            return;
        }

        const subtotal = Object.values(cart).reduce((s, i) => s + i.price * i.quantity, 0);
        const discount = Math.max(0, parseFloat(document.getElementById('discountInput').value) || 0);
        if (discount > subtotal) {
            showError('قيمة الخصم لا يمكن أن تتجاوز إجمالي الطلب.');
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
        } else if (currentOrderType === 'delivery') {
            payload.phone            = document.getElementById('customer_phone').value.trim() || null;
            payload.delivery_address = document.getElementById('delivery_address').value.trim() || null;
            payload.delivery_person  = document.getElementById('delivery_person').value.trim() || null;
            payload.payment_method   = currentPaymentMethod;
        } else {
            payload.payment_method   = currentPaymentMethod;
        }

        const btn = document.getElementById('submitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> <span>جاري الحفظ...</span>';

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
            document.getElementById('successOrderNumber').textContent = 'رقم الطلب: #' + (data.data?.order_number || '');
            showSuccessModal();
            soundSuccess();
            resetPOS();
            refreshTables();
        })
        .catch(err => {
            // توجيه لصفحة الشيفت إذا لم يكن هناك شيفت مفتوح
            if (err?.redirect) {
                alert(err.message || 'لا تتمكن من فعل هذه الخطوه دون بدء شيفت');
                window.location.href = err.redirect;
                return;
            }

            // تنبيه الطابعة غير المتصلة
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
            btn.innerHTML = '<i class="fa-solid fa-circle-check text-base"></i> <span>إتمام الطلب</span> <span class="hidden xl:inline text-[11px] font-mono opacity-80 bg-emerald-700 px-1.5 py-0.5 rounded-md">[F9]</span>';
            updateSubmitButton();
        });
    }

    // ========== 12. إدارة نافذة تحذير الطابعة ومتابعة الحفظ ==========
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

    // ========== 13. فحص وتشخيص حالة الطابعة المباشر ==========
    async function fetchPrinterStatus(isManual = false) {
        const btn = document.getElementById('posPrinterStatusBtn');
        const dot = document.getElementById('posPrinterDot');
        const text = document.getElementById('posPrinterText');

        if (isManual && text) text.textContent = 'جاري الفحص...';

        try {
            const res = await fetch('/api/pos/printer-status?device_uuid=pos-cashier-01', {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.connected && data.is_ready) {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/50';
                btn.className = 'flex items-center gap-2 px-2.5 sm:px-3 py-2 rounded-xl border text-xs font-bold transition-all bg-emerald-50 border-emerald-200 text-emerald-700 hover:bg-emerald-100 active:scale-95';
                text.textContent = 'الطابعات جاهزة';
            } else if (data.connected && !data.is_ready) {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse';
                btn.className = 'flex items-center gap-2 px-2.5 sm:px-3 py-2 rounded-xl border text-xs font-bold transition-all bg-amber-50 border-amber-200 text-amber-700 hover:bg-amber-100 active:scale-95';
                text.textContent = 'تنبيه طابعة';
            } else {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-red-500';
                btn.className = 'flex items-center gap-2 px-2.5 sm:px-3 py-2 rounded-xl border text-xs font-bold transition-all bg-red-50 border-red-200 text-red-700 hover:bg-red-100 active:scale-95';
                text.textContent = 'الطابعة غير متصلة';
            }

            updatePrinterDetailsUI(data);
        } catch (e) {
            if (dot && btn && text) {
                dot.className = 'w-2.5 h-2.5 rounded-full bg-gray-400';
                btn.className = 'flex items-center gap-2 px-2.5 sm:px-3 py-2 rounded-xl border text-xs font-bold transition-all bg-gray-50 border-gray-200 text-gray-700 active:scale-95';
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

    fetchPrinterStatus();
    setInterval(() => fetchPrinterStatus(), 20000);

    // ========== 14. إعادة تعيين الـ POS بعد الطلب ==========
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

    // ========== 15. المودال والـ Toasts ==========
    function showSuccessModal() {
        const modal = document.getElementById('successModal');
        modal.classList.remove('hidden');
        setTimeout(() => hideSuccessModal(), 2800);
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
        errorTimer = setTimeout(() => toast.classList.add('hidden'), 3500);
    }

    let infoTimer = null;
    function showInfoToast(msg) {
        const toast = document.getElementById('infoToast');
        document.getElementById('infoToastMsg').textContent = msg;
        toast.classList.remove('hidden');
        clearTimeout(infoTimer);
        infoTimer = setTimeout(() => toast.classList.add('hidden'), 2200);
    }

    // ========== 16. اختصارات الكيبورد الاحترافية للكاشير ==========
    document.addEventListener('keydown', function(e) {
        const active = document.activeElement;
        const isTyping = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA');

        // F11 أو F10: تفعيل وإلغاء الشاشة الكاملة
        if (e.key === 'F11' || e.key === 'F10') {
            e.preventDefault();
            togglePosFullscreen();
            return;
        }

        // F9 أو Ctrl+Enter: إتمام الطلب مباشرة
        if (e.key === 'F9' || (e.ctrlKey && e.key === 'Enter')) {
            e.preventDefault();
            submitOrder();
            return;
        }

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

        // / أو F2: التركيز المباشر على مربع البحث
        if ((e.key === '/' || e.key === 'F2') && !isTyping) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
            return;
        }

        // كتابة مباشرة: تركيز البحث تلقائياً
        if (!isTyping && !e.ctrlKey && !e.altKey && !e.metaKey && e.key.length === 1) {
            searchInput.focus();
        }
    });
</script>
@endpush
