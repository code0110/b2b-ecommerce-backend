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
        Schema::create('sales_target_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_target_id')->constrained()->onDelete('cascade');
            $table->string('target_type'); // 'category', 'brand', 'product'
            $table->unsignedBigInteger('target_id'); // ID of the category/brand/product
            $table->decimal('target_amount', 15, 2)->default(0);
            $table->timestamps();

            // Ensure unique target for same type/id within a sales_target
            $table->unique(['sales_target_id', 'target_type', 'target_id'], 'unique_sales_target_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_target_items');
    }
};
