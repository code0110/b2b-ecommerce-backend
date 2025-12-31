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
            if (!Schema::hasColumn('promotions', 'applies_to')) {
                $table->enum('applies_to', ['all', 'products', 'categories', 'brands'])->default('all')->after('status');
            }
            if (!Schema::hasColumn('promotions', 'discount_percent')) {
                $table->decimal('discount_percent', 5, 2)->nullable()->after('bonus_type');
            }
            if (!Schema::hasColumn('promotions', 'discount_value')) {
                $table->decimal('discount_value', 10, 2)->nullable()->after('discount_percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            if (Schema::hasColumn('promotions', 'applies_to')) {
                $table->dropColumn('applies_to');
            }
            if (Schema::hasColumn('promotions', 'discount_percent')) {
                $table->dropColumn('discount_percent');
            }
            if (Schema::hasColumn('promotions', 'discount_value')) {
                $table->dropColumn('discount_value');
            }
        });
    }
};
