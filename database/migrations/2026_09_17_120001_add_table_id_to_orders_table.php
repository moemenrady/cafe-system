<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // نضيف table_id كـ foreign key للطاولة مع الحفاظ على table_number القديمة
            $table->foreignId('table_id')
                ->nullable()
                ->after('customer_id')
                ->constrained('tables')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Table::class);
            $table->dropColumn('table_id');
        });
    }
};
