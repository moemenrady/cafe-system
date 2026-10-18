@extends('layouts.app')

@section('page_title', 'دليل الاستخدام التفاعلي')

@push('styles')
<style>
    .guide-accordion-header {
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .guide-accordion-header:hover {
        background-color: rgba(243, 244, 246, 0.8);
    }
    .guide-accordion-header.is-open {
        background-color: rgba(2, 132, 199, 0.04);
        border-bottom-color: rgba(229, 231, 235, 0.8);
    }
    .guide-chevron {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .guide-accordion-header.is-open .guide-chevron {
        transform: rotate(180deg);
        color: #0284c7;
    }
    .step-number {
        width: 30px;
        height: 30px;
        border-radius: 10px;
        background: #0284c7;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 0.8rem;
        flex-shrink: 0;
        box-shadow: 0 2px 8px rgba(2, 132, 199, 0.25);
    }
    .category-pill-btn.active {
        background: #0284c7 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25) !important;
    }
    @media print {
        #sidebar, header, .no-print, #mobileOverlay {
            display: none !important;
        }
        .guide-accordion-content {
            display: block !important;
            max-height: none !important;
            opacity: 1 !important;
        }
        main, body {
            background: #ffffff !important;
            overflow: visible !important;
            height: auto !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .guide-card {
            box-shadow: none !important;
            border: 1px solid #d1d5db !important;
            break-inside: avoid;
        }
    }
</style>
@endpush

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" id="userGuideContainer" dir="rtl">

    {{-- 1. كارت البانر الترحيبي (Hero Banner) --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-gray-900 via-sky-950 to-gray-900 text-white p-6 sm:p-8 shadow-xl border border-sky-900/40">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute left-10 -bottom-16 w-60 h-60 bg-sky-500/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="space-y-2.5 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-sky-200 text-xs font-semibold backdrop-blur-md border border-white/15">
                    <i class="fa-solid fa-graduation-cap text-amber-400"></i>
                    <span>دليل الاستخدام والتشغيل الرسمي | UNO Cafe Operations Manual</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white leading-snug">
                    دليل تشغيل وإدارة نظام UNO Cafe باحترافية
                </h1>
                <p class="text-xs sm:text-sm text-gray-300 leading-relaxed font-normal">
                    شرح تفاعلي دقيق ومفصل بأسلوب (سؤال وجواب) يوضح لك خطوة بخطوة كيف تدير عمليات البيع POS، فتح وإغلاق ورديات الكاشير، إدارة الصالة والترابيزات المشغولة، ربط الأصناف بالوصفات والمخزون، وتخصيص القائمة الجانبية والتاب الأساسية لحسابك.
                </p>
            </div>

            {{-- أزرار التحكم السريع (توسيع الكل، طي الكل، طباعة) --}}
            <div class="flex flex-wrap items-center gap-2.5 no-print self-start lg:self-center">
                <button type="button" onclick="expandAllQuestions()"
                    class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 border border-white/20 hover:scale-105 active:scale-95 shadow-sm">
                    <i class="fa-solid fa-angles-down text-sky-300"></i>
                    <span>توسيع جميع الأسئلة</span>
                </button>
                <button type="button" onclick="collapseAllQuestions()"
                    class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 border border-white/20 hover:scale-105 active:scale-95 shadow-sm">
                    <i class="fa-solid fa-angles-up text-sky-300"></i>
                    <span>طي الكل</span>
                </button>
                <button type="button" onclick="window.print()"
                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-lg shadow-amber-500/25 hover:scale-105 active:scale-95">
                    <i class="fa-solid fa-print"></i>
                    <span>طباعة الدليل</span>
                </button>
            </div>
        </div>

        {{-- شريط البحث اللحظي في الأسئلة والإجابات --}}
        <div class="mt-6 pt-5 border-t border-gray-800 flex items-center gap-3 no-print">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute right-4 top-3 text-gray-400 text-sm"></i>
                <input type="text" id="guideSearchInput" oninput="filterQuestions()"
                    class="w-full bg-gray-800/90 text-white placeholder-gray-400 rounded-2xl pr-11 pl-4 py-2.5 text-xs sm:text-sm border border-gray-700 focus:outline-none focus:ring-2 focus:ring-sky-400 focus:border-transparent transition"
                    placeholder="ابحث بأي سؤال أو كلمة مفتاحية (مثال: شيفت، خصم، ترابيزة، وصفة، جرد، إنستاباي)...">
            </div>
            <span id="guideResultsCount" class="text-xs text-gray-400 font-bold hidden sm:inline"></span>
        </div>
    </div>

    {{-- 2. أزرار الفلترة حسب الأقسام (Category Filter Pills) --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-2 no-print">
        <button type="button" onclick="filterCategory('all', this)" class="category-pill-btn active px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-border-all"></i>
            <span>جميع التساؤلات</span>
        </button>
        <button type="button" onclick="filterCategory('pos', this)" class="category-pill-btn px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-cash-register text-amber-500"></i>
            <span>نقطة البيع والطلبات</span>
        </button>
        <button type="button" onclick="filterCategory('tables', this)" class="category-pill-btn px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-bell-concierge text-sky-500"></i>
            <span>الصالة والترابيزات</span>
        </button>
        <button type="button" onclick="filterCategory('shifts', this)" class="category-pill-btn px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-clock text-emerald-500"></i>
            <span>الورديات والخزينة</span>
        </button>
        <button type="button" onclick="filterCategory('menu', this)" class="category-pill-btn px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-utensils text-orange-500"></i>
            <span>المنيو والوصفات</span>
        </button>
        <button type="button" onclick="filterCategory('inventory', this)" class="category-pill-btn px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-boxes-stacked text-blue-500"></i>
            <span>المخزن والمشتريات</span>
        </button>
        <button type="button" onclick="filterCategory('analytics', this)" class="category-pill-btn px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-chart-pie text-purple-500"></i>
            <span>التحليل الشامل والتقارير</span>
        </button>
        <button type="button" onclick="filterCategory('customization', this)" class="category-pill-btn px-4 py-2 rounded-2xl bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 text-xs font-bold whitespace-nowrap transition shadow-xs flex items-center gap-1.5">
            <i class="fa-solid fa-sliders text-rose-500"></i>
            <span>التخصيص والناف بار</span>
        </button>
    </div>

    {{-- رسالة عند عدم وجود نتائج بحث --}}
    <div id="noResultsAlert" class="hidden bg-white rounded-3xl border border-gray-200 p-8 text-center space-y-3">
        <div class="w-16 h-16 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-2xl">
            <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <h3 class="text-base font-bold text-gray-800">لم يتم العثور على تساؤلات مطابقة لبحثك</h3>
        <p class="text-xs text-gray-500 max-w-md mx-auto">جرب البحث بكلمات أخرى مثل (فاتورة، شيفت، ترابيزة، مصروف، مورد، خصم، إعدادات).</p>
    </div>

    {{-- 3. قائمة الأسئلة والإجابات التفاعلية (Accordion Q&A Items) --}}
    <div class="space-y-4" id="questionsList">

        {{-- ========================================================= --}}
        {{-- قسم 1: نقطة البيع والطلبات (POS) --}}
        {{-- ========================================================= --}}

        {{-- س 1: إنشاء طلب سريع سفري والدفع --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="pos">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header is-open w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-cash-register"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">نقطة البيع POS</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> القائمة الجانبية > البيع (POS)</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أقدر أعمل طلب سريع سفري (Takeaway) وأصدر الفاتورة وأحاسب العميل نقداً أو بالفيزا أو بإنستاباي؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5 shadow-2xs">
                        <div class="flex items-center gap-2">
                            <span class="step-number">1</span>
                            <strong class="font-bold text-gray-900 text-xs">اختيار الأصناف</strong>
                        </div>
                        <p class="text-[12px] text-gray-500">افتح صفحة البيع POS واضغط على الصنف المطلوب، أو استخدم الفلترة حسب الأقسام (قهوة ساخنة، مرطبات، إلخ) أو شريط البحث السريع.</p>
                    </div>

                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5 shadow-2xs">
                        <div class="flex items-center gap-2">
                            <span class="step-number">2</span>
                            <strong class="font-bold text-gray-900 text-xs">تعديل الكميات والملاحظات</strong>
                        </div>
                        <p class="text-[12px] text-gray-500">في سلة الطلب على يسار الشاشة، اضغط (+) أو (-) لزيادة أو تقليل الكمية، ويمكنك كتابة ملاحظة التحضير (مثال: سكر زيادة، حليب خالي الدسم).</p>
                    </div>

                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5 shadow-2xs">
                        <div class="flex items-center gap-2">
                            <span class="step-number">3</span>
                            <strong class="font-bold text-gray-900 text-xs">اختيار طريقة الدفع والدفع</strong>
                        </div>
                        <p class="text-[12px] text-gray-500">اختر طريقة السداد: <strong>كاش</strong>، <strong>فيزا / بطاقة</strong>، أو <strong>إنستاباي (InstaPay)</strong> ثم اضغط على زر <strong>دفع وإنهاء الفاتورة</strong>.</p>
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200/70 text-amber-900 flex items-start gap-3">
                    <i class="fa-solid fa-lightbulb text-amber-500 text-base mt-0.5 shrink-0"></i>
                    <div class="text-xs">
                        <strong>نصيحة ذهبية:</strong> بمجرد إتمام الفاتورة، يقوم النظام بخصم المكونات والخامات المستخدمة تلقائياً من المخزن بناءً على معايير الوصفة، ويتم إرسال أمر الطباعة لطابعة الباركود أو البون فوراً.
                    </div>
                </div>
            </div>
        </div>

        {{-- س 2: تطبيق خصم أو إلغاء صنف --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="pos">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-percent"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800">نقطة البيع POS</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> شاشة البيع السريع</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أقدر أطبق خصم على الفاتورة أو ألغي صنف تم إضافته بالخطأ؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <div class="space-y-2">
                    <div class="flex items-start gap-3 bg-white p-3 rounded-2xl border border-gray-100">
                        <span class="step-number">1</span>
                        <div>
                            <strong class="font-bold text-gray-800">إلغاء صنف من السلة:</strong>
                            <p class="text-[12px] text-gray-500 mt-0.5">اضغط على أيقونة الحذف (سلة المهملات الحمراء) المجاورة للصنف داخل سلة الطلب قبل تأكيد الفاتورة، أو قلل الكمية حتى صفر وسيتم حذفه تلقائياً.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 bg-white p-3 rounded-2xl border border-gray-100">
                        <span class="step-number">2</span>
                        <div>
                            <strong class="font-bold text-gray-800">تطبيق خصم إجمالي على الفاتورة:</strong>
                            <p class="text-[12px] text-gray-500 mt-0.5">في أسفل سلة الطلبات، اضغط على حقل <strong>الخصم (Discount)</strong> وأدخل قيمة الخصم بالجنيه. سيتم إعادة احتساب الصافي فوراً وتوضيح قيمة الخصم في بون الفاتورة.</p>
                        </div>
                    </div>
                </div>
                <div class="p-3.5 rounded-2xl bg-sky-50 border border-sky-200/70 text-sky-900 flex items-start gap-3 text-xs">
                    <i class="fa-solid fa-circle-info text-sky-500 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <strong>ملاحظة للمدير:</strong> تظهر الخصومات المسجلة في تقارير الشيفت وفي شاشة التحليل الشامل لمتابعة إجمالي الخصومات الممنوحة للعملاء بدقة.
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- قسم 2: إدارة الصالة والترابيزات (Tables) --}}
        {{-- ========================================================= --}}

        {{-- س 3: فتح ترابيزة ومتابعة الطلبات المعلقة --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="tables">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-bell-concierge"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-sky-100 text-sky-800">إدارة الصالة</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> القائمة الجانبية > الترابيزات المشغولة</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أقدر أفتح ترابيزة في الصالة لعميل، وأضيف طلبات لاحقة أثناء جلسته، وأقفل الحساب؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5 shadow-2xs">
                        <div class="flex items-center gap-2">
                            <span class="step-number">1</span>
                            <strong class="font-bold text-gray-900 text-xs">فتح الترابيزة</strong>
                        </div>
                        <p class="text-[12px] text-gray-500">من شاشة POS اختر "طلب صالة" وحدد رقم الترابيزة، أو ادخل على <strong>إدارة الترابيزات</strong> واضغط على أي ترابيزة متاحة (خضراء) لتصبح مشغولة (حمراء).</p>
                    </div>

                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5 shadow-2xs">
                        <div class="flex items-center gap-2">
                            <span class="step-number">2</span>
                            <strong class="font-bold text-gray-900 text-xs">إضافة طلبات إضافية</strong>
                        </div>
                        <p class="text-[12px] text-gray-500">في أي وقت أثناء تواجد العميل، يمكنك الدخول على <strong>الترابيزات المشغولة</strong> والضغط على الترابيزة لإضافة أي مشروبات أو حلوى جديدة إلى حسابه الحالي.</p>
                    </div>

                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5 shadow-2xs">
                        <div class="flex items-center gap-2">
                            <span class="step-number">3</span>
                            <strong class="font-bold text-gray-900 text-xs">طباعة الشيك وإنهاء الجلسة</strong>
                        </div>
                        <p class="text-[12px] text-gray-500">اضغط على <strong>طباعة الشيك</strong> لتقديمه للعميل، وعند السداد اضغط <strong>إنهاء وتحصيل</strong> لتصدر الفاتورة النهائية وتعود الترابيزة متاحة فوراً.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- قسم 3: الورديات والخزينة (Shifts) --}}
        {{-- ========================================================= --}}

        {{-- س 4: بدء الوردية وإدخال العهدة --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="shifts">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">الورديات والخزينة</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> القائمة الجانبية > الشيفت (ورديتي)</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أقدر أبدأ وردية جديدة ككاشير وأسجل رصيد العهدة الافتتاحي في الخزينة؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <ol class="space-y-2.5">
                    <li class="flex items-start gap-3 bg-white p-3 rounded-2xl border border-gray-100">
                        <span class="step-number">1</span>
                        <div>
                            <strong class="font-bold text-gray-800">الدخول لصفحة الشيفت:</strong>
                            <p class="text-[12px] text-gray-500 mt-0.5">من القائمة الجانبية ادخل على تاب <strong>الشيفت (ورديتي)</strong>.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3 bg-white p-3 rounded-2xl border border-gray-100">
                        <span class="step-number">2</span>
                        <div>
                            <strong class="font-bold text-gray-800">إدخال مبلغ العهدة النقدية (Starting Cash):</strong>
                            <p class="text-[12px] text-gray-500 mt-0.5">في حقل "الرصيد الافتتاحي"، اكتب المبلغ النقدي الموجود في درج الكاشير في بداية العمل (الفكة والعهدة).</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-3 bg-white p-3 rounded-2xl border border-gray-100">
                        <span class="step-number">3</span>
                        <div>
                            <strong class="font-bold text-gray-800">بدء الوردية:</strong>
                            <p class="text-[12px] text-gray-500 mt-0.5">اضغط على زر <strong>بدء الوردية الآن</strong>، وسيتم تفعيل نقطة البيع فوراً وربط كافة الفواتير باسم الكاشير وتوقيت الوردية.</p>
                        </div>
                    </li>
                </ol>

                <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200/70 text-rose-900 flex items-start gap-3 text-xs">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <strong>تنبيه أمان:</strong> إذا لم تكن صلاحية "بدء الشيفت" مفعلة لحسابك، تواصل مع مدير النظام لتفعيل صلاحية <code>can_start_shift</code> من إدارة المستخدمين في الإعدادات.
                    </div>
                </div>
            </div>
        </div>

        {{-- س 5: إغلاق الوردية وجرد الخزينة --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="shifts">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-100 text-emerald-800">الورديات والخزينة</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> نهاية الشيفت</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أقدر أقفل الوردية في نهاية اليوم وأعمل جرد نقدية وأقارن العجز والزيادة؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <p>
                    في نهاية فترة العمل، يقوم الكاشير بعملية إغلاق الوردية عبر الخطوات التالية:
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1">
                        <strong class="font-bold text-gray-900 text-xs block">1. عد النقدية في الدرج</strong>
                        <p class="text-[12px] text-gray-500">قم بعد جميع الأوراق النقدية والعملات المعدنية الموجودة فعلياً في درج الكاشير.</p>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1">
                        <strong class="font-bold text-gray-900 text-xs block">2. إدخال النقدية الفعلية</strong>
                        <p class="text-[12px] text-gray-500">في صفحة <strong>الشيفت (ورديتي)</strong>، اكتب المبلغ الفعلي في حقل "النقدية الفعلية بالجرد (Actual Cash)".</p>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1">
                        <strong class="font-bold text-gray-900 text-xs block">3. المطابقة والتقفيل</strong>
                        <p class="text-[12px] text-gray-500">يقوم النظام تلقائياً باحتساب المعادلة: <br><code class="text-xs font-mono text-emerald-700 font-bold">العهدة + المبيعات - المصروفات</code> وتوضيح أي عجز أو زيادة، ثم اضغط <strong>إغلاق الشيفت</strong>.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- قسم 4: المنيو والوصفات (Menu & Recipes) --}}
        {{-- ========================================================= --}}

        {{-- س 6: إضافة صنف جديد وربطه بالوصفة --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="menu">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-utensils"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-orange-100 text-orange-800">المنيو والوصفات</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> القائمة الجانبية > المينيو والأصناف</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أقدر أضيف صنف جديد للمنيو وأربطه بوصفة ومكونات من المخزن للخصم التلقائي؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <div class="space-y-3">
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1">
                        <strong class="font-bold text-gray-900">أولاً: إضافة الصنف في المنيو:</strong>
                        <p class="text-[12px] text-gray-600">ادخل على <strong>المينيو</strong> ثم اضغط <strong>إضافة صنف جديد</strong>. حدد اسم الصنف (مثال: سبانش لاتيه)، القسم التابع له (قهوة باردة)، سعر البيع، وارفع صورة معبرة.</p>
                    </div>
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1">
                        <strong class="font-bold text-gray-900">ثانياً: ربط الوصفة والخامات (Recipe):</strong>
                        <p class="text-[12px] text-gray-600">ادخل على تاب <strong>الوصفات وتكاليف المكونات</strong>، واختر الصنف ثم اضف مكوناته ومعاييرها (مثال: 18 جرام بن اسبريسو، 150 مل حليب، 30 مل سيرب حليب مكثف). سيقوم النظام باحتساب تكلفة الكوب بدقة وهامش ربحه تلقائياً.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- قسم 5: المخزن والمشتريات (Inventory & Purchases) --}}
        {{-- ========================================================= --}}

        {{-- س 7: تسجيل فاتورة مشتريات وتوريد --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="inventory">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-file-invoice"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800">المخزن والمشتريات</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> القائمة الجانبية > فواتير الشراء</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أقدر أسجل فاتورة شراء جديدة من مورد وتحديث رصيد المخزن تلقائياً؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 space-y-1">
                        <span class="step-number">1</span>
                        <strong class="font-bold text-gray-900 block">اختيار المورد</strong>
                        <p class="text-[11px] text-gray-500">حدد المورد من قائمة الموردين أو أضف مورداً جديداً فوراً.</p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 space-y-1">
                        <span class="step-number">2</span>
                        <strong class="font-bold text-gray-900 block">إضافة البنود</strong>
                        <p class="text-[11px] text-gray-500">اختر أصناف المخزن، الكمية الموردة، وسعر شراء الوحدة.</p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 space-y-1">
                        <span class="step-number">3</span>
                        <strong class="font-bold text-gray-900 block">تسجيل المدفوع</strong>
                        <p class="text-[11px] text-gray-500">اكتب المبلغ المدفوع كاش، وسيقوم النظام بترحيل الباقي كآجل في حساب المورد.</p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100 space-y-1">
                        <span class="step-number">4</span>
                        <strong class="font-bold text-gray-900 block">تحديث المخزن</strong>
                        <p class="text-[11px] text-gray-500">فور الحفظ، ترتفع كميات المخزون مباشرة مع إنشاء حركة وارد في السجل.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- قسم 6: التحليل الشامل والتقارير (Analytics) --}}
        {{-- ========================================================= --}}

        {{-- س 8: قراءة واستخدام التحليل الشامل --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="analytics">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-chart-pie"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-100 text-purple-800">التحليلات والتقارير</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> القائمة الجانبية > التحليل الشامل</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أستفيد من صفحة التحليل الشامل لمعرفة ساعات الذروة والأصناف الأكثر ربحاً؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <p>
                    توفر صفحة <strong>التحليل الشامل والذكاء التشغيلي</strong> رؤية متكاملة لمالك الكافيه والمدير عبر عدة تقارير ذكية:
                </p>
                <div class="space-y-2.5">
                    <div class="bg-white p-3 rounded-2xl border border-gray-100">
                        <strong class="font-bold text-gray-900 block text-xs">1. منحنى ساعات الذروة (Peak Hours):</strong>
                        <p class="text-[12px] text-gray-500 mt-0.5">يوضح عدد الفواتير في كل ساعة من ساعات اليوم، مما يساعدك على تنظيم جداول عمل الموظفين وزيادة عدد الباريستا في ساعات الضغط.</p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100">
                        <strong class="font-bold text-gray-900 block text-xs">2. قائمة الأصناف الأكثر ربحية (Top Profit Items):</strong>
                        <p class="text-[12px] text-gray-500 mt-0.5">جدول يفرز الأصناف بحسب الهامش الربحي الصافي، لتعرف أي المشروبات يحقق أعلى عائد على تكلفة المكونات.</p>
                    </div>
                    <div class="bg-white p-3 rounded-2xl border border-gray-100">
                        <strong class="font-bold text-gray-900 block text-xs">3. توزيع قنوات الدفع ومسار الأرباح:</strong>
                        <p class="text-[12px] text-gray-500 mt-0.5">مقارنة لحظية بين مبيعات الكاش والبطاقات وإنستاباي لتسوية حسابات البنوك والخزينة النقدية بسهولة.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- قسم 7: التخصيص وإعدادات الحساب (Customization & Main Tab) --}}
        {{-- ========================================================= --}}

        {{-- س 9: اختيار التاب الأساسية وترتيب المجموعات --}}
        <div class="guide-card bg-white rounded-3xl border border-gray-200/80 shadow-xs overflow-hidden transition" data-category="customization">
            <button type="button" onclick="toggleAccordion(this)"
                class="guide-accordion-header w-full p-5 sm:p-6 flex items-center justify-between text-right gap-4 cursor-pointer select-none">
                <div class="flex items-center gap-3.5">
                    <span class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-bold shrink-0">
                        <i class="fa-solid fa-sliders"></i>
                    </span>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-100 text-rose-800">التخصيص والناف بار</span>
                            <span class="text-[11px] text-gray-400 hidden sm:inline"><i class="fa-solid fa-location-dot text-[10px]"></i> القائمة الجانبية > الإعدادات</span>
                        </div>
                        <h3 class="text-sm sm:text-base font-black text-gray-800">
                            إزاي أختار التاب الأساسية اللي تفتح تلقائياً أول ما أدخل النظام (Main Tab)، وإزاي أرتب مجموعات القائمة الجانبية؟
                        </h3>
                    </div>
                </div>
                <i class="fa-solid fa-chevron-down guide-chevron text-gray-400 text-sm shrink-0"></i>
            </button>

            <div class="guide-accordion-content hidden p-5 sm:p-6 border-t border-gray-100 bg-gray-50/40 space-y-4 text-xs sm:text-sm text-gray-700 leading-relaxed">
                <div class="space-y-3">
                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="step-number">1</span>
                            <strong class="font-bold text-gray-900 text-xs">اختيار التاب الأساسية (Main Tab):</strong>
                        </div>
                        <p class="text-[12px] text-gray-600">
                            ادخل على صفحة <strong>الإعدادات</strong>، وفي قسم "التاب الأساسية المفضلة"، اختر التاب التي تريدها أن تكون الرئيسية (مثال: المدير يمكنه اختيار <strong>لوحة التحكم</strong> أو <strong>التحليل الشامل</strong>، والكاشير يختار <strong>البيع POS</strong>).
                            <br>
                            <span class="text-sky-700 font-bold">الميزة:</span> التاب المختارة ستظهر في أعلى القائمة الجانبية بتصميم Hero المميز والأوضح، وسيقوم النظام بتوجيهك إليها فور تسجيل الدخول!
                        </p>
                    </div>

                    <div class="bg-white p-3.5 rounded-2xl border border-gray-100 space-y-1.5">
                        <div class="flex items-center gap-2">
                            <span class="step-number">2</span>
                            <strong class="font-bold text-gray-900 text-xs">ترتيب المجموعات الـ 5 بالقائمة الجانبية:</strong>
                        </div>
                        <p class="text-[12px] text-gray-600">
                            في نفس صفحة الإعدادات (أو من خلال زر تخصيص القائمة السريع بالناف بار)، استخدم أزرار الصعود والهبوط لترتيب المجموعات الخمسة (العمليات، المالية، المخزن، التحليلات، الإدارة) بالطريقة التي تناسب وتسهل عملك اليومي، واضغط <strong>حفظ الترتيب</strong>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- سكريبت البحث والفلترة والأكورديون التفاعلي --}}
<script>
    // فتح وإغلاق الأكورديون
    function toggleAccordion(btn) {
        const content = btn.nextElementSibling;
        const isOpen = btn.classList.contains('is-open');

        if (isOpen) {
            btn.classList.remove('is-open');
            content.classList.add('hidden');
        } else {
            btn.classList.add('is-open');
            content.classList.remove('hidden');
        }
    }

    // توسيع كافة الأسئلة
    function expandAllQuestions() {
        document.querySelectorAll('.guide-accordion-header').forEach(btn => {
            btn.classList.add('is-open');
            btn.nextElementSibling.classList.remove('hidden');
        });
    }

    // طي كافة الأسئلة
    function collapseAllQuestions() {
        document.querySelectorAll('.guide-accordion-header').forEach(btn => {
            btn.classList.remove('is-open');
            btn.nextElementSibling.classList.add('hidden');
        });
    }

    // الفلترة حسب القسم
    function filterCategory(category, btn) {
        // تحديث حالة الأزرار
        document.querySelectorAll('.category-pill-btn').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        const cards = document.querySelectorAll('.guide-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const cardCat = card.getAttribute('data-category');
            if (category === 'all' || cardCat === category) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        checkNoResults(visibleCount);
    }

    // البحث الفوري بالكلمات المفتاحية
    function filterQuestions() {
        const query = document.getElementById('guideSearchInput').value.trim().toLowerCase();
        const cards = document.querySelectorAll('.guide-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const text = card.textContent.toLowerCase();
            if (query === '' || text.includes(query)) {
                card.style.display = 'block';
                visibleCount++;
                // في حالة البحث، نفتح السؤال تلقائياً ليرى المستخدم الإجابة
                if (query.length > 2) {
                    const btn = card.querySelector('.guide-accordion-header');
                    const content = card.querySelector('.guide-accordion-content');
                    if (btn && content) {
                        btn.classList.add('is-open');
                        content.classList.remove('hidden');
                    }
                }
            } else {
                card.style.display = 'none';
            }
        });

        const countElem = document.getElementById('guideResultsCount');
        if (countElem) {
            countElem.textContent = query.length > 0 ? `(${visibleCount} نتيجة)` : '';
        }

        checkNoResults(visibleCount);
    }

    function checkNoResults(count) {
        const alert = document.getElementById('noResultsAlert');
        if (alert) {
            if (count === 0) {
                alert.classList.remove('hidden');
            } else {
                alert.classList.add('hidden');
            }
        }
    }
</script>
@endsection
