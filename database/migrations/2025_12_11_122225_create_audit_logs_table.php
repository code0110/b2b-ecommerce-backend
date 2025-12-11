<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100);          // ex: price_update, promotion_status_change, payment_recorded
            $table->string('entity_type', 100);     // Product, Promotion, Customer, Payment etc.
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('changes')->nullable();    // before/after
            $table->json('meta')->nullable();       // orice alt context (IP, etc.)
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['action']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
