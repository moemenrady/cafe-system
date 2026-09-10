<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_jobs', function (Blueprint $table) {

            $table->id();

            $table->enum('type', [
                'kitchen',
                'waiter',
                'customer',
                'delivery',
            ]);

            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->json('payload');

            $table->enum('status', [
                'pending',
                'printing',
                'printed',
                'failed',
            ])->default('pending');

            $table->unsignedInteger('copies')->default(1);

            $table->string('printer_name')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_jobs');
    }
};