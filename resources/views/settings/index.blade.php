@extends('layouts.app')

@section('page_title', 'الإعدادات وتخصيص الحساب')

@section('content')
<div class="container mx-auto space-y-6 pb-16 max-w-4xl" dir="rtl">

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-4 rounded-2xl text-xs sm:text-sm font-bold flex items-center gap-3 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- رأس الصفحة --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shadow-inner">
                <i class="fa-solid fa-sliders"></i>
            </div>
            <div>
                <h2 class="text-lg sm:text-xl font-black text-gray-800">إعدادات الحساب وتخصيص النظام</h2>
                <p class="text-xs text-gray-400 mt-0.5">إدارة بيانات حسابك وتخصيص ترتيب القائمة الجانبية حسب رغبتك</p>
            </div>
        </div>
        <span class="px-3 py-1 rounded-xl bg-gray-100 text-gray-600 text-xs font-bold font-mono">
            {{ auth()->user()->role }}
        </span>
    </div>

    {{-- قسم 1: تخصيص وترتيب عناصر القائمة الجانبية (Sidebar Reordering) --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div>
                <h3 class="text-sm sm:text-base font-black text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-arrows-up-down text-sky-500"></i>
                    <span>تخصيص وترتيب القائمة الجانبية</span>
                </h3>
                <p class="text-xs text-gray-400 mt-0.5">رتب عناصر القائمة بسهولة عبر أزرار الصعود والهبوط وسيتم حفظ الترتيب في حسابك مباشرة</p>
            </div>
            <button type="button" onclick="resetSidebarOrderSettings()" class="px-3 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold transition">
                <i class="fa-solid fa-rotate-left ml-1"></i>
                استعادة الترتيب الافتراضي
            </button>
        </div>

        @php
            $orderedItems = auth()->user()->getOrderedSidebarItems();
        @endphp

        <div id="settingsSidebarList" class="space-y-2">
            @foreach($orderedItems as $key => $item)
                <div data-key="{{ $key }}" class="settings-sidebar-item flex items-center justify-between p-3.5 rounded-2xl border border-gray-100 bg-gray-50/60 hover:bg-gray-100/80 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm {{ $item['is_hero'] ?? false ? 'bg-sky-500 text-white' : 'bg-white text-gray-700 border border-gray-200 shadow-2xs' }}">
                            <i class="{{ $item['icon'] }}"></i>
                        </div>
                        <div>
                            <span class="font-bold text-gray-800 text-xs sm:text-sm block">{{ $item['title'] }}</span>
                            <span class="text-[10px] text-gray-400 font-bold">{{ $item['group_label'] ?? '' }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="moveItemUp(this)" class="w-8 h-8 rounded-xl bg-white hover:bg-sky-50 text-gray-600 hover:text-sky-600 border border-gray-200 flex items-center justify-center transition" title="تحريك لأعلى">
                            <i class="fa-solid fa-arrow-up text-xs"></i>
                        </button>
                        <button type="button" onclick="moveItemDown(this)" class="w-8 h-8 rounded-xl bg-white hover:bg-sky-50 text-gray-600 hover:text-sky-600 border border-gray-200 flex items-center justify-center transition" title="تحريك لأسفل">
                            <i class="fa-solid fa-arrow-down text-xs"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-3 flex justify-end">
            <button type="button" onclick="saveSettingsSidebarOrder()" id="saveOrderBtn" class="px-6 py-3 rounded-2xl bg-sky-500 hover:bg-sky-600 text-white font-bold text-xs sm:text-sm flex items-center gap-2 shadow-sm transition active:scale-95">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>حفظ الترتيب الحالي في الحساب</span>
            </button>
        </div>
    </div>

    {{-- قسم 2: بيانات الحساب الشخصي --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs p-5 sm:p-6 space-y-4">
        <h3 class="text-sm sm:text-base font-black text-gray-800 flex items-center gap-2 border-b border-gray-100 pb-3">
            <i class="fa-solid fa-user-gear text-sky-500"></i>
            <span>بيانات الحساب الحالي</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">الاسم الكامل</span>
                <strong class="text-sm text-gray-800">{{ auth()->user()->name }}</strong>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">البريد الإلكتروني</span>
                <strong class="text-sm text-gray-800 font-mono">{{ auth()->user()->email }}</strong>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">الصلاحية / الدور</span>
                <strong class="text-sm text-sky-700 font-bold">{{ auth()->user()->role }}</strong>
            </div>

            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100">
                <span class="text-gray-400 block mb-1">صلاحية بدء الشيفت</span>
                <strong class="text-sm {{ auth()->user()->canStartShift() ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ auth()->user()->canStartShift() ? 'مفعلة (مسموح)' : 'معطلة (ممنوع)' }}
                </strong>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    function moveItemUp(btn) {
        const item = btn.closest('.settings-sidebar-item');
        const prev = item.previousElementSibling;
        if (prev) {
            item.parentNode.insertBefore(item, prev);
        }
    }

    function moveItemDown(btn) {
        const item = btn.closest('.settings-sidebar-item');
        const next = item.nextElementSibling;
        if (next) {
            item.parentNode.insertBefore(next, item);
        }
    }

    async function saveSettingsSidebarOrder() {
        const btn = document.getElementById('saveOrderBtn');
        const items = document.querySelectorAll('#settingsSidebarList .settings-sidebar-item');
        const order = Array.from(items).map(el => el.dataset.key);

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner animate-spin"></i> <span>جاري الحفظ...</span>';

        try {
            const res = await fetch('{{ route('user.sidebar_order.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ order: order })
            });
            const data = await res.json();
            if (data.success) {
                alert('تم حفظ ترتيب القائمة بنجاح! سيتم تحديث الصفحة لتطبيق الترتيب الجديد.');
                window.location.reload();
            } else {
                alert('حدث خطأ أثناء الحفظ.');
            }
        } catch (e) {
            alert('حدث خطأ أثناء الحفظ.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> <span>حفظ الترتيب الحالي في الحساب</span>';
        }
    }

    async function resetSidebarOrderSettings() {
        if (!confirm('هل تريد استعادة الترتيب الافتراضي للقائمة؟')) return;

        try {
            const res = await fetch('{{ route('user.sidebar_order.reset') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            }
        } catch (e) {
            alert('حدث خطأ أثناء الاستعادة.');
        }
    }
</script>
@endpush
@endsection
