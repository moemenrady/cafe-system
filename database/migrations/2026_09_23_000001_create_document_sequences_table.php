<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->string('type', 30); // 'order', 'invoice'
            $table->string('date_key', 8); // 'YYYYMMDD'
            $table->unsignedBigInteger('last_number')->default(0);
            $table->primary(['type', 'date_key']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_sequences');
    }
};
