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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // اسم المادة الخام (مثل: بن برزيلي، لبن كامل الدسم، أكواب 12 أونص)
            $table->decimal('quantity', 10, 2)->default(0.00); // الكمية الحالية المتوفرة في المخزن
            $table->string('unit'); // الوحدة (جرام gm، مل ml، قطعة pcs، كيس bag)
            $table->decimal('reorder_level', 10, 2)->default(0.00); // الحد الأدنى (لو الكمية وصلت للرقم ده أو أقل، تعتبر من النواقص)
            $table->decimal('unit_price', 10, 2)->default(0.00); // سعر الوحدة (مثلاً سعر الكيلو أو سعر اللتر أو سعر القطعة)
            $table->timestamps();
            $table->softDeletes(); // للحذف المؤقت عشان الأمان
            // تحسين الأداء عند البحث أو الفرز
            $table->index('name');
            $table->index('quantity');
            $table->index('unit_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
