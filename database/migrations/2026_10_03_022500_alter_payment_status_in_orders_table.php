<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `invoice_transactions` MODIFY COLUMN `action` VARCHAR(50) NOT NULL DEFAULT 'create'");
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('payment_status', 50)->default('pending')->change();
            });
            Schema::table('invoice_transactions', function (Blueprint $table) {
                $table->string('action', 50)->default('create')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `payment_status` ENUM('pending', 'paid') NOT NULL DEFAULT 'pending'");
            DB::statement("ALTER TABLE `invoice_transactions` MODIFY COLUMN `action` ENUM('create', 'update', 'delete') NOT NULL DEFAULT 'create'");
        } else {
            Schema::table('orders', function (Blueprint $table) {
                $table->enum('payment_status', ['pending', 'paid'])->default('pending')->change();
            });
            Schema::table('invoice_transactions', function (Blueprint $table) {
                $table->enum('action', ['create', 'update', 'delete'])->default('create')->change();
            });
        }
    }
};
