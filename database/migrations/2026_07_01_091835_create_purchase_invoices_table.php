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
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // الأدمن اللي سجل الفاتورة
            $table->foreignId('inventory_item_id')->constrained()->onDelete('cascade'); // المادة الخام اللي اشتراها
            $table->decimal('quantity', 10, 2); // الكمية اللي دخلت المخزن
            $table->decimal('cost', 10, 2); // التكلفة الإجمالية للشروة
            $table->string('supplier_name')->nullable(); // اسم المورد (اختياري)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};
