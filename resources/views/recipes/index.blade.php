@extends('layouts.app')

@section('title', 'إدارة الوصفات')
@section('page_title', 'معيار الوصفات (المكونات)')

@section('content')
<div class="flex justify-between items-center bg-white p-4 rounded-2xl border border-gray-100 shadow-sm mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-800" data-ar="مكونات المشروبات" data-en="Drink Recipes">مكونات المشروبات</h2>
        <p class="text-xs text-gray-400 mt-1" data-ar="ربط منتجات المينيو بالخامات لخصمها تلقائياً عند البيع" data-en="Link menu items to inventory to auto-deduct upon sales">ربط منتجات المينيو بالخامات لخصمها تلقائياً عند البيع</p>
    </div>
    
    @if(auth()->user() && auth()->user()->role === 'admin')
        <a href="{{ route('recipes.create') }}" class="bg-cafePrimary text-sidebar px-4 py-2.5 rounded-xl font-bold text-xs hover:bg-sky-400 transition flex items-center gap-2 shadow-sm shadow-sky-200">
            <i class="fa-solid fa-plus"></i>
            <span data-ar="إضافة مكون لوصفة" data-en="Add Ingredient">إضافة مكون لوصفة</span>
        </a>
    @endif
</div>

@if(session('success'))
<div class="bg-emerald-50 border border-emerald-200 text-emerald-600 text-xs rounded-xl p-4 mb-6 flex items-center gap-2 animate-fade-in">
    <i class="fa-solid fa-circle-check text-base"></i>
    <span>{{ session('success') }}</span>
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($menus as $item)
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-4">
                    @if($item->image)
                        <img src="{{ asset('storage/' . $item->image) }}" class="w-12 h-12 rounded-xl object-cover border border-gray-100">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-gray-400">
                            <i class="fa-solid fa-mug-hot text-lg"></i>
                        </div>
                    @endif
                    <div>
                        <h3 class="font-bold text-sm text-gray-800">{{ $item->name }}</h3>
                        <span class="text-[10px] bg-sky-50 text-sky-600 px-2 py-0.5 rounded-full font-medium">{{ $item->category->name ?? 'بدون قسم' }}</span>
                    </div>
                </div>

                <div class="border-t border-dashed border-gray-100 pt-3 space-y-2">
                    <p class="text-[11px] font-bold text-gray-500 mb-1" data-ar="المكونات المستهلكة:" data-en="Ingredients used:">المكونات المستهلكة:</p>
                    
                    @php 
                        $itemRecipes = $recipes->where('menu_item_id', $item->id);
                    @endphp

                    @forelse($itemRecipes as $recipe)
                        <div class="flex justify-between items-center bg-gray-50 px-3 py-2 rounded-xl border border-gray-100/60 text-xs">
                            <span class="text-gray-700 font-medium">{{ $recipe->inventoryItem->name ?? 'خامة محذوفة' }}</span>
                            <div class="flex items-center gap-3">
                                <span class="font-bold text-gray-900 bg-white border px-2 py-0.5 rounded-lg text-[11px]">
                                    {{ floatval($recipe->quantity_used) }} {{ $recipe->inventoryItem->unit ?? '' }}
                                </span>
                                
                                @if(auth()->user() && auth()->user()->role === 'admin')
                                    <form action="{{ route('recipes.destroy', $recipe->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المكون؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-600 transition p-1">
                                            <i class="fa-regular fa-trash-can text-sm"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-[11px] text-amber-500 bg-amber-50 border border-amber-100 px-3 py-2 rounded-xl flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                            <span data-ar="لم يتم تحديد مكونات لهذا المشروب بعد" data-en="No ingredients set for this drink yet">لم يتم تحديد مكونات لهذا المشروب بعد</span>
                        </p>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-50 flex justify-between items-center text-xs">
                <span class="text-gray-400" data-ar="سعر البيع:" data-en="Selling Price:">سعر البيع:</span>
                <span class="font-bold text-gray-800">{{ number_format($item->price, 2) }} EGP</span>
            </div>
        </div>
    @empty
        <div class="col-span-full bg-white border border-gray-100 rounded-2xl p-12 text-center text-gray-400">
            <i class="fa-solid fa-receipt text-4xl mb-3 text-gray-200"></i>
            <p data-ar="لا يوجد منتجات في المينيو لإضافة وصفات لها" data-en="No menu items available to add recipes">لا يوجد منتجات في المينيو لإضافة وصفات لها</p>
        </div>
    @endforelse
</div>
@endsection