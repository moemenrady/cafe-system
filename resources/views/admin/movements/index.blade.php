@extends('layouts.app')

@section('page_title', 'سجل الرقابة - حركات المشرفين')

@section('content')
    <div class="container mx-auto space-y-6" dir="rtl">

        <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-shield-halved text-red-500"></i> سجل تعديلات الفواتير (خاص بالإدارة)
            </h3>
            <p class="text-xs text-gray-400 mt-1">يتم هنا تسجيل أي عملية تعديل على الفواتير الصادرة من قِبل المشرفين فقط
                لمتابعة الأمان المالي.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 text-xs font-bold border-b border-gray-100">
                            <th class="p-4 text-center">المشرف</th>
                            <th class="p-4 text-center">رقم الفاتورة</th>
                            <th class="p-4 text-center">نوع الحركة</th>
                            <th class="p-4">تفاصيل البيان</th>
                            <th class="p-4 text-center">التاريخ والوقت</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 text-sm">
                        @forelse($movements as $movement)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="p-4 text-center font-bold text-gray-800">
                                    <span
                                        class="px-3 py-1 bg-gray-100 rounded-lg text-xs">{{ $movement->creator->name ?? 'غير معروف' }}</span>
                                </td>
                                <td class="p-4 text-center font-black text-cafePrimary">
                                    #{{ $movement->invoice->invoice_number ?? 'محذوفة' }}
                                </td>
                                <td class="p-4 text-center">
                                    <span
                                        class="px-2.5 py-0.5 bg-amber-50 text-amber-600 border border-amber-100 font-bold rounded-md text-xs">
                                        تعديل فاتورة
                                    </span>
                                </td>
                                <td class="p-4 text-gray-600 font-medium">
                                    {{ $movement->description }}
                                </td>
                                <td class="p-4 text-center text-gray-400 text-xs font-mono">
                                    {{ $movement->created_at->format('Y-m-d - h:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-12 text-center text-gray-400 bg-gray-50/10">
                                    <div
                                        class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2 text-md">
                                        <i class="fa-solid fa-lock-open text-green-500"></i>
                                    </div>
                                    <p class="font-bold text-gray-700 text-xs">السجل الرقابي نظيف تماماً</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5">لم يقم أي مشرف بإجراء تعديلات على الفواتير
                                        حتى الآن.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t bg-gray-50/50">
                {{ $movements->links() }}
            </div>
        </div>
    </div>
@endsection
