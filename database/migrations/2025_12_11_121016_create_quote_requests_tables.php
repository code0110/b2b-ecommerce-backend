<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('created_by_user_id');
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->string('status', 50)->default('new'); // new, in_review, offered, approved, rejected
            $table->string('source', 50)->nullable();     // product, cart, manual
            $table->decimal('estimated_total', 15, 2)->default(0);
            $table->decimal('offered_total', 15, 2)->default(0);
            $table->timestamp('valid_until')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('assigned_agent_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('quote_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_request_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->decimal('quantity', 15, 3);
            $table->decimal('list_price', 15, 2)->default(0);
            $table->decimal('requested_price', 15, 2)->nullable();
            $table->decimal('offered_price', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('quote_request_id')->references('id')->on('quote_requests')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_request_items');
        Schema::dropIfExists('quote_requests');
    }
};
