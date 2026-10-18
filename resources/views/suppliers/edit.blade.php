@extends('layouts.app')

@section('title', 'تعديل بيانات المورد: ' . $supplier->name)
@section('page_title', 'تعديل بيانات المورد')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-16" dir="rtl">

    {{-- رأس الصفحة --}}
    <div class="flex items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('suppliers.show', $supplier->id) }}"
                class="w-10 h-10 rounded-2xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع لملف المورد">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <i class="fa-solid fa-pen text-amber-500"></i>
                    <span>تعديل بيانات المورد: {{ $supplier->name }}</span>
                </h2>
                <p class="text-xs text-gray-400 mt-0.5">تحديث بيانات التواصل أو الشركة وسيتم تحديثها تلقائياً بكافة الفواتير</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('suppliers.show', $supplier->id) }}"
                class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 text-xs font-bold transition">
                إلغاء
            </a>
            <button type="submit" form="supplierForm"
                class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-blue-600/20 cursor-pointer">
                <i class="fa-solid fa-check"></i>
                <span>حفظ التعديلات</span>
            </button>
        </div>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800 space-y-1">
            <div class="font-black flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
                <span>يرجى مراجعة الأخطاء التالية:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 pr-4 text-[11px]">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- نموذج تعديل المورد --}}
    <form action="{{ route('suppliers.update', $supplier->id) }}" method="POST" id="supplierForm" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-5">
            <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-3">
                <i class="fa-solid fa-address-card text-blue-600"></i>
                <span>البيانات الأساسية للمورد</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                {{-- اسم المورد --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        اسم المورد / المسؤول <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name', $supplier->name) }}" required
                        placeholder="مثال: هيثم، حاتم زينهم، شركة الريس..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition">
                </div>

                {{-- اسم الشركة / المؤسسة --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        اسم الشركة أو المحل (اختياري)
                    </label>
                    <input type="text" name="company_name" value="{{ old('company_name', $supplier->company_name) }}"
                        placeholder="مثال: خضروات وفواكه العيلة، شركة القواس..."
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition">
                </div>

                {{-- رقم الهاتف --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        رقم الهاتف / الواتساب
                    </label>
                    <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}" dir="ltr"
                        placeholder="01xxxxxxxxx"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition text-right">
                </div>

                {{-- البريد الإلكتروني --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        البريد الإلكتروني
                    </label>
                    <input type="email" name="email" value="{{ old('email', $supplier->email) }}" dir="ltr"
                        placeholder="supplier@example.com"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition text-right">
                </div>

                {{-- العنوان --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        العنوان / مقر التوزيع
                    </label>
                    <input type="text" name="address" value="{{ old('address', $supplier->address) }}"
                        placeholder="مثال: الإسكندرية - سموحة"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition">
                </div>

                {{-- الرصيد الافتتاحي --}}
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1.5">
                        الرصيد الافتتاحي (ج.م)
                    </label>
                    <input type="number" step="0.01" name="opening_balance" value="{{ old('opening_balance', $supplier->opening_balance) }}"
                        class="w-full bg-gray-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-xs text-gray-900 font-mono font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition">
                </div>
            </div>

            {{-- ملاحظات --}}
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1.5">
                    ملاحظات إضافية عن المورد
                </label>
                <textarea name="notes" rows="3"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition">{{ old('notes', $supplier->notes) }}</textarea>
            </div>
        </div>
    </form>

</div>
@endsection
