<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['table_id', 'status', 'payment_status'], 'idx_orders_table_active_status');
            $table->index(['created_at', 'status'], 'idx_orders_created_status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->index('created_at', 'idx_invoices_created_at');
            $table->index(['payment_method', 'created_at'], 'idx_invoices_payment_date');
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->index(['inventory_item_id', 'created_at'], 'idx_movements_item_date');
            $table->index(['type', 'created_at'], 'idx_movements_type_date');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('idx_movements_type_date');
            $table->dropIndex('idx_movements_item_date');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_payment_date');
            $table->dropIndex('idx_invoices_created_at');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_created_status');
            $table->dropIndex('idx_orders_table_active_status');
        });
    }
};
