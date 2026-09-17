<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();           // مثال: "طاولة 1", "VIP 1"
            $table->unsignedInteger('capacity')->nullable(); // عدد الكراسي
            $table->string('area')->nullable();          // مثال: "صالة رئيسية", "VIP"
            $table->string('notes')->nullable();         // ملاحظات اختيارية
            $table->boolean('is_active')->default(true); // نشط / غير نشط
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
