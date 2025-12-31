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
        Schema::create('contract_prices', function (Blueprint $table) {
            $table->id();
            
            // Link to Product
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            
            // Link to Customer OR Customer Group (Priority: Customer > Group)
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained()->cascadeOnDelete();
            
            // The special price
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('RON');
            
            $table->timestamps();
            
            // Indexes for fast lookup
            $table->index(['product_id', 'customer_id']);
            $table->index(['product_id', 'customer_group_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_prices');
    }
};
