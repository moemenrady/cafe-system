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
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('status', 30)->default('paid')->after('payment_method');
            $table->timestamp('refunded_at')->nullable()->after('note');
            $table->text('refund_reason')->nullable()->after('refunded_at');
            $table->foreignId('refunded_by')->nullable()->after('refund_reason')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['refunded_by']);
            $table->dropColumn(['status', 'refunded_at', 'refund_reason', 'refunded_by']);
        });
    }
};
