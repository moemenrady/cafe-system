<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Menu;
use App\Models\InventoryItem;
use App\Models\Recipe;

class CafeSystemSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. إنشاء مكونات المخزن الأساسية (Inventory Items)
        // ==========================================
        $coffeeBeans = InventoryItem::create(['name' => 'حبوب قهوة اسبريسو', 'quantity' => 10000, 'unit' => 'gram']);
        $turkishCoffee = InventoryItem::create(['name' => 'بن تركي مطحون', 'quantity' => 5000, 'unit' => 'gram']);
        $milk = InventoryItem::create(['name' => 'حليب كامل الدسم', 'quantity' => 20000, 'unit' => 'ml']);
        $chocolateSyrup = InventoryItem::create(['name' => 'سيرب شوكولاتة', 'quantity' => 3000, 'unit' => 'ml']);
        $caramelSyrup = InventoryItem::create(['name' => 'سيرب كراميل', 'quantity' => 3000, 'unit' => 'ml']);
        $mojitoSyrup = InventoryItem::create(['name' => 'سيرب موهيتو ليمون نعناع', 'quantity' => 2000, 'unit' => 'ml']);
        $teaBags = InventoryItem::create(['name' => 'فتيل شاي أحمر', 'quantity' => 500, 'unit' => 'piece']);
        $frozenCroissant = InventoryItem::create(['name' => 'كرواسون مجمد جاهز للخبز', 'quantity' => 100, 'unit' => 'piece']);
        $moltenCakeMix = InventoryItem::create(['name' => 'قطع مولتن كيك جاهزة', 'quantity' => 50, 'unit' => 'piece']);
        $water = InventoryItem::create(['name' => 'مياه معدنية صغيرة', 'quantity' => 200, 'unit' => 'piece']);

        // ==========================================
        // 2. إنشاء التصنيفات (Categories)
        // ==========================================
        $hotCoffeeCat = Category::create(['name' => 'قهوة ساخنة']);
        $coldCoffeeCat = Category::create(['name' => 'قهوة باردة']);
        $softDrinksCat = Category::create(['name' => 'مشروبات منعشة ومرطبات']);
        $bakeryCat = Category::create(['name' => 'مخبوزات وحلويات']);

        // ==========================================
        // 3. إضافة المنتجات والوصفات (20 منتج بالتمام والكمال)
        // ==========================================

        // --- قسم القهوة الساخنة (6 منتجات) ---
        $itemsHot = [
            ['name' => 'قهوة تركي سينجل', 'price' => 25.00, 'inv' => $turkishCoffee, 'qty' => 15],
            ['name' => 'قهوة تركي دبل', 'price' => 35.00, 'inv' => $turkishCoffee, 'qty' => 30],
            ['name' => 'إسبريسو سينجل', 'price' => 30.00, 'inv' => $coffeeBeans, 'qty' => 9],
            ['name' => 'إسبريسو دبل', 'price' => 45.00, 'inv' => $coffeeBeans, 'qty' => 18],
            ['name' => 'كابتشينو', 'price' => 55.00, 'inv' => $coffeeBeans, 'qty' => 9, 'milk' => 150],
            ['name' => 'كافيه لاتيه', 'price' => 60.00, 'inv' => $coffeeBeans, 'qty' => 9, 'milk' => 200],
        ];

        foreach ($itemsHot as $item) {
            $menu = Menu::create(['name' => $item['name'], 'category_id' => $hotCoffeeCat->id, 'price' => $item['price'], 'is_available' => true]);
            Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $item['inv']->id, 'quantity_used' => $item['qty']]);
            if (isset($item['milk'])) {
                Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $milk->id, 'quantity_used' => $item['milk']]);
            }
        }

        // --- قسم القهوة الباردة (5 منتجات) ---
        $itemsCold = [
            ['name' => 'آيس سبانش لاتيه', 'price' => 70.00, 'inv' => $coffeeBeans, 'qty' => 18, 'milk' => 180],
            ['name' => 'آيس كراميل ماكياتو', 'price' => 75.00, 'inv' => $coffeeBeans, 'qty' => 9, 'milk' => 150, 'syrup' => $caramelSyrup, 'syrup_qty' => 20],
            ['name' => 'آيس موكا شوكليت', 'price' => 70.00, 'inv' => $coffeeBeans, 'qty' => 9, 'milk' => 150, 'syrup' => $chocolateSyrup, 'syrup_qty' => 25],
            ['name' => 'آيس لاتيه كلاسيك', 'price' => 65.00, 'inv' => $coffeeBeans, 'qty' => 18, 'milk' => 200],
            ['name' => 'فرابوتشينو كراميل', 'price' => 80.00, 'inv' => $coffeeBeans, 'qty' => 9, 'milk' => 120, 'syrup' => $caramelSyrup, 'syrup_qty' => 30],
        ];

        foreach ($itemsCold as $item) {
            $menu = Menu::create(['name' => $item['name'], 'category_id' => $coldCoffeeCat->id, 'price' => $item['price'], 'is_available' => true]);
            Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $item['inv']->id, 'quantity_used' => $item['qty']]);
            Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $milk->id, 'quantity_used' => $item['milk']]);
            if (isset($item['syrup'])) {
                Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $item['syrup']->id, 'quantity_used' => $item['syrup_qty']]);
            }
        }

        // --- قسم المرطبات والمشروبات المنعشة (4 منتجات) ---
        $itemsSoft = [
            ['name' => 'موهيتو ليمون نعناع كلاسيك', 'price' => 55.00, 'inv' => $mojitoSyrup, 'qty' => 40],
            ['name' => 'شاي أحمر فتيل', 'price' => 20.00, 'inv' => $teaBags, 'qty' => 1],
            ['name' => 'شاي بلبن', 'price' => 30.00, 'inv' => $teaBags, 'qty' => 1, 'milk' => 100],
            ['name' => 'زجاجة مياه معدنية', 'price' => 15.00, 'inv' => $water, 'qty' => 1],
        ];

        foreach ($itemsSoft as $item) {
            $menu = Menu::create(['name' => $item['name'], 'category_id' => $softDrinksCat->id, 'price' => $item['price'], 'is_available' => true]);
            Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $item['inv']->id, 'quantity_used' => $item['qty']]);
            if (isset($item['milk'])) {
                Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $milk->id, 'quantity_used' => $item['milk']]);
            }
        }

        // --- قسم الحلويات والمخبوزات (5 منتجات) ---
        $itemsBakery = [
            ['name' => 'كرواسون زبدة سادة', 'price' => 45.00, 'inv' => $frozenCroissant],
            ['name' => 'كرواسون نوتيلا', 'price' => 60.00, 'inv' => $frozenCroissant, 'syrup' => $chocolateSyrup, 'syrup_qty' => 20],
            ['name' => 'مولتن كيك شوكولاتة', 'price' => 75.00, 'inv' => $moltenCakeMix],
            ['name' => 'مولتن كيك كراميل', 'price' => 80.00, 'inv' => $moltenCakeMix, 'syrup' => $caramelSyrup, 'syrup_qty' => 30],
            ['name' => 'كرواسون جبنة شيدر', 'price' => 55.00, 'inv' => $frozenCroissant],
        ];

        foreach ($itemsBakery as $item) {
            $menu = Menu::create(['name' => $item['name'], 'category_id' => $bakeryCat->id, 'price' => $item['price'], 'is_available' => true]);
            Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $item['inv']->id, 'quantity_used' => 1]);
            if (isset($item['syrup'])) {
                Recipe::create(['menu_item_id' => $menu->id, 'inventory_item_id' => $item['syrup']->id, 'quantity_used' => $item['syrup_qty']]);
            }
        }
    }
}