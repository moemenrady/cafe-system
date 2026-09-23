<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();


            // Print destination/type
            $table->enum('type', [
                'cashier_takeaway',
                'cashier_delivery',
                'cashier_dine_in',
                'barista',
            ])->index();

            // Print data
            $table->json('payload');

            // Print job status
            $table->enum('status', [
                'pending',
                'printed',
                'failed',
            ])->default('pending')->index();


            // Processing timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('printed_at')->nullable();

            $table->timestamps();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};