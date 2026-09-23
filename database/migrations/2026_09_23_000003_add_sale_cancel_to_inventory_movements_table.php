<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_movements MODIFY COLUMN type ENUM('sale', 'sale_update', 'sale_cancel', 'restock', 'waste') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE inventory_movements MODIFY COLUMN type ENUM('sale', 'sale_update', 'restock', 'waste') NOT NULL");
        }
    }
};
