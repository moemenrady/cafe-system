@extends('layouts.app')

@section('title', 'تفاصيل فاتورة شراء رقم ' . $purchaseInvoice->invoice_number)
@section('page_title', 'تفاصيل فاتورة الشراء')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto pb-16" dir="rtl">

    {{-- ==================== شريط التحكم والأزرار العلوية ==================== --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-gray-100 shadow-xs print:hidden">
        <div class="flex items-center gap-3">
            <a href="{{ route('purchase-invoices.index') }}"
                class="w-10 h-10 rounded-2xl bg-gray-50 hover:bg-gray-100 text-gray-600 flex items-center justify-center transition border border-gray-200"
                title="الرجوع لجميع فواتير الشراء">
                <i class="fa-solid fa-arrow-right"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-black text-gray-900 font-mono">{{ $purchaseInvoice->invoice_number }}</h2>
                    @php $statusInfo = $purchaseInvoice->payment_status_info; @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black border {{ $statusInfo['bg'] }}">
                        {{ $statusInfo['label'] }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 mt-0.5">
                    المورد: <strong class="text-gray-700">{{ $purchaseInvoice->supplier_name }}</strong>
                    • التاريخ: <span class="font-mono text-gray-600">{{ $purchaseInvoice->invoice_date ? $purchaseInvoice->invoice_date->format('Y-m-d') : $purchaseInvoice->created_at->format('Y-m-d') }}</span>
                </p>
            </div>
        </div>

        {{-- أزرار الإجراءات (طباعة، تعديل، حذف) --}}
        <div class="flex items-center gap-2 w-full sm:w-auto">
            {{-- زر الطباعة --}}
            <button type="button" onclick="window.print()"
                class="px-4 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 text-gray-700 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                <i class="fa-solid fa-print text-gray-500"></i>
                <span>طباعة</span>
            </button>

            {{-- زر التعديل --}}
            <a href="{{ route('purchase-invoices.edit', $purchaseInvoice->id) }}"
                class="px-4 py-2.5 rounded-xl bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-pen text-amber-600"></i>
                <span>تعديل</span>
            </a>

            {{-- زر الحذف --}}
            <form action="{{ route('purchase-invoices.destroy', $purchaseInvoice->id) }}" method="POST"
                onsubmit="return confirm('تحذير: هل أنت متأكد من رغبتك في حذف فاتورة الشراء هذه؟ سيتم خصم الكميات من المخزن.')"
                class="inline-block">
                @csrf
                @method('DELETE')
                <button type="submit"
                    class="px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-xs cursor-pointer">
                    <i class="fa-solid fa-trash text-rose-600"></i>
                    <span>حذف</span>
                </button>
            </form>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800 font-bold flex items-center gap-2 print:hidden animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- ==================== ورقة الفاتورة المعتمدة (Print-Ready Invoice Card) ==================== --}}
    <div class="bg-white rounded-3xl p-6 sm:p-8 border border-gray-100 shadow-md space-y-6 print:border-none print:shadow-none print:p-0">

        {{-- رأس الفاتورة الرسمي --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-gray-200">
            <div class="space-y-1">
                <div class="flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-2xl bg-blue-600 text-white flex items-center justify-center text-lg font-black shadow-xs">
                        <i class="fa-solid fa-mug-hot"></i>
                    </div>
                    <div>
                        <h1 class="text-base font-black text-gray-900">ENTERPRISE CAFE</h1>
                        <span class="text-[10px] text-gray-400 font-bold tracking-widest block uppercase">إدارة التوريدات والمخازن</span>
                    </div>
                </div>
            </div>

            <div class="text-left sm:text-left font-mono">
                <span class="text-xs text-gray-400 font-sans block">فاتورة شراء رقم:</span>
                <span class="text-xl font-black text-blue-600">{{ $purchaseInvoice->invoice_number }}</span>
                <div class="text-[11px] text-gray-500 font-sans mt-0.5">
                    التاريخ: {{ $purchaseInvoice->invoice_date ? $purchaseInvoice->invoice_date->translatedFormat('d F Y') : $purchaseInvoice->created_at->format('Y-m-d') }}
                </div>
            </div>
        </div>

        {{-- كروت بيانات المورد والدفع --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 p-4 bg-gray-50/80 rounded-2xl border border-gray-100 text-xs">
            {{-- بيانات المورد --}}
            <div class="space-y-1">
                <span class="text-gray-400 font-bold block text-[11px]">بيانات المورد / الشركة:</span>
                <strong class="text-sm font-black text-gray-900 block">{{ $purchaseInvoice->supplier_name }}</strong>
                <span class="text-[11px] text-gray-500 block">توريد مواد خام للمخزن</span>
            </div>

            {{-- بيانات السداد --}}
            <div class="space-y-1 sm:text-center">
                <span class="text-gray-400 font-bold block text-[11px]">طريقة وحالة السداد:</span>
                <div class="flex items-center sm:justify-center gap-2">
                    <span class="font-bold text-gray-800">{{ $purchaseInvoice->payment_method_label }}</span>
                    <span>•</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-black border {{ $statusInfo['bg'] }}">
                        {{ $statusInfo['label'] }}
                    </span>
                </div>
                @if($purchaseInvoice->remaining_amount > 0)
                    <span class="text-[11px] font-bold text-rose-600 block">
                        متبقي للدفع: {{ number_format($purchaseInvoice->remaining_amount, 2) }} ج.م
                    </span>
                @endif
            </div>

            {{-- المحرر والتوقيت --}}
            <div class="space-y-1 sm:text-left">
                <span class="text-gray-400 font-bold block text-[11px]">حررت بواسطة:</span>
                <strong class="text-xs font-black text-gray-800 block">{{ $purchaseInvoice->user->name ?? 'المدير المسؤول' }}</strong>
                <span class="text-[10px] text-gray-400 font-mono block">سُجلت في: {{ $purchaseInvoice->created_at->format('Y-m-d h:i A') }}</span>
            </div>
        </div>

        {{-- جدول تفاصيل الأصناف المشتراة --}}
        <div class="space-y-3">
            <h3 class="text-xs font-black text-gray-800 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-list-check text-blue-600"></i>
                <span>قائمة الأصناف والمواد المشتراة ({{ $purchaseInvoice->items->count() }} صنف):</span>
            </h3>

            <div class="border border-gray-200 rounded-2xl overflow-hidden">
                <table class="w-full text-right border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200 text-[11px]">
                            <th class="p-3 w-12 text-center">#</th>
                            <th class="p-3">اسم الصنف الخام</th>
                            <th class="p-3 w-28 text-center">التصنيف</th>
                            <th class="p-3 w-28 text-center">الكمية المشتراة</th>
                            <th class="p-3 w-32 text-center">سعر الوحدة</th>
                            <th class="p-3 w-32 text-left">الإجمالي الفرعي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-medium text-gray-800">
                        @foreach($purchaseInvoice->items as $index => $item)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-3.5 text-center text-gray-400 font-mono">{{ $index + 1 }}</td>
                                <td class="p-3.5">
                                    <div class="font-bold text-gray-900 text-sm">{{ $item->item_name }}</div>
                                    @if($item->notes)
                                        <div class="text-[10px] text-gray-400 mt-0.5">ملاحظة: {{ $item->notes }}</div>
                                    @endif
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="px-2 py-0.5 rounded-lg bg-gray-100 text-gray-700 text-[10px] font-bold">
                                        {{ $item->inventoryItem->category ?? 'عام' }}
                                    </span>
                                </td>
                                <td class="p-3.5 text-center">
                                    <span class="font-mono font-black text-blue-600 text-sm">
                                        {{ (float)$item->quantity }}
                                    </span>
                                    <span class="text-gray-500 text-[11px] font-bold mr-1">{{ $item->unit }}</span>
                                </td>
                                <td class="p-3.5 text-center font-mono text-gray-700 font-bold">
                                    {{ number_format($item->unit_price, 2) }} ج
                                </td>
                                <td class="p-3.5 text-left font-mono font-black text-gray-900 text-sm">
                                    {{ number_format($item->subtotal, 2) }} ج.م
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- تفكيك الحسابات والمجاميع الختامية --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 pt-4 border-t border-gray-200">
            {{-- الملاحظات إن وجدت --}}
            <div class="max-w-md text-xs text-gray-500">
                @if($purchaseInvoice->notes)
                    <div class="p-3.5 bg-amber-50/60 border border-amber-200/80 rounded-2xl">
                        <strong class="text-amber-800 block mb-1">ملاحظات الفاتورة:</strong>
                        <p class="text-gray-700 text-[11px] leading-relaxed">{{ $purchaseInvoice->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- جدول الإجماليات --}}
            <div class="w-full sm:w-72 bg-gray-50 rounded-2xl p-4 border border-gray-200 space-y-2 text-xs">
                <div class="flex justify-between items-center text-gray-600">
                    <span>إجمالي الأصناف:</span>
                    <span class="font-mono font-bold text-gray-900">{{ number_format($purchaseInvoice->total_amount, 2) }} ج.م</span>
                </div>

                @if($purchaseInvoice->discount > 0)
                    <div class="flex justify-between items-center text-emerald-600 font-bold">
                        <span>الخصم المكتسب:</span>
                        <span class="font-mono">- {{ number_format($purchaseInvoice->discount, 2) }} ج.م</span>
                    </div>
                @endif

                @if($purchaseInvoice->tax > 0)
                    <div class="flex justify-between items-center text-gray-600">
                        <span>الضريبة:</span>
                        <span class="font-mono">+ {{ number_format($purchaseInvoice->tax, 2) }} ج.م</span>
                    </div>
                @endif

                <div class="border-t border-gray-200 pt-2 flex justify-between items-baseline">
                    <span class="font-black text-gray-900 text-sm">الصافي النهائي:</span>
                    <span class="text-lg font-black font-mono text-blue-600">
                        {{ number_format($purchaseInvoice->net_amount, 2) }} <span class="text-xs">ج.م</span>
                    </span>
                </div>

                <div class="border-t border-gray-200/70 pt-2 flex justify-between items-center text-gray-500 text-[11px]">
                    <span>المبلغ المسدد:</span>
                    <span class="font-mono font-bold text-emerald-700">{{ number_format($purchaseInvoice->paid_amount, 2) }} ج.م</span>
                </div>

                @if($purchaseInvoice->remaining_amount > 0)
                    <div class="flex justify-between items-center text-rose-600 text-[11px] font-bold">
                        <span>المتبقي للمورد:</span>
                        <span class="font-mono">{{ number_format($purchaseInvoice->remaining_amount, 2) }} ج.م</span>
                    </div>
                @endif
            </div>
        </div>

    </div>

</div>
@endsection
