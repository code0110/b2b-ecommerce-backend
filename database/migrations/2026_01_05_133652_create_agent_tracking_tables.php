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
        // Tabela pentru rezumatul zilei (Tura/Shift)
        Schema::create('agent_daily_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->float('total_distance')->default(0); // km
            $table->string('status')->default('active'); // active, completed
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        // Tabela pentru punctele detaliate ale rutei (Breadcrumbs)
        Schema::create('route_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_daily_route_id')->constrained('agent_daily_routes')->onDelete('cascade');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->float('accuracy')->nullable();
            $table->float('speed')->nullable();
            $table->float('heading')->nullable();
            $table->timestamp('recorded_at');
            $table->integer('battery_level')->nullable();
            $table->boolean('is_mocked')->default(false);
            $table->timestamps();
            
            // Index spațial sau compus ar fi ideal, dar simplu index e suficient momentan
            $table->index('agent_daily_route_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_points');
        Schema::dropIfExists('agent_daily_routes');
    }
};
