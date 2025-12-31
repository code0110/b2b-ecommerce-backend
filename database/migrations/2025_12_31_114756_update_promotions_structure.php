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
        Schema::table('promotions', function (Blueprint $table) {
            // Priority & Stacking
            $table->integer('priority')->default(0)->after('is_iterative');
            $table->enum('stacking_type', ['exclusive', 'iterative'])->default('iterative')->after('priority');
            
            // Flexible Triggers & Benefits
            // We keep existing columns (min_cart_total, etc.) for backward compatibility or simple UI,
            // but add JSON payloads for complex scenarios (Bundles, Mix & Match).
            $table->enum('trigger_type', ['simple', 'product_combination', 'category_combination'])->default('simple')->after('customer_type');
            $table->json('trigger_payload')->nullable()->after('trigger_type'); // For specific SKU lists, combinations, etc.
            
            $table->enum('benefit_type', ['discount_percent', 'discount_fixed', 'free_item', 'special_price', 'bundle_price'])->default('discount_percent')->after('bonus_type');
            $table->json('benefit_payload')->nullable()->after('benefit_type'); // For free item SKU, special price value, etc.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'priority', 
                'stacking_type', 
                'trigger_type', 
                'trigger_payload', 
                'benefit_type', 
                'benefit_payload'
            ]);
        });
    }
};
