<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('id')->constrained('expense_categories')->nullOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('amount')->constrained('users')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('expenses', 'expense_date')) {
                $table->date('expense_date')->nullable()->after('created_by');
            }
            $table->string('title')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'category_id')) {
                $table->dropForeign(['category_id']);
                $table->dropColumn('category_id');
            }
            if (Schema::hasColumn('expenses', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('expenses', 'expense_date')) {
                $table->dropColumn('expense_date');
            }
            $table->string('title')->nullable(false)->change();
        });
    }
};
