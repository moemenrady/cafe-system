@extends('layouts.app')

@section('page_title', 'إدارة الطاولات')

@section('content')
<div class="container mx-auto px-4 py-2" dir="rtl">

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-5 rounded-xl text-right font-medium flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-green-500"></i>
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-5 rounded-xl text-right font-medium flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-red-500"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-black text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-table-cells-large text-cafePrimary"></i>
                إدارة الطاولات
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">إضافة وتعديل وتفعيل طاولات الكافيه</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tables.history') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-4 py-2.5 rounded-xl text-sm transition-all flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left"></i>
                سجل الطاولات المغلقة
            </a>
            <a href="{{ route('tables.create') }}"
               class="bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-bold px-4 py-2.5 rounded-xl text-sm transition-all flex items-center gap-2 shadow-md shadow-blue-500/20">
                <i class="fa-solid fa-plus"></i>
                إضافة طاولة
            </a>
        </div>
    </div>

    {{-- Stats Bar --}}
    @php
        $total    = $tables->count();
        $active   = $tables->where('is_active', true)->count();
        $occupied = $tables->where('is_occupied', true)->count();
        $inactive = $tables->where('is_active', false)->count();
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-2xl p-3.5 border border-gray-100 shadow-sm text-center">
            <p class="text-2xl font-black text-gray-800">{{ $total }}</p>
            <p class="text-xs text-gray-500 font-medium mt-0.5">إجمالي الطاولات</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-green-100 shadow-sm text-center">
            <p class="text-2xl font-black text-emerald-600">{{ $active }}</p>
            <p class="text-xs text-gray-500 font-medium mt-0.5">نشطة</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-orange-100 shadow-sm text-center">
            <p class="text-2xl font-black text-orange-500">{{ $occupied }}</p>
            <p class="text-xs text-gray-500 font-medium mt-0.5">مشغولة الآن</p>
        </div>
        <div class="bg-white rounded-2xl p-3.5 border border-gray-200 shadow-sm text-center">
            <p class="text-2xl font-black text-gray-400">{{ $inactive }}</p>
            <p class="text-xs text-gray-500 font-medium mt-0.5">غير نشطة</p>
        </div>
    </div>

    {{-- Tables Grid --}}
    @if($tables->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-16 text-center">
            <i class="fa-solid fa-table-cells text-5xl text-gray-200 mb-4"></i>
            <p class="text-gray-500 font-medium">لا توجد طاولات بعد</p>
            <a href="{{ route('tables.create') }}" class="mt-3 inline-block text-blue-600 font-bold text-sm hover:underline">
                أضف أول طاولة الآن
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($tables as $table)
                <div class="bg-white rounded-2xl border shadow-sm overflow-hidden transition-all
                    {{ !$table->is_active ? 'border-gray-200 opacity-70' : ($table->is_occupied ? 'border-orange-200' : 'border-gray-100') }}">

                    {{-- Status Stripe --}}
                    <div class="h-1.5
                        {{ !$table->is_active ? 'bg-gray-300' : ($table->is_occupied ? 'bg-orange-400' : 'bg-emerald-400') }}">
                    </div>

                    <div class="p-4">
                        {{-- Name + Badges --}}
                        <div class="flex items-start justify-between mb-3">
                            <div>
                                <h3 class="font-black text-gray-800 text-base">{{ $table->name }}</h3>
                                @if($table->area)
                                    <span class="text-xs text-gray-400">{{ $table->area }}</span>
                                @endif
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                {{-- Active/Inactive Badge --}}
                                @if($table->is_active)
                                    <span class="text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-200 px-2 py-0.5 rounded-full">نشطة</span>
                                @else
                                    <span class="text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200 px-2 py-0.5 rounded-full">معطلة</span>
                                @endif
                                {{-- Occupancy Badge --}}
                                @if($table->is_active)
                                    @if($table->is_occupied)
                                        <span class="text-[10px] font-bold bg-orange-50 text-orange-500 border border-orange-200 px-2 py-0.5 rounded-full">مشغولة</span>
                                    @else
                                        <span class="text-[10px] font-bold bg-sky-50 text-sky-600 border border-sky-200 px-2 py-0.5 rounded-full">متاحة</span>
                                    @endif
                                @endif
                            </div>
                        </div>

                        {{-- Details --}}
                        <div class="flex items-center gap-3 text-xs text-gray-500 mb-4">
                            @if($table->capacity)
                                <span class="flex items-center gap-1">
                                    <i class="fa-solid fa-users text-gray-300"></i>
                                    {{ $table->capacity }} أشخاص
                                </span>
                            @endif
                            @if($table->notes)
                                <span class="flex items-center gap-1 truncate">
                                    <i class="fa-solid fa-note-sticky text-gray-300"></i>
                                    {{ $table->notes }}
                                </span>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            {{-- Toggle Active --}}
                            <button
                                onclick="toggleTable({{ $table->id }}, this)"
                                data-active="{{ $table->is_active ? 'true' : 'false' }}"
                                class="flex-1 py-2 rounded-xl text-xs font-bold transition-all
                                    {{ $table->is_active
                                        ? 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                                        : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200' }}"
                                title="{{ $table->is_active ? 'تعطيل الطاولة' : 'تفعيل الطاولة' }}">
                                <i class="fa-solid {{ $table->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }} ml-1"></i>
                                {{ $table->is_active ? 'تعطيل' : 'تفعيل' }}
                            </button>

                            {{-- Edit --}}
                            <a href="{{ route('tables.edit', $table->id) }}"
                               class="w-9 h-9 flex items-center justify-center rounded-xl bg-yellow-50 text-yellow-600 hover:bg-yellow-100 transition border border-yellow-100"
                               title="تعديل">
                                <i class="fa-solid fa-pen text-xs"></i>
                            </a>

                            {{-- Delete --}}
                            <form action="{{ route('tables.destroy', $table->id) }}" method="POST"
                                  onsubmit="return confirmDelete(event, '{{ $table->name }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-red-50 text-red-500 hover:bg-red-100 transition border border-red-100"
                                    title="حذف">
                                    <i class="fa-solid fa-trash text-xs"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

{{-- Delete Confirmation + Toggle Script --}}
@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function confirmDelete(event, tableName) {
        if (!confirm(`هل أنت متأكد من حذف الطاولة "${tableName}"؟\nسيتم إلغاء ارتباط الطلبات السابقة بها.`)) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    function toggleTable(tableId, btn) {
        const isActive = btn.dataset.active === 'true';
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin ml-1"></i> جاري...';

        fetch(`/tables/${tableId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // نعمل reload للصفحة عشان الـ UI يتحدث بشكل كامل
                window.location.reload();
            } else {
                alert(data.message || 'حدث خطأ أثناء تغيير حالة الطاولة.');
                btn.disabled = false;
                btn.innerHTML = isActive
                    ? '<i class="fa-solid fa-toggle-on ml-1"></i> تعطيل'
                    : '<i class="fa-solid fa-toggle-off ml-1"></i> تفعيل';
            }
        })
        .catch(() => {
            alert('حدث خطأ في الاتصال بالخادم.');
            btn.disabled = false;
            btn.innerHTML = isActive
                ? '<i class="fa-solid fa-toggle-on ml-1"></i> تعطيل'
                : '<i class="fa-solid fa-toggle-off ml-1"></i> تفعيل';
        });
    }
</script>
@endpush
@endsection
