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
        if (Schema::hasTable('orders') && !Schema::hasColumn('orders', 'closed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable()->after('status');
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('invoices', 'subtotal')) {
                    $table->decimal('subtotal', 12, 2)->default(0)->after('invoice_number');
                }
                if (!Schema::hasColumn('invoices', 'vat')) {
                    $table->decimal('vat', 12, 2)->default(0)->after('discount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'closed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('closed_at');
            });
        }

        if (Schema::hasTable('invoices')) {
            Schema::table('invoices', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('invoices', 'subtotal')) {
                    $columns[] = 'subtotal';
                }
                if (Schema::hasColumn('invoices', 'vat')) {
                    $columns[] = 'vat';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
