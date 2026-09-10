<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            
            // ربطها بجدول الـ menu المتواجد عندك (المنتجات)
            $table->foreignId('menu_id')->nullable()->constrained('menu')->nullOnDelete(); 
            //$table->foreignId('service_id')->nullable()->constrained()->nullOnDelete(); // للمستقبل لو ضفت خدمات
            
            $table->integer('quantity');
            $table->decimal('item_price', 12, 2); // Snapshot Price
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
