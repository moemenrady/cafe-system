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
        if (Schema::hasTable('printer_jobs')) {
            Schema::table('printer_jobs', function (Blueprint $table) {
                if (!Schema::hasColumn('printer_jobs', 'uuid')) {
                    $table->string('uuid', 64)->nullable()->index()->after('id');
                }
                if (!Schema::hasColumn('printer_jobs', 'device_uuid')) {
                    $table->string('device_uuid', 64)->nullable()->after('uuid');
                }
                if (!Schema::hasColumn('printer_jobs', 'printer_identifier')) {
                    $table->string('printer_identifier', 64)->nullable()->after('device_uuid');
                }
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'table_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('table_number', 100)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('printer_jobs')) {
            Schema::table('printer_jobs', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('printer_jobs', 'printer_identifier')) {
                    $columns[] = 'printer_identifier';
                }
                if (Schema::hasColumn('printer_jobs', 'device_uuid')) {
                    $columns[] = 'device_uuid';
                }
                if (Schema::hasColumn('printer_jobs', 'uuid')) {
                    $columns[] = 'uuid';
                }
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'table_number')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedInteger('table_number')->nullable()->change();
            });
        }
    }
};
