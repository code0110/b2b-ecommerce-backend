<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_comparisons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_key', 100)->nullable();
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->index(['user_id', 'product_id']);
            $table->index(['session_key', 'product_id']);
            $table->unique(['user_id', 'product_id']);
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_comparisons');
    }
};
