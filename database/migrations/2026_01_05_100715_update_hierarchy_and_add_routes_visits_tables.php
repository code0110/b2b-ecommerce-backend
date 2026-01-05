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
        // 1. Add director_id to users table (Hierarchy: Agent -> Director)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('director_id')->nullable()->constrained('users')->nullOnDelete();
        });

        // 2. Create agent_routes table (Route Planning)
        Schema::create('agent_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['agent_id', 'customer_id', 'day_of_week']);
        });

        // 3. Create customer_visits table (Visits)
        Schema::create('customer_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('status', ['planned', 'in_progress', 'completed', 'missed'])->default('planned');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_visits');
        Schema::dropIfExists('agent_routes');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropColumn('director_id');
        });
    }
};
