<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shift_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('action_type', 50); // order_created, invoice_created, expense_created, invoice_updated, expense_updated, cash_drop, shift_closed
            $table->string('action_title');
            $table->string('model_type')->nullable(); // Order, Invoice, Expense
            $table->unsignedBigInteger('model_id')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['shift_id', 'created_at']);
            $table->index('user_id');
            $table->index('action_type');
            $table->index(['model_type', 'model_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_actions');
    }
};
