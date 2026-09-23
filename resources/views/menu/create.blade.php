@extends('layouts.app')

@section('page_title', 'إضافة صنف جديد للمينيو')

@section('content')
<div class="container mx-auto max-w-2xl px-4 py-4" dir="rtl">
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-gray-100 shadow-md">
        
        {{-- الرأس --}}
        <div class="flex items-center justify-between pb-5 border-b border-gray-100 mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                    <i class="fa-solid fa-mug-hot"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black text-gray-800">إضافة صنف جديد للمينيو</h2>
                    <p class="text-xs text-gray-400">أدخل بيانات المنتج وسعره وصورته للعرض في الـ POS</p>
                </div>
            </div>
            <a href="{{ route('menu.index') }}"
                class="text-gray-400 hover:text-gray-600 w-9 h-9 rounded-xl bg-gray-50 hover:bg-gray-100 flex items-center justify-center transition border border-gray-100"
                title="إلغاء والعودة">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
        </div>

        <form action="{{ route('menu.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- الاسم والقسم --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5" for="name">
                        اسم المنتج <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}"
                        class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl px-3.5 py-2.5 text-xs font-medium outline-hidden transition @error('name') border-red-500 @enderror"
                        placeholder="مثال: لاتيه فانيلا، كريب شوكولاتة" required>
                    @error('name')
                        <p class="text-red-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5" for="category_id">
                        القسم / التصنيف <span class="text-red-500">*</span>
                    </label>
                    <select name="category_id" id="category_id"
                        class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl px-3.5 py-2.5 text-xs font-bold outline-hidden transition @error('category_id') border-red-500 @enderror"
                        required>
                        <option value="">-- اختر القسم --</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-red-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- السعر والحالة --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5" for="price">
                        السعر للجمهور (ج.م) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="number" step="0.25" min="0" name="price" id="price" value="{{ old('price') }}"
                            class="w-full bg-gray-50 focus:bg-white text-gray-800 border border-gray-200 focus:border-blue-500 rounded-xl pr-3.5 pl-10 py-2.5 text-xs font-black outline-hidden transition @error('price') border-red-500 @enderror"
                            placeholder="0.00" required>
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-bold text-gray-400">ج.م</span>
                    </div>
                    @error('price')
                        <p class="text-red-500 text-[11px] font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="pt-5">
                    <label class="relative flex items-center gap-3 p-3 bg-gray-50 border border-gray-200 rounded-2xl cursor-pointer hover:bg-gray-100/60 transition">
                        <input type="checkbox" name="is_available" value="1" {{ old('is_available', '1') == '1' ? 'checked' : '' }}
                            class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <div>
                            <span class="block text-xs font-black text-gray-800">متاح للطلب الآن</span>
                            <span class="block text-[11px] text-gray-400">يظهر فوراً في شاشة الكاشير (POS)</span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- منطقة رفع ومعاينة صورة المنتج باحترافية --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">
                    صورة المنتج (اختياري)
                </label>
                
                {{-- مربع السحب والإفلات / الاختيار --}}
                <div id="dropzoneContainer"
                    class="relative border-2 border-dashed border-gray-300 hover:border-blue-400 bg-gray-50 hover:bg-blue-50/20 rounded-2xl p-6 text-center transition cursor-pointer">
                    
                    <input type="file" name="image" id="imageInput" accept="image/png, image/jpeg, image/jpg, image/webp"
                        class="absolute inset-0 opacity-0 cursor-pointer w-full h-full z-10"
                        onchange="handleImagePreview(this)">

                    {{-- الحالة الافتراضية قبل اختيار صورة --}}
                    <div id="uploadPlaceholder" class="space-y-2">
                        <div class="w-12 h-12 rounded-2xl bg-blue-100/80 text-blue-600 flex items-center justify-center mx-auto text-xl">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="text-xs font-bold text-gray-700">
                            انقر لاختيار صورة، أو اسحب الصورة وأفلتها هنا
                        </div>
                        <p class="text-[11px] text-gray-400">
                            الصيغ المدعومة: PNG, JPG, JPEG, WEBP (الحد الأقصى: 5 ميجابايت)
                        </p>
                    </div>

                    {{-- المعاينة الفورية للصورة بعد اختيارها --}}
                    <div id="previewContainer" class="hidden flex-col items-center justify-center space-y-2">
                        <div class="relative w-32 h-32 rounded-2xl overflow-hidden border-2 border-blue-400 shadow-md">
                            <img id="imagePreview" src="#" alt="معاينة الصورة" class="w-full h-full object-cover">
                            <button type="button" onclick="cancelImageSelection(event)"
                                class="absolute top-1 left-1 w-6 h-6 rounded-full bg-red-600 text-white flex items-center justify-center text-xs shadow hover:bg-red-700 z-20"
                                title="إلغاء وحذف الصورة">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <p id="fileName" class="text-xs font-bold text-gray-700 truncate max-w-xs"></p>
                        <p id="fileSize" class="text-[10px] text-gray-400"></p>
                    </div>
                </div>

                @error('image')
                    <p class="text-red-500 text-[11px] font-bold mt-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- أزرار الإجراءات --}}
            <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black py-3 px-6 rounded-xl text-xs transition shadow-md shadow-blue-500/25 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check"></i>
                    <span>حفظ ونشر المنتج</span>
                </button>
                <a href="{{ route('menu.index') }}"
                    class="px-5 py-3 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold text-xs transition">
                    إلغاء
                </a>
            </div>

        </form>

    </div>
</div>

@push('scripts')
<script>
    function handleImagePreview(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];

            // تحقق من الحجم (5 ميجابايت)
            if (file.size > 5 * 1024 * 1024) {
                alert('حجم الصورة كبير جداً! الحد الأقصى المسموح به هو 5 ميجابايت.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('imagePreview').src = e.target.result;
                document.getElementById('fileName').textContent = file.name;
                document.getElementById('fileSize').textContent = (file.size / 1024).toFixed(1) + ' كيلوبايت';
                document.getElementById('uploadPlaceholder').classList.add('hidden');
                document.getElementById('previewContainer').classList.remove('hidden');
                document.getElementById('previewContainer').classList.add('flex');
            };
            reader.readAsDataURL(file);
        }
    }

    function cancelImageSelection(e) {
        e.stopPropagation();
        e.preventDefault();
        const input = document.getElementById('imageInput');
        input.value = '';
        document.getElementById('imagePreview').src = '#';
        document.getElementById('previewContainer').classList.add('hidden');
        document.getElementById('previewContainer').classList.remove('flex');
        document.getElementById('uploadPlaceholder').classList.remove('hidden');
    }
</script>
@endpush
@endsection
