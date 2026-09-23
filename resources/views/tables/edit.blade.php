@extends('layouts.app')

@section('page_title', 'تعديل الطاولة')

@section('content')
<div class="container mx-auto px-4 py-2 max-w-xl" dir="rtl">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('tables.index') }}"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-white border border-gray-200 text-gray-500 hover:bg-gray-50 transition shadow-sm">
            <i class="fa-solid fa-arrow-right text-sm"></i>
        </a>
        <div>
            <h2 class="text-xl font-black text-gray-800">تعديل: {{ $table->name }}</h2>
            <p class="text-xs text-gray-500">تحديث بيانات الطاولة</p>
        </div>
    </div>

    {{-- Warning if occupied --}}
    @if($table->isOccupied())
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-3 mb-4 flex items-center gap-2 text-orange-700 text-sm">
            <i class="fa-solid fa-triangle-exclamation text-orange-400"></i>
            <span>هذه الطاولة عليها طلب مفتوح الآن. يمكن التعديل لكن لا يمكن تعطيلها.</span>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <form action="{{ route('tables.update', $table->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            {{-- Name --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">
                    اسم الطاولة <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $table->name) }}"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition
                    @error('name') border-red-400 bg-red-50 @enderror"
                    autofocus>
                @error('name')
                    <p class="text-red-500 text-xs mt-1.5 flex items-center gap-1">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Capacity + Area --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">سعة الطاولة (أشخاص)</label>
                    <input type="number" name="capacity" value="{{ old('capacity', $table->capacity) }}" min="1" max="50"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1.5">المنطقة / القسم</label>
                    <input type="text" name="area" value="{{ old('area', $table->area) }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1.5">ملاحظات</label>
                <input type="text" name="notes" value="{{ old('notes', $table->notes) }}"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition">
            </div>

            {{-- Active Toggle --}}
            <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-xl px-4 py-3
                {{ $table->isOccupied() ? 'opacity-60' : '' }}">
                <div>
                    <p class="text-sm font-bold text-gray-700">الطاولة نشطة</p>
                    <p class="text-xs text-gray-400 mt-0.5">الطاولات النشطة فقط تظهر في شاشة الكاشير</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer {{ $table->isOccupied() ? 'pointer-events-none' : '' }}">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                        {{ old('is_active', $table->is_active) ? 'checked' : '' }}
                        {{ $table->isOccupied() ? 'disabled' : '' }}>
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
            </div>

            {{-- Buttons --}}
            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold py-3 rounded-xl text-sm transition-all shadow-md shadow-blue-500/20 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    حفظ التعديلات
                </button>
                <a href="{{ route('tables.index') }}"
                   class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-3 rounded-xl text-sm transition-all text-center flex items-center justify-center">
                    إلغاء
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
