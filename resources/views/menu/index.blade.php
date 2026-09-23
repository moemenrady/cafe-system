@extends('layouts.app')

@section('page_title', 'مينيو UNO Cafe - قائمة الأصناف')

@section('content')
<div class="container mx-auto space-y-6 pb-16" dir="rtl">

    {{-- رأس الصفحة مع الأزرار والإحصائيات --}}
    <div class="bg-white p-5 sm:p-6 rounded-3xl border border-gray-100 shadow-xs flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <div>
                    <h2 class="text-lg sm:text-xl font-black text-gray-800">قائمة أصناف ومنتجات UNO Cafe</h2>
                    <p class="text-xs text-gray-400 mt-0.5">مينيو الكافيه الرسمي المنظم بالأقسام والأصناف والأسعار المعتمدة للبيع</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
            {{-- زر فتح وتحميل ملف المينيو PDF الأصلي --}}
            <a href="{{ route('menu.pdf') }}" target="_blank"
               class="flex-1 sm:flex-none bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 active:scale-95 text-white font-bold px-4 py-2.5 rounded-2xl text-xs flex items-center justify-center gap-2 shadow-md shadow-rose-600/25 transition">
                <i class="fa-solid fa-file-pdf text-base"></i>
                <span>عرض منيو UNO PDF الأصلي</span>
            </a>

            <button type="button" onclick="openPdfModal()"
                    class="bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold px-3 py-2.5 rounded-2xl text-xs flex items-center gap-1.5 border border-rose-200 transition"
                    title="معاينة المينيو داخل الصفحة">
                <i class="fa-solid fa-eye"></i>
                <span class="hidden sm:inline">معاينة سريعة</span>
            </button>

            @if(auth()->user() && auth()->user()->isManager())
                <a href="{{ route('menu.create') }}"
                   class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold px-4 py-2.5 rounded-2xl text-xs flex items-center justify-center gap-2 shadow-md shadow-blue-600/25 transition">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>إضافة منتج جديد</span>
                </a>
            @endif
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-exclamation text-rose-500 text-lg"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- بطاقات الإحصائيات السريعة --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400">عدد الأقسام</p>
                <h4 class="text-lg sm:text-xl font-black text-gray-800 mt-0.5">{{ $categories->count() }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400">إجمالي المنتجات</p>
                <h4 class="text-lg sm:text-xl font-black text-gray-800 mt-0.5">{{ $totalMenuItems }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-utensils"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400">منتجات متاحة للبيع</p>
                <h4 class="text-lg sm:text-xl font-black text-emerald-600 mt-0.5">{{ $availableCount }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400">منتجات غير متاحة</p>
                <h4 class="text-lg sm:text-xl font-black text-rose-500 mt-0.5">{{ $unavailableCount }}</h4>
            </div>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center text-sm">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>
    </div>

    {{-- شريط التصفية السريعة والبحث --}}
    <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs space-y-3">
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <i class="fa-solid fa-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" id="menuSearchInput" onkeyup="filterMenuSearch()"
                       placeholder="ابحث عن اسم أي منتج أو قسم في المينيو..."
                       class="w-full bg-gray-50 border border-gray-200 rounded-2xl pr-10 pl-4 py-2.5 text-xs sm:text-sm outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition-all">
            </div>
            <div class="flex items-center gap-2 self-end sm:self-auto">
                <button type="button" onclick="collapseAllCategories()" class="px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold transition">
                    <i class="fa-solid fa-chevron-up ml-1 text-[10px]"></i> طي الكل
                </button>
                <button type="button" onclick="expandAllCategories()" class="px-3 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold transition">
                    <i class="fa-solid fa-chevron-down ml-1 text-[10px]"></i> فتح الكل
                </button>
            </div>
        </div>

        {{-- أزرار الأقسام السريعة (Category Filter Pills) --}}
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs no-scrollbar">
            <button type="button" onclick="filterCategory('all')"
                    class="category-pill active px-3.5 py-1.5 rounded-xl font-bold bg-amber-500 text-white shadow-xs shrink-0 transition"
                    data-cat="all">
                🌟 كل الأقسام ({{ $categories->count() }})
            </button>
            @foreach($categories as $category)
                <button type="button" onclick="filterCategory('{{ $category->id }}')"
                        class="category-pill px-3.5 py-1.5 rounded-xl font-bold bg-gray-100 hover:bg-gray-200 text-gray-700 shrink-0 transition"
                        data-cat="{{ $category->id }}">
                    {{ $category->name }} ({{ $category->menuItems->count() }})
                </button>
            @endforeach
        </div>
    </div>

    {{-- عرض المينيو المنظم: كل صنف وتحته منتجاته (Grouped by Category) --}}
    <div id="categoriesContainer" class="space-y-6">
        @forelse($categories as $category)
            <div class="category-block bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden transition-all duration-300"
                 id="cat-block-{{ $category->id }}" data-category-id="{{ $category->id }}">

                {{-- عنوان القسم الرئيسي --}}
                <div class="p-4 sm:p-5 bg-gradient-to-l from-gray-50 via-white to-gray-50/60 border-b border-gray-100 flex items-center justify-between cursor-pointer select-none"
                     onclick="toggleCategorySection('{{ $category->id }}')">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center text-base font-bold shadow-2xs">
                            <i class="fa-solid fa-utensils"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base sm:text-lg font-black text-gray-900 category-title">{{ $category->name }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 text-[10px] font-black">
                                    {{ $category->menuItems->count() }} منتجات
                                </span>
                            </div>
                            <span class="text-[11px] text-gray-400">قسم معتمد في مينيو UNO Cafe</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        @if(auth()->user() && auth()->user()->isManager())
                            <a href="{{ route('menu.create', ['category_id' => $category->id]) }}"
                               class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white hover:bg-gray-100 border border-gray-200 text-gray-700 text-xs font-bold transition"
                               onclick="event.stopPropagation()">
                                <i class="fa-solid fa-plus text-[10px] text-blue-600"></i>
                                <span>إضافة صنف للقسم</span>
                            </a>
                        @endif
                        <i class="fa-solid fa-chevron-down text-gray-400 transition-transform duration-300 text-sm"
                           id="cat-chevron-{{ $category->id }}"></i>
                    </div>
                </div>

                {{-- قائمة منتجات هذا القسم (Cards Grid) --}}
                <div class="p-4 sm:p-6" id="cat-body-{{ $category->id }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5 sm:gap-4">
                        @forelse($category->menuItems as $item)
                            <div class="menu-product-card bg-gray-50/70 hover:bg-white border border-gray-100 hover:border-amber-200 rounded-2xl p-4 transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 flex flex-col justify-between"
                                 data-product-name="{{ strtolower($item->name) }}">

                                <div>
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        {{-- صورة أو أيقونة المنتج --}}
                                        <div class="w-10 h-10 rounded-xl {{ $item->image_url ? 'overflow-hidden' : 'bg-white text-amber-600 border border-gray-200' }} flex items-center justify-center text-sm shrink-0 shadow-2xs">
                                            @if($item->image_url)
                                                <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                            @else
                                                <i class="fa-solid fa-mug-hot"></i>
                                            @endif
                                        </div>

                                        {{-- حالة التوفر --}}
                                        @if($item->is_available)
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                متاح
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200 text-[10px] font-bold flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                غير متاح
                                            </span>
                                        @endif
                                    </div>

                                    <h4 class="font-extrabold text-gray-800 text-sm product-name leading-snug">
                                        {{ $item->name }}
                                    </h4>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $category->name }}</p>
                                </div>

                                {{-- السعر والإجراءات --}}
                                <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                                    <div>
                                        <span class="text-xs text-gray-400 font-bold">السعر:</span>
                                        <span class="text-base font-black text-gray-900 font-mono">
                                            {{ number_format($item->price, 2) }}
                                        </span>
                                        <span class="text-[10px] font-bold text-gray-500">ج.م</span>
                                    </div>

                                    @if(auth()->user() && auth()->user()->isManager())
                                        <div class="flex items-center gap-1">
                                            <a href="{{ route('menu.edit', $item->id) }}"
                                               class="w-7 h-7 rounded-lg bg-white hover:bg-amber-50 text-gray-500 hover:text-amber-600 border border-gray-200 flex items-center justify-center transition"
                                               title="تعديل">
                                                <i class="fa-solid fa-pen text-[10px]"></i>
                                            </a>
                                            <form action="{{ route('menu.destroy', $item->id) }}" method="POST"
                                                  onsubmit="return confirm('هل تريد حذف صنف {{ $item->name }} من المينيو؟');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="w-7 h-7 rounded-lg bg-white hover:bg-rose-50 text-gray-500 hover:text-rose-600 border border-gray-200 flex items-center justify-center transition"
                                                        title="حذف">
                                                    <i class="fa-solid fa-trash text-[10px]"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @empty
                            <div class="col-span-full py-8 text-center text-gray-400 text-xs font-bold">
                                لا توجد منتجات مسجلة في هذا القسم حالياً.
                            </div>
                        @endforelse
                    </div>
                </div>

            </div>
        @empty
            <div class="bg-white p-12 rounded-3xl border border-gray-100 text-center text-gray-400">
                <i class="fa-solid fa-utensils text-4xl text-gray-200 mb-3 block"></i>
                <h3 class="text-base font-bold text-gray-700">لا توجد أقسام أو منتجات في المينيو</h3>
                <p class="text-xs text-gray-400 mt-1">يمكنك تشغيل السيدر لإضافة قائمة منتجات UNO Cafe الرسمية.</p>
            </div>
        @endforelse
    </div>

</div>

{{-- مودال عرض ملف المينيو PDF الأصلي داخل الصفحة --}}
<div id="pdfViewerModal" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4 bg-black/70 backdrop-blur-xs hidden transition-opacity duration-300">
    <div class="bg-white rounded-3xl max-w-4xl w-full h-[90vh] shadow-2xl flex flex-col overflow-hidden text-right border border-gray-100 animate-slide-in" dir="rtl">
        {{-- رأس النافذة --}}
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg shadow-inner">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-800">منيو UNO Cafe الأصلي (PDF)</h3>
                    <p class="text-[11px] text-gray-400 mt-0.5">معاينة الملف الطباعي الأصلي لأسعار وأصناف الكافيه</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('menu.pdf') }}" target="_blank"
                   class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                    <span>فتح في صفحة جديدة</span>
                </a>
                <button type="button" onclick="closePdfModal()"
                        class="w-8 h-8 rounded-xl text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
        </div>

        {{-- إطار عرض الـ PDF --}}
        <div class="flex-1 bg-gray-100 p-2 overflow-hidden">
            <iframe src="{{ route('menu.pdf') }}" class="w-full h-full rounded-2xl border border-gray-200 bg-white" frameborder="0"></iframe>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleCategorySection(catId) {
        const body = document.getElementById(`cat-body-${catId}`);
        const chevron = document.getElementById(`cat-chevron-${catId}`);
        if (body.classList.contains('hidden')) {
            body.classList.remove('hidden');
            chevron.classList.remove('-rotate-90');
        } else {
            body.classList.add('hidden');
            chevron.classList.add('-rotate-90');
        }
    }

    function collapseAllCategories() {
        document.querySelectorAll('[id^="cat-body-"]').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('[id^="cat-chevron-"]').forEach(el => el.classList.add('-rotate-90'));
    }

    function expandAllCategories() {
        document.querySelectorAll('[id^="cat-body-"]').forEach(el => el.classList.remove('hidden'));
        document.querySelectorAll('[id^="cat-chevron-"]').forEach(el => el.classList.remove('-rotate-90'));
    }

    function filterCategory(catId) {
        // تحديث أزرار الـ Pills
        document.querySelectorAll('.category-pill').forEach(btn => {
            btn.classList.remove('bg-amber-500', 'text-white', 'shadow-xs', 'active');
            btn.classList.add('bg-gray-100', 'text-gray-700');
        });

        const activeBtn = document.querySelector(`.category-pill[data-cat="${catId}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-100', 'text-gray-700');
            activeBtn.classList.add('bg-amber-500', 'text-white', 'shadow-xs', 'active');
        }

        const blocks = document.querySelectorAll('.category-block');
        if (catId === 'all') {
            blocks.forEach(b => b.classList.remove('hidden'));
        } else {
            blocks.forEach(b => {
                if (b.dataset.categoryId === catId) {
                    b.classList.remove('hidden');
                    // فتح القسم المستهدف تلقائياً
                    const body = b.querySelector('[id^="cat-body-"]');
                    const chevron = b.querySelector('[id^="cat-chevron-"]');
                    if (body) body.classList.remove('hidden');
                    if (chevron) chevron.classList.remove('-rotate-90');
                } else {
                    b.classList.add('hidden');
                }
            });
        }
    }

    function filterMenuSearch() {
        const query = document.getElementById('menuSearchInput').value.toLowerCase().trim();
        const blocks = document.querySelectorAll('.category-block');

        blocks.forEach(block => {
            const catTitle = block.querySelector('.category-title').textContent.toLowerCase();
            const productCards = block.querySelectorAll('.menu-product-card');
            let hasVisibleProduct = false;

            productCards.forEach(card => {
                const prodName = card.dataset.productName;
                if (prodName.includes(query) || catTitle.includes(query)) {
                    card.classList.remove('hidden');
                    hasVisibleProduct = true;
                } else {
                    card.classList.add('hidden');
                }
            });

            if (hasVisibleProduct || catTitle.includes(query)) {
                block.classList.remove('hidden');
                const body = block.querySelector('[id^="cat-body-"]');
                if (body && query.length > 0) body.classList.remove('hidden');
            } else {
                block.classList.add('hidden');
            }
        });
    }

    function openPdfModal() {
        const modal = document.getElementById('pdfViewerModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closePdfModal() {
        const modal = document.getElementById('pdfViewerModal');
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closePdfModal();
    });
</script>
@endpush
@endsection
