<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            
            // التعديل هنا: بما أن الجدول الآن اسمه 'menu' وليس 'menu_items'،
            // يجب تحديد الجدول يدوياً لأن لارافيل بيبحث افتراضياً عن 'menu_items'
            $table->foreignId('menu_item_id')->constrained('menu')->onDelete('cascade');
            
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->decimal('quantity_used', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
