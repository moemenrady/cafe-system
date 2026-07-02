<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamp('email_verified_at')->nullable();
        
        // 🌟 تحديث الأدوار المتاحة في النظام (البارسيتا يكتب Barista)
        $table->enum('role', ['admin', 'supervisor', 'cashier', 'barista', 'client'])->default('cashier');
        
        // 🌟 إضافة حقل حالة الحساب لتفعيل أو تعطيل الموظف
        $table->boolean('is_active')->default(true);
        
        $table->rememberToken();
        $table->timestamps();
        $table->softDeletes();
        
        // الفهارس لتسريع البحث
        $table->index('email');
        $table->index('role');
        $table->index('is_active');
        $table->index('deleted_at');
    });
}

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
