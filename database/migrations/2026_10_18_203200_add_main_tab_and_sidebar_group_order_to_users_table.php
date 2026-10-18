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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'main_tab')) {
                $table->string('main_tab')->nullable()->after('sidebar_order');
            }
            if (!Schema::hasColumn('users', 'sidebar_group_order')) {
                $table->json('sidebar_group_order')->nullable()->after('main_tab');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'sidebar_group_order')) {
                $table->dropColumn('sidebar_group_order');
            }
            if (Schema::hasColumn('users', 'main_tab')) {
                $table->dropColumn('main_tab');
            }
        });
    }
};
