<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('courier', 50);
            $table->string('awb_number', 100)->nullable();
            $table->string('label_url')->nullable();
            $table->string('tracking_url')->nullable();
            $table->string('status', 50)->default('created'); // created, in_transit, delivered, cancelled
            $table->json('raw_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
            $table->index(['courier', 'awb_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
