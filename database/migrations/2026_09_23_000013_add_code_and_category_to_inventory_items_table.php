<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'code')) {
                $table->string('code')->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('inventory_items', 'category')) {
                $table->string('category')->nullable()->default('خامات ومشروبات')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (Schema::hasColumn('inventory_items', 'code')) {
                $table->dropColumn('code');
            }
            if (Schema::hasColumn('inventory_items', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
