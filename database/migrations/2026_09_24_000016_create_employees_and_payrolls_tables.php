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
        // 1. جدول الموظفين (Employees)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('national_id')->nullable(); // رقم البطاقة القومية (اختياري)
            $table->string('address')->nullable();     // عنوان السكن (اختياري)
            $table->string('job_title')->default('موظف'); // المسمى الوظيفي (كاشير، باريستا، ويتر، شيف، إلخ)
            $table->decimal('base_salary', 10, 2)->default(0.00); // المرتب الشهري
            $table->date('hire_date')->nullable();     // تاريخ التعيين / بدء العمل
            $table->boolean('is_active')->default(true); // حالة الموظف (على رأس العمل / متوقف)
            $table->text('notes')->nullable();         // ملاحظات إدارية
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('phone');
            $table->index('is_active');
            $table->index('hire_date');
        });

        // 2. جدول سجل الرواتب الشهرية والقبض (Employee Payrolls)
        Schema::create('employee_payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('salary_month', 7); // مثلاً '2026-09'
            $table->decimal('base_salary', 10, 2)->default(0.00);
            $table->decimal('total_bonus', 10, 2)->default(0.00);      // إجمالي المكافآت والبونص للشهر
            $table->decimal('total_deductions', 10, 2)->default(0.00); // إجمالي الخصومات للشهر
            $table->decimal('net_salary', 10, 2)->default(0.00);       // صافي المرتب المستحق (أساسي + بونص - خصومات)
            $table->decimal('paid_amount', 10, 2)->default(0.00);      // المبلغ المصروف فعلياً
            $table->enum('status', ['pending', 'paid', 'partial'])->default('pending'); // حالة الصرف
            $table->dateTime('payment_date')->nullable();              // تاريخ وتوقيت الصرف
            $table->string('payment_method')->default('cash');         // كاش / فيزا / إنستاباي / تحويل بنكي
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete(); // المدير الذي سجل الصرف
            $table->text('notes')->nullable();
            $table->timestamps();

            // سجل واحد فقط لكل موظف في الشهر الواحد
            $table->unique(['employee_id', 'salary_month']);
            $table->index('salary_month');
            $table->index('status');
        });

        // 3. جدول الخصومات والمكافآت (Employee Adjustments: Bonuses & Deductions)
        Schema::create('employee_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('employee_payrolls')->nullOnDelete();
            $table->string('salary_month', 7); // الشهر المتأثر (مثلاً '2026-09')
            $table->enum('type', ['bonus', 'deduction']); // مكافأة (+) أو خصم (-)
            $table->decimal('amount', 10, 2); // القيمة
            $table->string('reason');         // السبب / البيان
            $table->date('date');             // تاريخ الواقعة / الحركة
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'salary_month']);
            $table->index('type');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_adjustments');
        Schema::dropIfExists('employee_payrolls');
        Schema::dropIfExists('employees');
    }
};
