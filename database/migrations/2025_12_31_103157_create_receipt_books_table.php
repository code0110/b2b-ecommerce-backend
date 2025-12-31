<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('receipt_books', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Agent or Director assigned to this book');
            $table->string('series', 10);
            $table->integer('start_number');
            $table->integer('end_number');
            $table->integer('current_number');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            // Ensure unique series per user if needed, or just unique series globally? 
            // Usually series + numbers are unique. 
            // Let's assume series are unique globally for simplicity or user assignment constraint.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_books');
    }
};
