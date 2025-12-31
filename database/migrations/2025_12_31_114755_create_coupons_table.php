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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            
            // Discount logic
            $table->enum('discount_type', ['percent', 'fixed_cart', 'fixed_product'])->default('percent');
            $table->decimal('discount_value', 10, 2); // e.g., 10% or 50 RON
            
            // Limits
            $table->integer('usage_limit')->nullable(); // Total times can be used
            $table->integer('usage_limit_per_user')->nullable(); // Times per user
            $table->decimal('min_cart_value', 12, 2)->nullable();
            
            // Validity
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            // Conflict / Stacking
            // If true, can be used with other promotions. If false, might clear other promos or be rejected.
            $table->boolean('is_stackable')->default(true); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
