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
        // 1. جدول الموردين (Suppliers)
        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name')->index(); // اسم المورد
                $table->string('company_name')->nullable(); // اسم الشركة / المؤسسة
                $table->string('phone')->nullable()->index(); // رقم الهاتف
                $table->string('email')->nullable(); // البريد الإلكتروني
                $table->string('address')->nullable(); // العنوان
                $table->decimal('opening_balance', 12, 2)->default(0); // رصيد افتتاحي (مدين أو دائن)
                $table->text('notes')->nullable(); // ملاحظات إضافية
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. ربط فواتير الشراء بجدول الموردين
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->after('user_id')->constrained('suppliers')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_invoices', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
        });

        Schema::dropIfExists('suppliers');
    }
};
