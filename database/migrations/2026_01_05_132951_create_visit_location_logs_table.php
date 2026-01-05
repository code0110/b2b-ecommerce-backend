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
        Schema::create('visit_location_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_visit_id')->constrained('customer_visits')->onDelete('cascade');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy')->nullable(); // Precizie în metri
            $table->integer('battery_level')->nullable(); // Nivel baterie (opțional, util pentru diagnostic)
            $table->string('provider')->nullable(); // gps, network, etc.
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_location_logs');
    }
};
