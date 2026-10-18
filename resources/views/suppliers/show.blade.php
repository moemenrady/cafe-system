@extends('layouts.app')

@section('title', 'ملف المورد: ' . $supplier->name)
@section('page_title', 'كشف حساب وملف المورد')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" dir="rtl">

    {{-- ==================== رأس الصفحة والتنقل السريع ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('suppliers.index') }}"
                class="w-10 h-10 rounded-2xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع لقائمة الموردين">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                        <i class="fa-solid fa-truck-field text-blue-600"></i>
                        <span>{{ $supplier->name }}</span>
                    </h2>
                    @if($supplier->company_name)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                            {{ $supplier->company_name }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-gray-400 mt-0.5">كشف الحساب وسجل التوريدات وفواتير الشراء المسجلة</p>
            </div>
        </div>

        {{-- أزرار الإجراءات الرئيسية --}}
        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <a href="{{ route('suppliers.edit', $supplier->id) }}"
                class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-pen text-amber-500"></i>
                <span>تعديل البيانات</span>
            </a>

            {{-- الزر الذهبي: إنشاء فاتورة شراء جديدة لهذا المورد مباشرة --}}
            <a href="{{ route('purchase-invoices.create', ['supplier_id' => $supplier->id]) }}"
                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-blue-600/20">
                <i class="fa-solid fa-cart-plus"></i>
                <span>إنشاء فاتورة شراء لهذا المورد</span>
            </a>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 font-bold flex items-center gap-2 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ==================== بطاقة بيانات المورد والإحصائيات المالية ==================== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- تفاصيل المورد والاتصال --}}
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-xs space-y-4">
            <h3 class="text-xs font-black text-gray-700 uppercase tracking-wider flex items-center gap-2 border-b border-gray-100 pb-3">
                <i class="fa-solid fa-id-card text-blue-600"></i>
                <span>بيانات المورد والاتصال</span>
            </h3>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-400 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-user text-gray-400"></i>
                        <span>اسم المورد:</span>
                    </span>
                    <strong class="text-gray-900 font-black">{{ $supplier->name }}</strong>
                </div>

                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-400 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-building text-gray-400"></i>
                        <span>الشركة / المؤسسة:</span>
                    </span>
                    <strong class="text-gray-700 font-bold">{{ $supplier->company_name ?: 'غير محدد' }}</strong>
                </div>

                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-400 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-phone text-gray-400"></i>
                        <span>رقم الهاتف:</span>
                    </span>
                    @if($supplier->phone)
                        <a href="tel:{{ $supplier->phone }}" dir="ltr" class="font-mono font-bold text-blue-600 hover:underline">
                            {{ $supplier->phone }}
                        </a>
                    @else
                        <span class="text-gray-400">غير مسجل</span>
                    @endif
                </div>

                <div class="flex items-center justify-between py-1 border-b border-gray-50">
                    <span class="text-gray-400 font-bold flex items-center gap-1.5">
                        <i class="fa-solid fa-location-dot text-gray-400"></i>
                        <span>العنوان / الفرع:</span>
                    </span>
                    <span class="text-gray-700 font-bold">{{ $supplier->address ?: 'غير محدد' }}</span>
                </div>

                @if($supplier->notes)
                    <div class="pt-2">
                        <span class="text-gray-400 font-bold block mb-1">ملاحظات:</span>
                        <p class="text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 text-[11px] leading-relaxed">
                            {{ $supplier->notes }}
                        </p>
                    </div>
                @endif
            </div>

            {{-- حذف المورد في الأسفل بتحفظ --}}
            <div class="pt-4 border-t border-gray-100">
                <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST"
                    onsubmit="return confirm('هل أنت متأكد من حذف هذا المورد؟ سيتم الاحتفاظ بفواتيره في الأرشيف.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full text-center text-rose-500 hover:text-rose-700 text-xs font-bold transition py-1">
                        <i class="fa-solid fa-trash-can ml-1"></i>
                        <span>حذف المورد</span>
                    </button>
                </form>
            </div>
        </div>

        {{-- بطاقات كشف الحساب والمؤشرات المالية --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {{-- إجمالي التوريدات --}}
                <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs">
                    <span class="text-[11px] font-bold text-gray-400 block mb-1">إجمالي المشتريات</span>
                    <div class="text-lg font-black text-gray-900 font-mono">
                        {{ number_format($totalPurchases, 2) }}
                    </div>
                    <span class="text-[10px] text-gray-400">ج.م في {{ $invoicesCount }} فاتورة</span>
                </div>

                {{-- المسدد --}}
                <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs">
                    <span class="text-[11px] font-bold text-gray-400 block mb-1">إجمالي المسدد</span>
                    <div class="text-lg font-black text-emerald-600 font-mono">
                        {{ number_format($totalPaid, 2) }}
                    </div>
                    <span class="text-[10px] text-emerald-500">ج.م تم سداده</span>
                </div>

                {{-- المستحق المتبقي --}}
                <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs">
                    <span class="text-[11px] font-bold text-gray-400 block mb-1">المتبقي للمورد</span>
                    <div class="text-lg font-black {{ $totalDue > 0 ? 'text-rose-600' : 'text-gray-900' }} font-mono">
                        {{ number_format($totalDue, 2) }}
                    </div>
                    <span class="text-[10px] {{ $totalDue > 0 ? 'text-rose-500' : 'text-gray-400' }}">
                        {{ $totalDue > 0 ? 'دين مستحق السداد' : 'خالص الحساب' }}
                    </span>
                </div>

                {{-- عدد الفواتير --}}
                <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs">
                    <span class="text-[11px] font-bold text-gray-400 block mb-1">سجل التوريدات</span>
                    <div class="text-lg font-black text-blue-600 font-mono">
                        {{ $invoicesCount }}
                    </div>
                    <span class="text-[10px] text-blue-400">فاتورة شراء معتمدة</span>
                </div>
            </div>

            {{-- لافتة الدعوة للشراء السريع --}}
            <div class="bg-linear-to-r from-blue-600 to-indigo-700 rounded-3xl p-5 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-md">
                <div class="space-y-1">
                    <h4 class="font-black text-sm flex items-center gap-2">
                        <i class="fa-solid fa-cart-flatbed"></i>
                        <span>تسجيل توريد جديد من {{ $supplier->name }}</span>
                    </h4>
                    <p class="text-xs text-blue-100">
                        اضغط لفتح فاتورة شراء جديدة مع تحديد اسم المورد تلقائياً لتسجيل الأصناف وإضافتها للمخزن فوراً.
                    </p>
                </div>
                <a href="{{ route('purchase-invoices.create', ['supplier_id' => $supplier->id]) }}"
                    class="px-5 py-2.5 rounded-xl bg-white text-blue-700 hover:bg-blue-50 active:scale-95 font-black text-xs transition shrink-0 flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-plus-circle"></i>
                    <span>فتح فاتورة جديدة الآن</span>
                </a>
            </div>
        </div>

    </div>

    {{-- ==================== سجل فواتير الشراء الخاصة بهذا المورد ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden space-y-4">
        <div class="p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-xs font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-receipt text-blue-600"></i>
                    <span>فواتير الشراء والتوريد ({{ $invoices->count() }} فاتورة)</span>
                </h3>
                <p class="text-[11px] text-gray-400 mt-0.5">جميع فواتير الشراء الواردة من هذا المورد مع الأصناف والمبالغ</p>
            </div>

            <a href="{{ route('purchase-invoices.create', ['supplier_id' => $supplier->id]) }}"
                class="px-3.5 py-2 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs transition inline-flex items-center gap-1.5 border border-blue-200">
                <i class="fa-solid fa-plus"></i>
                <span>فاتورة جديدة لهذا المورد</span>
            </a>
        </div>

        @if($invoices->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-16 h-16 rounded-full bg-gray-50 text-gray-300 flex items-center justify-center text-2xl mx-auto">
                    <i class="fa-solid fa-file-circle-question"></i>
                </div>
                <h4 class="text-sm font-black text-gray-800">لا توجد فواتير شراء مسجلة لهذا المورد حتى الآن</h4>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">يمكنك تسجيل أول عملية توريد أو شراء لهذا المورد بالضغط على الزر أدناه.</p>
                <div class="pt-2">
                    <a href="{{ route('purchase-invoices.create', ['supplier_id' => $supplier->id]) }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs transition">
                        <i class="fa-solid fa-cart-plus"></i>
                        <span>إنشاء أول فاتورة توريد</span>
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-bold">
                            <th class="py-3 px-4">رقم الفاتورة</th>
                            <th class="py-3 px-4">تاريخ التوريد</th>
                            <th class="py-3 px-4">الأصناف والخامات</th>
                            <th class="py-3 px-4 text-left">إجمالي الفاتورة</th>
                            <th class="py-3 px-4 text-left">المسدد</th>
                            <th class="py-3 px-4 text-left">المتبقي</th>
                            <th class="py-3 px-4 text-center">حالة السداد</th>
                            <th class="py-3 px-4 text-center">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($invoices as $invoice)
                            @php
                                $statusInfo = $invoice->payment_status_info;
                            @endphp
                            <tr class="hover:bg-blue-50/30 transition">
                                {{-- رقم الفاتورة --}}
                                <td class="py-3.5 px-4 font-mono font-black text-blue-600">
                                    <a href="{{ route('purchase-invoices.show', $invoice->id) }}" class="hover:underline">
                                        {{ $invoice->invoice_number }}
                                    </a>
                                </td>

                                {{-- التاريخ --}}
                                <td class="py-3.5 px-4 font-bold text-gray-700">
                                    {{ $invoice->invoice_date ? $invoice->invoice_date->format('Y-m-d') : $invoice->created_at->format('Y-m-d') }}
                                </td>

                                {{-- الأصناف والخامات الموردة --}}
                                <td class="py-3.5 px-4">
                                    <div class="space-y-0.5">
                                        @foreach($invoice->items->take(3) as $item)
                                            <div class="text-[11px] text-gray-700 font-bold flex items-center gap-1">
                                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                <span>{{ $item->item_name }}</span>
                                                <span class="text-gray-400 font-mono">({{ (float)$item->quantity }} {{ $item->unit }})</span>
                                            </div>
                                        @endforeach
                                        @if($invoice->items->count() > 3)
                                            <span class="text-[10px] text-gray-400 font-bold pr-2.5">
                                                + {{ $invoice->items->count() - 3 }} أصناف أخرى...
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- الإجمالي --}}
                                <td class="py-3.5 px-4 text-left font-mono font-black text-gray-900">
                                    {{ number_format($invoice->net_amount, 2) }} <small class="text-[10px] font-sans text-gray-400">ج.م</small>
                                </td>

                                {{-- المسدد --}}
                                <td class="py-3.5 px-4 text-left font-mono font-black text-emerald-600">
                                    {{ number_format($invoice->paid_amount, 2) }} <small class="text-[10px] font-sans text-gray-400">ج.م</small>
                                </td>

                                {{-- المتبقي --}}
                                <td class="py-3.5 px-4 text-left font-mono font-black {{ $invoice->remaining_amount > 0 ? 'text-rose-600' : 'text-gray-400' }}">
                                    {{ number_format($invoice->remaining_amount, 2) }} <small class="text-[10px] font-sans">ج.م</small>
                                </td>

                                {{-- شارة السداد --}}
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black border {{ $statusInfo['bg'] }}">
                                        {{ $statusInfo['label'] }}
                                    </span>
                                </td>

                                {{-- الإجراءات --}}
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('purchase-invoices.show', $invoice->id) }}"
                                            class="w-7 h-7 rounded-lg bg-gray-50 hover:bg-gray-100 text-gray-700 flex items-center justify-center border border-gray-200 transition"
                                            title="عرض وتفاصيل الفاتورة">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>
                                        <a href="{{ route('purchase-invoices.edit', $invoice->id) }}"
                                            class="w-7 h-7 rounded-lg bg-gray-50 hover:bg-gray-100 text-amber-600 flex items-center justify-center border border-gray-200 transition"
                                            title="تعديل الفاتورة">
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection
