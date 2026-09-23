<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('printer_jobs', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id')->unique();
            $table->string('device_uuid', 100)->nullable()->after('uuid')->index();
            $table->unsignedSmallInteger('attempts')->default(0)->after('status');
            $table->timestamp('started_at')->nullable()->after('attempts');
            $table->timestamp('printed_at')->nullable()->after('started_at');
            $table->text('error_message')->nullable()->after('printed_at');
        });

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE printer_jobs MODIFY COLUMN status ENUM('pending', 'printing', 'processing', 'printed', 'failed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        Schema::table('printer_jobs', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'device_uuid',
                'attempts',
                'started_at',
                'printed_at',
                'error_message',
            ]);
        });
    }
};
