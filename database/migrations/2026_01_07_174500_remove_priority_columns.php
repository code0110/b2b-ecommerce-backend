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
        Schema::table('discount_rules', function (Blueprint $table) {
            $table->dropColumn(['priority', 'is_exclusive']);
        });

        Schema::table('promotions', function (Blueprint $table) {
            // We only drop priority. Exclusivity might still be useful for "Don't combine"?
            // User asked to remove priority.
            // If we use "Best Deal", exclusivity is implicit (we pick one).
            // So we can drop priority.
            $table->dropColumn('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('discount_rules', function (Blueprint $table) {
            $table->integer('priority')->default(0);
            $table->boolean('is_exclusive')->default(false);
        });

        Schema::table('promotions', function (Blueprint $table) {
            $table->integer('priority')->default(0);
        });
    }
};
