<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Models\Menu;
use App\Models\InventoryItem;
use App\Models\InventoryMovement;
use App\Models\Recipe;

class UnoMenuSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================================
        // 1. تنظيف بيانات المينيو القديمة بأمان
        // ==========================================
        Schema::disableForeignKeyConstraints();
        Recipe::truncate();
        Menu::truncate();
        Category::truncate();
        Schema::enableForeignKeyConstraints();

        // ==========================================
        // 3. إدخال أقسام ومنتجات مينيو UNO Cafe الـ 17
        // ==========================================
        $menuData = [
            'Break Fast' => [
                ['name' => 'Bagel', 'price' => 60.00],
                ['name' => 'Focaccia', 'price' => 60.00],
                ['name' => 'Baguette', 'price' => 60.00],
                ['name' => 'Mixed Cheese', 'price' => 140.00],
                ['name' => 'Turkey', 'price' => 160.00],
                ['name' => 'Tuna', 'price' => 170.00],
            ],
            'Iced Coffee' => [
                ['name' => 'Classic Latte', 'price' => 120.00],
                ['name' => 'Vanilla Latte', 'price' => 130.00],
                ['name' => 'Hazelnut Latte', 'price' => 130.00],
                ['name' => 'Spanish Latte', 'price' => 130.00],
                ['name' => 'Spanish Cookiee', 'price' => 140.00],
                ['name' => 'Pecan Cookiee', 'price' => 140.00],
                ['name' => 'Salted Vanilla', 'price' => 130.00],
                ['name' => 'Roasted Almond Pudding', 'price' => 140.00],
                ['name' => 'Caramel Machiato', 'price' => 130.00],
                ['name' => 'Ice Americano', 'price' => 80.00],
            ],
            'V 60' => [
                ['name' => 'Yemeni', 'price' => 180.00],
                ['name' => 'Colombian', 'price' => 150.00],
            ],
            'Hot Drinks' => [
                ['name' => 'Cappuccino', 'price' => 120.00],
                ['name' => 'Cortado', 'price' => 80.00],
                ['name' => 'Flat white', 'price' => 100.00],
                ['name' => 'Hot Spanish Latte', 'price' => 130.00],
                ['name' => 'Machiato', 'price' => 70.00],
                ['name' => 'Mocha', 'price' => 140.00],
                ['name' => 'Espresso', 'price' => 60.00],
                ['name' => 'Americano', 'price' => 90.00],
                ['name' => 'Hot Chocolate', 'price' => 120.00],
                ['name' => 'Marshmallow Hot chocolate', 'price' => 130.00],
                ['name' => 'Turkish Coffee', 'price' => 40.00],
                ['name' => 'French Coffee', 'price' => 70.00],
                ['name' => 'Hazelnut Coffee', 'price' => 70.00],
            ],
            'Milk Shake' => [
                ['name' => 'Nutella', 'price' => 130.00],
                ['name' => 'Lotus', 'price' => 130.00],
                ['name' => 'Kinder', 'price' => 130.00],
                ['name' => 'Pistachio', 'price' => 130.00],
                ['name' => 'Vanilla classic', 'price' => 110.00],
                ['name' => 'Caramel', 'price' => 130.00],
                ['name' => 'Oreo', 'price' => 130.00],
            ],
            'Ice Tea' => [
                ['name' => 'Strawberry', 'price' => 120.00],
                ['name' => 'Mango Peach', 'price' => 120.00],
                ['name' => 'Passion fruit', 'price' => 120.00],
                ['name' => 'Mango passion', 'price' => 120.00],
            ],
            'Milk Tea' => [
                ['name' => 'Boba Caramel', 'price' => 140.00],
                ['name' => 'Vanilla Boba', 'price' => 140.00],
            ],
            'Ice Matcha' => [
                ['name' => 'Honey Matcha', 'price' => 140.00],
                ['name' => 'Mango Matcha', 'price' => 160.00],
                ['name' => 'Strawberry Matcha', 'price' => 160.00],
                ['name' => 'Sweet Matcha', 'price' => 150.00],
            ],
            'Blend Matcha' => [
                ['name' => 'Honey matcha', 'price' => 140.00],
                ['name' => 'strawberry matcha', 'price' => 160.00],
                ['name' => 'mango matcha', 'price' => 160.00],
                ['name' => 'candy matcha', 'price' => 150.00],
            ],
            'Fresh Juice' => [
                ['name' => 'Mango kiwi', 'price' => 140.00],
                ['name' => 'Mango Ice Peach', 'price' => 140.00],
                ['name' => 'Milk strawberry Ice', 'price' => 140.00],
                ['name' => 'Fresh Mango', 'price' => 120.00],
                ['name' => 'Fresh Orange', 'price' => 100.00],
                ['name' => 'Fresh kiwi', 'price' => 130.00],
                ['name' => 'Fresh Watermelon', 'price' => 120.00],
                ['name' => 'Fresh Lemon', 'price' => 100.00],
                ['name' => 'Fresh Mint Lemon', 'price' => 100.00],
                ['name' => 'Kiwi Splash', 'price' => 140.00],
                ['name' => 'Tropical Glow', 'price' => 140.00],
                ['name' => 'Coco Cream', 'price' => 140.00],
            ],
            'Smoothie' => [
                ['name' => 'Watermelon', 'price' => 120.00],
                ['name' => 'Mango', 'price' => 120.00],
                ['name' => 'Blueberry', 'price' => 120.00],
                ['name' => 'strawberry', 'price' => 120.00],
                ['name' => 'kiwi', 'price' => 120.00],
            ],
            'Soda' => [
                ['name' => 'passion blue', 'price' => 140.00],
                ['name' => 'kiwi breeza', 'price' => 140.00],
                ['name' => 'berry bliss', 'price' => 140.00],
                ['name' => 'candy Milon', 'price' => 140.00],
                ['name' => 'coco wave', 'price' => 140.00],
                ['name' => 'sunny passion', 'price' => 140.00],
                ['name' => 'classic Mojito', 'price' => 120.00],
                ['name' => 'Watermelon', 'price' => 120.00],
                ['name' => 'Passion', 'price' => 130.00],
                ['name' => 'Strawberry', 'price' => 120.00],
                ['name' => 'Kiwi', 'price' => 120.00],
                ['name' => 'Pineapple', 'price' => 120.00],
            ],
            'Frappe' => [
                ['name' => 'Spanish', 'price' => 140.00],
                ['name' => 'Mocha', 'price' => 130.00],
                ['name' => 'Caramel', 'price' => 130.00],
                ['name' => 'Classic', 'price' => 130.00],
                ['name' => 'Mocha nuts', 'price' => 130.00],
            ],
            'Dessert' => [
                ['name' => 'Waffle Nutella', 'price' => 130.00],
                ['name' => 'Waffle Kinder', 'price' => 130.00],
                ['name' => 'Waffle Lotus', 'price' => 130.00],
                ['name' => 'Waffle Pistachio', 'price' => 140.00],
                ['name' => 'Fettuccine Nutella', 'price' => 150.00],
                ['name' => 'Fettuccine Kinder', 'price' => 150.00],
                ['name' => 'Fettuccine Lotus', 'price' => 150.00],
                ['name' => 'Fettuccine Pistachio', 'price' => 160.00],
                ['name' => 'Pan Cake Honey', 'price' => 160.00],
                ['name' => 'Pan Cake Nutella', 'price' => 140.00],
                ['name' => 'Pan Cake Kinder', 'price' => 140.00],
                ['name' => 'Pan Cake Lotus', 'price' => 140.00],
                ['name' => 'Pan Cake Pistachio', 'price' => 150.00],
                ['name' => 'San Sebastian Cake', 'price' => 150.00],
                ['name' => 'Molten Cake', 'price' => 150.00],
                ['name' => 'Cheese Cake', 'price' => 150.00],
            ],
            'Fruit Salad' => [
                ['name' => 'Classic Fruit Salad (M)', 'price' => 70.00],
                ['name' => 'Classic Fruit Salad (L)', 'price' => 180.00],
                ['name' => 'Special Fruit salad', 'price' => 600.00],
            ],
            'Beverage' => [
                ['name' => 'Tea', 'price' => 35.00],
                ['name' => 'Water', 'price' => 10.00],
                ['name' => 'Hot Cidar', 'price' => 100.00],
                ['name' => 'Red Bull', 'price' => 100.00],
            ],
            'Ice Cream' => [
                ['name' => 'Chocolate Ice Cream (S)', 'price' => 40.00],
                ['name' => 'Chocolate Ice Cream (L)', 'price' => 60.00],
                ['name' => 'Vanilla Ice Cream (S)', 'price' => 40.00],
                ['name' => 'Vanilla Ice Cream (L)', 'price' => 60.00],
            ],
        ];

        foreach ($menuData as $categoryName => $products) {
            $category = Category::create([
                'name' => $categoryName,
            ]);

            foreach ($products as $prod) {
                Menu::create([
                    'category_id'  => $category->id,
                    'name'         => $prod['name'],
                    'price'        => $prod['price'],
                    'is_available' => true,
                ]);
            }
        }
    }
}
