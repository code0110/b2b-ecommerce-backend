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
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('is_partner');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });

        Schema::table('customer_visits', function (Blueprint $table) {
            // Existing latitude/longitude are considered 'start'
            $table->decimal('end_latitude', 10, 8)->nullable()->after('longitude');
            $table->decimal('end_longitude', 11, 8)->nullable()->after('end_latitude');
            $table->integer('distance_deviation')->nullable()->comment('Distance in meters from customer location at check-in')->after('end_longitude');
            $table->boolean('is_off_site')->default(false)->comment('True if check-in was far from customer location')->after('distance_deviation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('customer_visits', function (Blueprint $table) {
            $table->dropColumn(['end_latitude', 'end_longitude', 'distance_deviation', 'is_off_site']);
        });
    }
};
