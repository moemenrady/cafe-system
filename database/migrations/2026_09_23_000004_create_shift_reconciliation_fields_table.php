<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('opening_float', 10, 2)->default(0)->after('user_id');
            $table->decimal('cash_sales', 10, 2)->default(0)->after('opening_float');
            $table->decimal('card_sales', 10, 2)->default(0)->after('cash_sales');
            $table->decimal('instapay_sales', 10, 2)->default(0)->after('card_sales');
            $table->decimal('cash_drops', 10, 2)->default(0)->after('instapay_sales');
            $table->decimal('expected_cash', 10, 2)->default(0)->after('cash_drops');
            $table->decimal('actual_cash', 10, 2)->nullable()->after('expected_cash');
            $table->decimal('difference', 10, 2)->nullable()->after('actual_cash');
            $table->enum('status', ['open', 'closed'])->default('open')->after('difference');
            $table->text('closing_notes')->nullable()->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('table_id')->constrained('shifts')->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('order_id')->constrained('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Shift::class);
            $table->dropColumn('shift_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Shift::class);
            $table->dropColumn('shift_id');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn([
                'opening_float',
                'cash_sales',
                'card_sales',
                'instapay_sales',
                'cash_drops',
                'expected_cash',
                'actual_cash',
                'difference',
                'status',
                'closing_notes',
            ]);
        });
    }
};
