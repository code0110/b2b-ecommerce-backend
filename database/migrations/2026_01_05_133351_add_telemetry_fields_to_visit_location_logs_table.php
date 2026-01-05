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
        Schema::table('visit_location_logs', function (Blueprint $table) {
            $table->float('speed')->nullable(); // Viteza în m/s
            $table->float('heading')->nullable(); // Direcția (grade față de Nord)
            $table->float('altitude')->nullable(); // Altitudine în metri
            $table->string('network_type')->nullable(); // 'wifi', '4g', etc.
            $table->boolean('is_mocked')->default(false); // Flag pentru locații simulate (fake GPS)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visit_location_logs', function (Blueprint $table) {
            $table->dropColumn(['speed', 'heading', 'altitude', 'network_type', 'is_mocked']);
        });
    }
};
