<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('erp_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50); // product, stock, price_list, order, invoice, customer
            $table->string('direction', 20);   // push, pull
            $table->string('external_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending, success, error
            $table->json('payload')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('run_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'direction', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_sync_logs');
    }
};
