<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            // ربط الحركة بالصنف الخام (بن وميلك وسكر..)
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            
            // نوع الحركة: بيع، تعديل بيع، توريد جديد، هالك
            $table->enum('type', ['sale', 'sale_update', 'restock', 'waste']);
            
            // الكمية (موجبة في التوريد والإرجاع، سالبة في البيع والهالك)
            $table->decimal('quantity', 10, 2);
            
            // الرصيد الفعلي للمخزن "بعد" هذه الحركة مباشرة (مهم جداً للرقابة والتتبع التاريخي)
            $table->decimal('balance_after', 10, 2);
            
            // حقول اختيارية للربط بالفاتورة والموظف المتسبب في الحركة
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};