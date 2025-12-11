<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('mobile_image')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_iterative')->default(false);
            $table->enum('bonus_type', ['free_item', 'discount_value', 'discount_percent'])->default('discount_percent');
            $table->decimal('min_cart_total', 15, 2)->default(0);
            $table->integer('min_qty_per_product')->default(0);
            $table->enum('customer_type', ['b2b', 'b2c', 'both'])->default('both');
            $table->boolean('logged_in_only')->default(false);
            $table->timestamps();
        });

        Schema::create('customer_group_promotion', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('customer_group_id');
            $table->primary(['promotion_id', 'customer_group_id']);
        });

        Schema::create('customer_promotion', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('customer_id');
            $table->primary(['promotion_id', 'customer_id']);
        });

        Schema::create('category_promotion', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('category_id');
            $table->primary(['promotion_id', 'category_id']);
        });

        Schema::create('brand_promotion', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('brand_id');
            $table->primary(['promotion_id', 'brand_id']);
        });

        Schema::create('product_promotion', function (Blueprint $table) {
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedBigInteger('product_id');
            $table->primary(['promotion_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_promotion');
        Schema::dropIfExists('brand_promotion');
        Schema::dropIfExists('category_promotion');
        Schema::dropIfExists('customer_promotion');
        Schema::dropIfExists('customer_group_promotion');
        Schema::dropIfExists('promotions');
    }
};
