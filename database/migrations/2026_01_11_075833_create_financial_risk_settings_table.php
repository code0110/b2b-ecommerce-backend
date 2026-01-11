<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_risk_settings', function (Blueprint $table) {
            $table->id();
            
            // Invoices Count Thresholds
            $table->integer('warning_threshold_invoices')->default(1);
            $table->integer('approval_threshold_invoices')->default(3);
            $table->integer('block_threshold_invoices')->default(5);

            // Overdue Days Thresholds
            $table->integer('warning_threshold_days')->default(7);
            $table->integer('approval_threshold_days')->default(15);
            $table->integer('block_threshold_days')->default(30);

            $table->timestamps();
        });

        // Seed default values
        DB::table('financial_risk_settings')->insert([
            'warning_threshold_invoices' => 1,
            'approval_threshold_invoices' => 3,
            'block_threshold_invoices' => 5,
            'warning_threshold_days' => 7,
            'approval_threshold_days' => 15,
            'block_threshold_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_risk_settings');
    }
};
