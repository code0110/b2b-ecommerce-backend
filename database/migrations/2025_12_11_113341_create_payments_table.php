<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id')->nullable(); // agent / client
            $table->enum('type', ['chs', 'bo', 'cec', 'card', 'op']);
            $table->string('channel', 50)->nullable(); // offline/online
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('RON');
            $table->string('status', 50)->default('confirmed'); // pending/confirmed/rejected
            $table->timestamp('payment_date')->nullable();
            $table->string('document_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('recorded_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
