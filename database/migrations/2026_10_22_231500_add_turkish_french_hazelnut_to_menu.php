<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Category;
use App\Models\Menu;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // العثور على أو إنشاء قسم Hot Drinks
        $category = Category::firstOrCreate(
            ['name' => 'Hot Drinks']
        );

        // إدخال الأصناف الثلاثة بالأسعار المطلوبة بدقة:
        // تركي ب 40
        // فرنساوي 70
        // بندق 70
        $items = [
            ['name' => 'Turkish Coffee', 'price' => 40.00],
            ['name' => 'French Coffee', 'price' => 70.00],
            ['name' => 'Hazelnut Coffee', 'price' => 70.00],
        ];

        foreach ($items as $item) {
            Menu::updateOrCreate(
                [
                    'category_id' => $category->id,
                    'name'        => $item['name'],
                ],
                [
                    'price'        => $item['price'],
                    'is_available' => true,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Menu::whereIn('name', ['Turkish Coffee', 'French Coffee', 'Hazelnut Coffee'])->delete();
    }
};
