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
        Schema::table('agent_routes', function (Blueprint $table) {
            $table->enum('week_type', ['all', 'odd', 'even'])->default('all')->after('day_of_week');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_visit_id')->nullable()->constrained('customer_visits')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('customer_visit_id')->nullable()->constrained('customer_visits')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_routes', function (Blueprint $table) {
            $table->dropColumn('week_type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_visit_id']);
            $table->dropColumn('customer_visit_id');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['customer_visit_id']);
            $table->dropColumn('customer_visit_id');
        });
    }
};
