@extends('layouts.app')

@section('title', 'إدارة الموردين وحسابات التوريد')
@section('page_title', 'سجل الموردين والشركات')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-16" dir="rtl">

    {{-- ==================== رأس الصفحة والإجراءات السريعة ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs">
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl font-black border border-blue-100 shadow-xs">
                <i class="fa-solid fa-truck-field"></i>
            </div>
            <div>
                <h2 class="text-lg font-black text-gray-900 flex items-center gap-2">
                    <span>سجل الموردين والشركات</span>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $totalSuppliersCount }} مورد
                    </span>
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">متابعة حسابات الموردين، فواتير الشراء، ومستحقات التوريد بدقة محاسبية</p>
            </div>
        </div>

        <div class="flex items-center gap-2.5 w-full sm:w-auto">
            <a href="{{ route('purchase-invoices.create') }}"
                class="px-4 py-2.5 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 font-bold text-xs transition flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-file-invoice text-blue-600"></i>
                <span>فاتورة شراء عامة</span>
            </a>
            <a href="{{ route('suppliers.create') }}"
                class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 active:scale-95 text-white font-black text-xs transition flex items-center gap-2 shadow-md shadow-blue-600/20">
                <i class="fa-solid fa-plus"></i>
                <span>إضافة مورد جديد</span>
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

    {{-- ==================== بطاقات الإحصائيات العامة (KPIs) ==================== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- إجمالي الموردين --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-black shrink-0">
                <i class="fa-solid fa-users-viewfinder"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 block">إجمالي الموردين المسجلين</span>
                <span class="text-xl font-black text-gray-900 font-mono">{{ $totalSuppliersCount }}</span>
            </div>
        </div>

        {{-- إجمالي قيمة المشتريات --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg font-black shrink-0">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 block">إجمالي مشتريات التوريد</span>
                <span class="text-xl font-black text-blue-600 font-mono">{{ number_format($totalPurchasesAll, 2) }} <small class="text-xs font-sans">ج.م</small></span>
            </div>
        </div>

        {{-- إجمالي المسدد للموردين --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-black shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 block">إجمالي المبالغ المسددة</span>
                <span class="text-xl font-black text-emerald-600 font-mono">{{ number_format($totalPaidAll, 2) }} <small class="text-xs font-sans">ج.م</small></span>
            </div>
        </div>

        {{-- إجمالي المستحقات والديون --}}
        <div class="bg-white p-5 rounded-3xl border border-gray-100 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg font-black shrink-0">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
            <div>
                <span class="text-[11px] font-bold text-gray-400 block">إجمالي الديون والمتبقي</span>
                <span class="text-xl font-black text-rose-600 font-mono">{{ number_format($totalDueAll, 2) }} <small class="text-xs font-sans">ج.م</small></span>
            </div>
        </div>
    </div>

    {{-- ==================== شريط البحث والتصفية ==================== --}}
    <div class="bg-white p-4 rounded-3xl border border-gray-100 shadow-xs">
        <form action="{{ route('suppliers.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <i class="fa-solid fa-magnifying-glass absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="بحث باسم المورد، اسم الشركة، أو رقم الهاتف..."
                    class="w-full bg-gray-50 border border-gray-200 rounded-2xl pr-10 pl-4 py-2.5 text-xs text-gray-900 font-bold focus:ring-2 focus:ring-blue-500 focus:outline-hidden transition">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit"
                    class="px-5 py-2.5 rounded-2xl bg-gray-900 hover:bg-gray-800 text-white font-bold text-xs transition flex items-center gap-2">
                    <i class="fa-solid fa-filter"></i>
                    <span>بحث</span>
                </button>
                @if(request()->filled('search'))
                    <a href="{{ route('suppliers.index') }}"
                        class="px-4 py-2.5 rounded-2xl border border-gray-200 text-gray-600 hover:bg-gray-50 font-bold text-xs transition">
                        إلغاء البحث
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- ==================== جدول قائمة الموردين ==================== --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xs font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-address-book text-blue-600"></i>
                <span>قائمة الموردين وحساباتهم</span>
            </h3>
            <span class="text-[11px] font-bold text-gray-400">
                عرض {{ $suppliers->count() }} مورد
            </span>
        </div>

        @if($suppliers->isEmpty())
            <div class="p-12 text-center space-y-3">
                <div class="w-16 h-16 rounded-full bg-gray-50 text-gray-300 flex items-center justify-center text-2xl mx-auto">
                    <i class="fa-solid fa-truck-ramp-box"></i>
                </div>
                <h4 class="text-sm font-black text-gray-800">لا يوجد موردين مطابقين للبحث</h4>
                <p class="text-xs text-gray-400 max-w-md mx-auto">لم يتم العثور على أي مورد مسجل، يمكنك البدء بإضافة موردين جدد لربطهم بفواتير الشراء.</p>
                <div class="pt-2">
                    <a href="{{ route('suppliers.create') }}"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-black text-xs transition">
                        <i class="fa-solid fa-plus"></i>
                        <span>إضافة أول مورد الآن</span>
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-xs">
                    <thead>
                        <tr class="bg-gray-50/75 border-b border-gray-100 text-gray-500 font-bold">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">اسم المورد / الشركة</th>
                            <th class="py-3 px-4">بيانات الاتصال</th>
                            <th class="py-3 px-4 text-center">الفواتير</th>
                            <th class="py-3 px-4 text-left">إجمالي المشتريات</th>
                            <th class="py-3 px-4 text-left">المسدد</th>
                            <th class="py-3 px-4 text-left">الرصيد المتبقي</th>
                            <th class="py-3 px-4 text-center">الإجراءات والتعاملات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($suppliers as $index => $supplier)
                            @php
                                $totalDue = $supplier->total_due;
                            @endphp
                            <tr class="hover:bg-blue-50/40 transition">
                                <td class="py-3.5 px-4 font-mono text-gray-400">{{ $index + 1 }}</td>
                                
                                {{-- اسم المورد --}}
                                <td class="py-3.5 px-4">
                                    <a href="{{ route('suppliers.show', $supplier->id) }}" class="group">
                                        <div class="font-black text-gray-900 group-hover:text-blue-600 transition flex items-center gap-1.5">
                                            <i class="fa-solid fa-truck-field text-blue-500 text-xs"></i>
                                            <span>{{ $supplier->name }}</span>
                                        </div>
                                        @if($supplier->company_name)
                                            <div class="text-[11px] text-gray-400 font-bold mt-0.5">
                                                {{ $supplier->company_name }}
                                            </div>
                                        @endif
                                    </a>
                                </td>

                                {{-- بيانات الاتصال --}}
                                <td class="py-3.5 px-4">
                                    @if($supplier->phone)
                                        <div class="font-mono font-bold text-gray-700 flex items-center gap-1">
                                            <i class="fa-solid fa-phone text-[10px] text-gray-400"></i>
                                            <span dir="ltr">{{ $supplier->phone }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-300 text-[11px]">غير مسجل</span>
                                    @endif
                                </td>

                                {{-- عدد الفواتير --}}
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-black bg-gray-100 text-gray-700 font-mono">
                                        {{ $supplier->invoices_count }} فاتورة
                                    </span>
                                </td>

                                {{-- إجمالي المشتريات --}}
                                <td class="py-3.5 px-4 text-left font-mono font-black text-gray-900">
                                    {{ number_format($supplier->total_purchases, 2) }} <small class="text-[10px] font-sans text-gray-400">ج.م</small>
                                </td>

                                {{-- المسدد --}}
                                <td class="py-3.5 px-4 text-left font-mono font-black text-emerald-600">
                                    {{ number_format($supplier->total_paid, 2) }} <small class="text-[10px] font-sans text-gray-400">ج.م</small>
                                </td>

                                {{-- الرصيد المتبقي --}}
                                <td class="py-3.5 px-4 text-left">
                                    @if($totalDue > 0)
                                        <span class="font-mono font-black text-rose-600 block">
                                            {{ number_format($totalDue, 2) }} <small class="text-[10px] font-sans">ج.م</small>
                                        </span>
                                        <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-black bg-rose-50 text-rose-700 border border-rose-200 mt-0.5">
                                            متبقي للمورد
                                        </span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                                            خالص السداد
                                        </span>
                                    @endif
                                </td>

                                {{-- الإجراءات والتعاملات السريعة --}}
                                <td class="py-3.5 px-4 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        {{-- زر الانتقال للشراء فوراً مع اختيار المورد --}}
                                        <a href="{{ route('purchase-invoices.create', ['supplier_id' => $supplier->id]) }}"
                                            class="px-2.5 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-black text-[11px] transition flex items-center gap-1 border border-blue-200 shadow-2xs"
                                            title="إنشاء فاتورة شراء جديدة لهذا المورد مباشرة">
                                            <i class="fa-solid fa-cart-plus text-xs"></i>
                                            <span>فاتورة جديدة</span>
                                        </a>

                                        {{-- زر عرض الملف --}}
                                        <a href="{{ route('suppliers.show', $supplier->id) }}"
                                            class="w-8 h-8 rounded-xl bg-gray-50 hover:bg-gray-100 text-gray-700 flex items-center justify-center border border-gray-200 transition"
                                            title="عرض ملف المورد وكشف الحساب">
                                            <i class="fa-solid fa-eye text-xs"></i>
                                        </a>

                                        {{-- زر تعديل --}}
                                        <a href="{{ route('suppliers.edit', $supplier->id) }}"
                                            class="w-8 h-8 rounded-xl bg-gray-50 hover:bg-gray-100 text-amber-600 flex items-center justify-center border border-gray-200 transition"
                                            title="تعديل بيانات المورد">
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
