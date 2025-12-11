<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['b2c', 'b2b']);
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('cif', 50)->nullable();
            $table->string('reg_com', 50)->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->integer('payment_terms_days')->default(0);
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->string('currency', 10)->default('RON');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_partner')->default(false);
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('customer_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
