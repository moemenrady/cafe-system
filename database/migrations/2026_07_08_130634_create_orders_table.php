<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->unsignedInteger('table_number')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('phone')->nullable();
            $table->string('delivery_person')->nullable();
            $table->enum('type', [
                'dine_in',
                'takeaway',
                'delivery'
            ]);
            $table->enum('status', [
                'open',
                'waiting_delivery',
                'completed',
                'cancelled'
            ])->default('open');
            $table->enum('payment_status', [
                'pending',
                'paid'
            ])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('vat', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
