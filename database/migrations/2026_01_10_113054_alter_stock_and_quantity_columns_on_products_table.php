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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock_qty', 15, 4)->default(0)->change();
            $table->decimal('supplier_stock_qty', 15, 4)->default(0)->change();
            $table->decimal('min_order_quantity', 15, 4)->nullable()->change();
            $table->decimal('order_quantity_step', 15, 4)->nullable()->change();
            // Adding packaging unit column if it doesn't exist, useful for display like "cutii", "bare"
            $table->string('packaging_unit', 50)->nullable()->after('unit_of_measure'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_qty')->default(0)->change();
            $table->integer('supplier_stock_qty')->default(0)->change();
            $table->integer('min_order_quantity')->nullable()->change();
            $table->integer('order_quantity_step')->nullable()->change();
            $table->dropColumn('packaging_unit');
        });
    }
};
