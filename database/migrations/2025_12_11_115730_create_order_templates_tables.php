<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');   // proprietarul (de obicei B2B)
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('order_template_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_template_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->decimal('quantity', 15, 3)->default(1);
            $table->timestamps();

            $table->foreign('order_template_id')->references('id')->on('order_templates')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_template_items');
        Schema::dropIfExists('order_templates');
    }
};
