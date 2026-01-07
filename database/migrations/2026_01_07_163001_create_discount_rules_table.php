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
        Schema::create('discount_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('rule_type')->default('approval_threshold'); // approval_threshold, max_discount
            $table->string('target_type')->default('global'); // global, role, user
            $table->unsignedBigInteger('target_id')->nullable();
            $table->decimal('limit_percent', 5, 2);
            $table->integer('priority')->default(0);
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('apply_to_total')->default(true); // true = limit applies to (Manual + Promo), false = limit applies only to Manual
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_rules');
    }
};
