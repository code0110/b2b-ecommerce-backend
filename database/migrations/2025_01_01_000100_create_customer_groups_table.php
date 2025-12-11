<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['b2b', 'b2c']);
            $table->decimal('default_discount_percent', 8, 2)->default(0);
            $table->integer('default_payment_terms_days')->default(0);
            $table->decimal('default_credit_limit', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
