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
            // Add new columns for the robust system
            if (!Schema::hasColumn('promotions', 'type')) {
                $table->enum('type', ['standard', 'volume', 'bundle', 'shipping', 'special_price', 'gift'])->default('standard')->after('slug');
            }
            if (!Schema::hasColumn('promotions', 'value_type')) {
                $table->enum('value_type', ['percent', 'fixed_amount', 'fixed_price'])->default('percent')->after('type');
            }
            if (!Schema::hasColumn('promotions', 'value')) {
                $table->decimal('value', 15, 2)->default(0)->after('value_type');
            }
            
            // Check for priority before adding
            if (!Schema::hasColumn('promotions', 'priority')) {
                $table->integer('priority')->default(0)->after('status');
            }

            if (!Schema::hasColumn('promotions', 'settings')) {
                $table->json('settings')->nullable()->after('end_at');
            }
            if (!Schema::hasColumn('promotions', 'conditions')) {
                $table->json('conditions')->nullable()->after('settings');
            }
            
            // Drop redundant/old columns to clean up
            $columnsToDrop = [];
            if (Schema::hasColumn('promotions', 'bonus_type')) $columnsToDrop[] = 'bonus_type';
            if (Schema::hasColumn('promotions', 'trigger_type')) $columnsToDrop[] = 'trigger_type';
            if (Schema::hasColumn('promotions', 'trigger_payload')) $columnsToDrop[] = 'trigger_payload';
            if (Schema::hasColumn('promotions', 'benefit_type')) $columnsToDrop[] = 'benefit_type';
            if (Schema::hasColumn('promotions', 'benefit_payload')) $columnsToDrop[] = 'benefit_payload';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn(['type', 'value_type', 'value', 'settings', 'conditions']);
            
            // Re-add dropped columns if needed (simplified rollback)
            if (!Schema::hasColumn('promotions', 'bonus_type')) {
                $table->enum('bonus_type', ['free_item', 'discount_value', 'discount_percent'])->default('discount_percent');
            }
        });
    }
};
