<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->enum('type', ['billing', 'shipping', 'office'])->default('shipping');
            $table->string('label')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('country', 100)->default('Romania');
            $table->string('county', 100)->nullable();
            $table->string('city', 100);
            $table->string('street', 191);
            $table->string('postal_code', 20)->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('is_default_billing')->default(false);
            $table->boolean('is_default_shipping')->default(false);
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
