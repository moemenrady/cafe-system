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
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'recipe_unit')) {
                $table->string('recipe_unit')->nullable()->after('unit'); // وحدة الاستخدام والخصم بالريسيبي (مل، جرام، قطعة)
            }
            if (!Schema::hasColumn('inventory_items', 'purchase_unit')) {
                $table->string('purchase_unit')->nullable()->after('recipe_unit'); // وحدة الشراء الأصلية بالفاتورة (كرتونة، جردل، باكت)
            }
            if (!Schema::hasColumn('inventory_items', 'conversion_factor')) {
                $table->decimal('conversion_factor', 10, 2)->default(1.00)->after('purchase_unit'); // معامل التحويل
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $cols = ['recipe_unit', 'purchase_unit', 'conversion_factor'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('inventory_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
