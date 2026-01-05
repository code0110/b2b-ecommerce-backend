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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // e.g., 'offers', 'general', 'notifications'
            $table->string('type')->default('string'); // 'string', 'integer', 'boolean', 'json'
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed default settings
        DB::table('settings')->insert([
            [
                'key' => 'offer_discount_threshold_approval',
                'value' => '15',
                'group' => 'offers',
                'type' => 'integer',
                'description' => 'Discount percentage threshold above which director approval is required (15-20%).',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'offer_discount_max',
                'value' => '20',
                'group' => 'offers',
                'type' => 'integer',
                'description' => 'Maximum allowed discount percentage for offers.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
