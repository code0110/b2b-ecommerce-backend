<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('internal_code', 100)->unique();
            $table->string('barcode', 100)->nullable();
            $table->string('erp_id', 100)->nullable();
            $table->text('short_description')->nullable();
            $table->longText('long_description')->nullable();
            $table->unsignedBigInteger('main_category_id');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->enum('status', ['published', 'hidden'])->default('hidden');
            $table->integer('sort_order')->default(0);
            $table->decimal('list_price', 15, 2)->default(0);
            $table->decimal('rrp_price', 15, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(19);
            $table->decimal('price_override', 15, 2)->nullable();
            $table->string('stock_status', 50)->default('in_stock');
            $table->integer('stock_qty')->default(0);
            $table->integer('supplier_stock_qty')->default(0);
            $table->integer('lead_time_days')->default(0);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_promo')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->timestamps();

            $table->foreign('main_category_id')->references('id')->on('categories')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('product_id');
            $table->primary(['category_id', 'product_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('path');
            $table->boolean('is_main')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('name')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->string('erp_id', 100)->nullable();
            $table->string('slug')->nullable();
            $table->decimal('list_price', 15, 2)->default(0);
            $table->decimal('price_override', 15, 2)->nullable();
            $table->integer('stock_qty')->default(0);
            $table->string('stock_status', 50)->default('in_stock');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attribute_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variant_id')->nullable();
            $table->string('value', 191)->nullable();
            $table->timestamps();

            $table->foreign('attribute_id')->references('id')->on('attributes')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
        });

        Schema::create('related_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('related_product_id');
            $table->enum('type', ['similar', 'complementary'])->default('similar');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('related_product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('related_products');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('products');
    }
};
