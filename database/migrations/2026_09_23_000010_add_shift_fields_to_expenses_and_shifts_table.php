<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'shift_id')) {
                $table->foreignId('shift_id')->nullable()->after('created_by')->constrained('shifts')->nullOnDelete();
            }
        });

        Schema::table('shifts', function (Blueprint $table) {
            if (!Schema::hasColumn('shifts', 'expenses_total')) {
                $table->decimal('expenses_total', 10, 2)->default(0)->after('cash_drops');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            if (Schema::hasColumn('shifts', 'expenses_total')) {
                $table->dropColumn('expenses_total');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'shift_id')) {
                $table->dropForeign(['shift_id']);
                $table->dropColumn('shift_id');
            }
        });
    }
};
