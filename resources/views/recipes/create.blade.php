@extends('layouts.app')

@section('title', 'إضافة وصفة متكاملة')
@section('page_title', 'تعريف مكونات المنتج')

@section('content')
<div class="max-w-3xl mx-auto bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden" dir="rtl">
    <div class="p-5 border-b border-gray-50 bg-gray-50/50">
        <h2 class="text-sm font-bold text-gray-800">إضافة مكونات جديدة للمنتج</h2>
    </div>

    <form action="{{ route('recipes.store') }}" method="POST" class="p-6 space-y-5">
        @csrf
        
        <div class="bg-sky-50 p-4 rounded-xl border border-sky-100">
            <label class="block text-xs font-bold text-sky-800 mb-2">المنتج المراد تعريفه:</label>
            <select name="menu_item_id" class="w-full border-sky-200 rounded-xl px-3 py-2 text-xs bg-white focus:ring-1 focus:ring-sky-400" required>
                <option value="">اختر المنتج...</option>
                @foreach($menus as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        <div id="ingredients-container" class="space-y-3">
            <label class="block text-xs font-bold text-gray-700">المكونات والمقادير:</label>
            
            <div class="flex gap-2 ingredient-row items-center">
                <select name="inventory_ids[]" class="w-1/2 border border-gray-200 rounded-xl px-3 py-2 text-xs bg-white" required>
                    <option value="">اختر المادة الخام...</option>
                    @foreach($inventories as $inv)
                        <option value="{{ $inv->id }}">{{ $inv->name }} ({{ $inv->unit }})</option>
                    @endforeach
                </select>
                
                <input type="number" step="0.01" name="quantities[]" placeholder="الكمية المطلوبة للطلب الواحد" class="w-1/3 border border-gray-200 rounded-xl px-3 py-2 text-xs" required>
                
                <button type="button" class="text-red-400 hover:text-red-600 p-2 transition" onclick="removeRow(this)">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        <button type="button" onclick="addRow()" class="inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-700 font-bold border border-blue-200 hover:border-blue-300 px-4 py-2 rounded-xl bg-gray-50 transition">
            <i class="fa-solid fa-plus"></i> إضافة مكون آخر
        </button>

        <hr class="border-gray-100 my-4">

        <div class="flex gap-3">
            <a href="{{ route('recipes.index') }}" class="w-1/3 text-center bg-gray-100 text-gray-600 py-3 rounded-xl text-xs font-bold hover:bg-gray-200 transition">
                إلغاء وعودة
            </a>
            <button type="submit" class="w-2/3 bg-blue-600 hover:bg-blue-500 text-white py-3 rounded-xl text-xs font-bold shadow-sm transition">
                حفظ الوصفة كاملة
            </button>
        </div>
    </form>
</div>

<script>
    function addRow() {
        const container = document.getElementById('ingredients-container');
        // جلب السطر الأول لنسخه
        const rows = container.querySelectorAll('.ingredient-row');
        const newRow = rows[0].cloneNode(true);
        
        // تفريغ المدخلات في السطر الجديد حتى لا ينسخ البيانات القديمة
        newRow.querySelector('select').value = "";
        newRow.querySelector('input').value = "";
        
        container.appendChild(newRow);
    }

    function removeRow(btn) {
        const container = document.getElementById('ingredients-container');
        const totalRows = container.querySelectorAll('.ingredient-row').length;
        
        // منع حذف السطر إذا كان هو الوحيد المتبقي بالفورم
        if (totalRows > 1) {
            btn.parentElement.remove();
        } else {
            alert('يجب أن تحتوي الوصفة على مكون واحد على الأقل!');
        }
    }
</script>
@endsection