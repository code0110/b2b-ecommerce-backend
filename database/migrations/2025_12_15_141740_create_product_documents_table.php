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
    Schema::create('product_documents', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')
            ->constrained()
            ->onDelete('cascade');

        $table->string('path');
        $table->string('type')->nullable();        // spec_sheet, manual, certificat etc.
        $table->string('visibility')->default('public'); // public / customers_only / by_request

        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_documents');
    }
};
