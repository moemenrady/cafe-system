@extends('layouts.app')

@section('title', 'إدارة الوصفات')
@section('page_title', 'معيار الوصفات (المكونات والتكلفة)')

@section('content')
    <!-- استدعاء مكتبة Select2 للبحث في الخامات -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        /* تعديلات تصميمية لتتناسب Select2 مع Tailwind */
        .select2-container--default .select2-selection--single {
            border-color: #e5e7eb !important;
            border-radius: 0.75rem !important;
            height: 42px !important;
            display: flex;
            align-items: center;
        }

        .select2-container[dir="rtl"] .select2-selection--single .select2-selection__rendered {
            padding-right: 12px;
            font-size: 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px !important;
            left: 10px !important;
            right: auto !important;
        }

        .select2-dropdown {
            border-color: #e5e7eb !important;
            border-radius: 0.75rem !important;
        }
    </style>

    <div
        class="flex flex-col md:flex-row justify-between items-center bg-white p-4 rounded-2xl border border-gray-100 shadow-sm mb-4 gap-4">
        <div>
            <h2 class="text-lg font-bold text-gray-800">مكونات المشروبات وتكلفتها</h2>
            <p class="text-xs text-gray-400 mt-1">اضغط على أي منتج لإضافة أو تعديل الوصفة (الريسبي) الخاصة به.</p>
        </div>
    </div>

    @if (session('success'))
        <div
            class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl p-4 mb-6 flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-check"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- شبكة المنتجات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($menus as $item)
            <!-- الكارت أصبح قابل للضغط (Cursor Pointer) -->
            <div onclick="openRecipeModal({{ $item->id }}, '{{ $item->name }}', {{ json_encode($item->recipes) }})"
                class="bg-white border cursor-pointer border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-lg hover:border-blue-200 transform hover:-translate-y-1 transition-all duration-300 flex flex-col justify-between group relative">

                <!-- علامة توضح حالة المنتج -->
                @if ($item->recipes->count() > 0)
                    <span class="absolute top-4 left-4 bg-emerald-100 text-emerald-600 p-1.5 rounded-full shadow-sm"
                        title="يحتوي على وصفة">
                        <i class="fa-solid fa-check text-xs"></i>
                    </span>
                @else
                    <span class="absolute top-4 left-4 bg-rose-100 text-rose-600 p-1.5 rounded-full shadow-sm animate-pulse"
                        title="يحتاج إضافة وصفة">
                        <i class="fa-solid fa-plus text-xs"></i>
                    </span>
                @endif

                <div>
                    <div class="flex items-center gap-4 mb-5">
                        @if ($item->image)
                            <img src="{{ asset('storage/' . $item->image) }}"
                                class="w-14 h-14 rounded-xl object-cover border border-gray-100 shadow-sm group-hover:scale-105 transition-transform">
                        @else
                            <div
                                class="w-14 h-14 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400 shadow-sm group-hover:scale-105 transition-transform">
                                <i class="fa-solid fa-mug-hot text-xl"></i>
                            </div>
                        @endif
                        <div>
                            <h3 class="font-bold text-sm text-gray-800">{{ $item->name }}</h3>
                            <span
                                class="inline-block mt-1 text-[10px] bg-sky-50 text-sky-600 px-2 py-0.5 rounded-md font-medium border border-sky-100">
                                {{ $item->category->name ?? 'بدون قسم' }}
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-dashed border-gray-200 pt-4 space-y-2.5">
                        @if ($item->recipes->count() > 0)
                            <p class="text-[11px] font-bold text-gray-500 mb-2">المكونات ({{ $item->recipes->count() }}):
                            </p>
                            @foreach ($item->recipes->take(3) as $recipe)
                                <div
                                    class="flex justify-between items-center bg-gray-50/80 px-3 py-2 rounded-lg border border-gray-100">
                                    <span
                                        class="text-xs font-bold text-gray-700 truncate w-2/3">{{ $recipe->inventoryItem->name ?? 'خامة محذوفة' }}</span>
                                    <span class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md text-[10px]">
                                        {{ floatval($recipe->quantity_used) }} {{ $recipe->inventoryItem->unit ?? '' }}
                                    </span>
                                </div>
                            @endforeach
                            @if ($item->recipes->count() > 3)
                                <p class="text-center text-[10px] text-gray-400 mt-2">وأكثر...</p>
                            @endif
                        @else
                            <div class="text-center py-4 bg-amber-50/50 border border-amber-100 border-dashed rounded-xl">
                                <i class="fa-solid fa-flask text-amber-400 mb-1 block text-lg"></i>
                                <p class="text-[11px] text-amber-600 font-medium">اضغط هنا لإضافة مكونات الوصفة</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-2 gap-3">
                    <div class="bg-rose-50 border border-rose-100 rounded-xl p-2 text-center">
                        <span class="block text-[10px] font-bold text-rose-500 mb-0.5">التكلفة</span>
                        <span class="text-xs font-black text-rose-700">{{ number_format($item->recipe_cost, 2) }}</span>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-2 text-center">
                        <span class="block text-[10px] font-bold text-emerald-500 mb-0.5">سعر البيع</span>
                        <span class="text-xs font-black text-emerald-700">{{ number_format($item->price, 2) }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full text-center py-10">لا توجد منتجات.</div>
        @endforelse
    </div>

    <!-- نافذة تعديل / إضافة الوصفة (Modal) -->
    <div id="recipeModal"
        class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 transition-opacity">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden transform scale-95 transition-transform duration-300"
            id="modalContent">
            <!-- Modal Header -->
            <div class="flex justify-between items-center p-5 border-b border-gray-100 bg-gray-50/50">
                <div>
                    <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid fa-blender text-blue-600"></i>
                        وصفة المنتج: <span id="modalProductName" class="text-blue-600"></span>
                    </h2>
                </div>
                <button type="button" onclick="closeRecipeModal()"
                    class="text-gray-400 hover:text-red-500 hover:bg-red-50 p-2 rounded-xl transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Modal Body (Form) -->
            <form id="recipeForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="p-6 max-h-[60vh] overflow-y-auto" dir="rtl">
                    <div
                        class="bg-blue-50 text-blue-700 text-xs p-3 rounded-xl mb-4 border border-blue-100 flex items-start gap-2">
                        <i class="fa-solid fa-circle-info mt-0.5"></i>
                        <p>يمكنك البحث عن الخامات بكتابة اسمها. تأكد من إدخال الكمية بدقة (مثال: للجرامات اكتب 0.05 لـ 50
                            جرام إذا كانت الوحدة كجم).</p>
                    </div>

                    <div id="ingredients-container" class="space-y-3">
                        <!-- سيتم إضافة الصفوف هنا عن طريق الجافاسكريبت -->
                    </div>

                    <button type="button" onclick="addEmptyRow()"
                        class="mt-4 w-full flex justify-center items-center gap-2 text-xs text-blue-600 hover:text-white font-bold border-2 border-dashed border-blue-200 hover:border-blue-600 hover:bg-blue-600 py-3 rounded-xl transition-all">
                        <i class="fa-solid fa-plus"></i> إضافة خامة للوصفة
                    </button>
                </div>

                <!-- Modal Footer -->
                <div class="p-5 border-t border-gray-100 bg-gray-50/50 flex gap-3 justify-end">
                    <button type="button" onclick="closeRecipeModal()"
                        class="px-5 py-2.5 text-xs font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition">
                        إلغاء
                    </button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 border border-transparent rounded-xl hover:bg-blue-700 shadow-sm shadow-blue-200 transition flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> حفظ الوصفة
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- استدعاء jQuery و Select2 (ضروري لعمل Select2) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        // نقل بيانات الخامات من السيرفر للجافاسكريبت لبناء القوائم المنسدلة
        const inventories = @json($inventories);
        const modal = document.getElementById('recipeModal');
        const modalContent = document.getElementById('modalContent');
        const form = document.getElementById('recipeForm');
        const container = document.getElementById('ingredients-container');

        // دالة فتح المودال وتعبئته بالبيانات
        function openRecipeModal(menuId, menuName, existingRecipes) {
            // تحديث اسم المنتج في الهيدر
            document.getElementById('modalProductName').textContent = menuName;

            // تحديث مسار الفورم ليذهب لدالة التحديث الصحيحة
            form.action = `/recipes/update/${menuId}`;

            // تفريغ المكونات السابقة في المودال
            container.innerHTML = '';

            // إذا كان يوجد وصفة مسجلة مسبقاً، ارسمها
            if (existingRecipes && existingRecipes.length > 0) {
                existingRecipes.forEach(recipe => {
                    addRowHTML(recipe.inventory_item_id, recipe.quantity_used);
                });
            } else {
                // إذا لم يوجد، افتح له صف واحد فارغ كبداية
                addEmptyRow();
            }

            // إظهار المودال بأنيميشن لطيف
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95');
                modalContent.classList.add('scale-100');
            }, 10);

            // تفعيل Select2 على جميع حقول الاختيار الموجودة حالياً
            initSelect2();
        }

        // إغلاق المودال
        function closeRecipeModal() {
            modalContent.classList.remove('scale-100');
            modalContent.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        // إضافة صف فارغ جديد
        function addEmptyRow() {
            addRowHTML('', '');
            initSelect2(); // إعادة تفعيل Select2 للحقل الجديد
        }

        // بناء الـ HTML لصف المكون (مع قيم اختيارية)
        function addRowHTML(selectedInvId, quantity) {
            let optionsHTML = '<option value="">بحث واختيار الخامة...</option>';

            inventories.forEach(inv => {
                const selected = (inv.id == selectedInvId) ? 'selected' : '';
                optionsHTML +=
                    `<option value="${inv.id}" ${selected}>${inv.name} (${inv.unit}) - السعر: ${inv.unit_price}</option>`;
            });

            const row = document.createElement('div');
            row.className =
                'flex flex-col sm:flex-row gap-3 ingredient-row items-center bg-white p-3 rounded-xl border border-gray-100 shadow-sm relative';

            row.innerHTML = `
                <div class="w-full sm:w-7/12">
                    <label class="block text-[10px] text-gray-500 mb-1">المادة الخام</label>
                    <select name="inventory_ids[]" class="w-full select2-searchable" required>
                        ${optionsHTML}
                    </select>
                </div>
                <div class="w-full sm:w-4/12">
                    <label class="block text-[10px] text-gray-500 mb-1">الكمية للطلب الواحد</label>
                    <input type="number" step="0.001" name="quantities[]" value="${quantity}" placeholder="مثال: 1.5" class="w-full border-gray-200 rounded-xl px-3 py-2 text-xs bg-gray-50 focus:bg-white focus:ring-1 focus:ring-blue-400 focus:border-blue-400 h-[42px]" required>
                </div>
                <div class="w-full sm:w-1/12 flex sm:justify-end justify-center mt-4 sm:mt-5">
                    <button type="button" class="text-rose-400 hover:text-rose-600 hover:bg-rose-50 p-2 rounded-lg transition" onclick="removeRow(this)" title="حذف الخامة">
                        <i class="fa-solid fa-trash text-sm"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
        }

        // حذف صف من المودال
        function removeRow(btn) {
            const totalRows = container.querySelectorAll('.ingredient-row').length;
            // يمكنك السماح بحذف كل الصفوف إذا كان المستخدم يريد مسح الوصفة بالكامل، 
            // أو ترك صف واحد على الأقل. هنا سمحنا بمسح كل شيء لإتاحة تفريغ الوصفة.
            const row = btn.closest('.ingredient-row');

            // تدمير Select2 من العنصر قبل حذفه من الـ DOM لتجنب تسريب الذاكرة
            $(row).find('.select2-searchable').select2('destroy');
            row.remove();

            if (container.querySelectorAll('.ingredient-row').length === 0) {
                addEmptyRow(); // إجبار وجود صف واحد على الأقل كشكل
            }
        }

        // دالة تشغيل Select2
        function initSelect2() {
            $('.select2-searchable').select2({
                dir: "rtl",
                width: '100%',
                placeholder: "ابحث عن خامة...",
                language: {
                    noResults: function() {
                        return "لم يتم العثور على خامة";
                    }
                }
            });
        }

        // إغلاق المودال عند الضغط خارجه (على الخلفية السوداء)
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRecipeModal();
            }
        });
    </script>
@endsection
