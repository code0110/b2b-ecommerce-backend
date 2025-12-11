<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['courier', 'own_fleet', 'pickup'])->default('courier');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipping_method_id');
            $table->decimal('min_weight', 10, 2)->default(0);
            $table->decimal('max_weight', 10, 2)->default(0);
            $table->decimal('min_value', 15, 2)->default(0);
            $table->decimal('max_value', 15, 2)->default(0);
            $table->string('region', 100)->nullable();
            $table->string('county', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('free_over', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rules');
        Schema::dropIfExists('shipping_methods');
    }
};
