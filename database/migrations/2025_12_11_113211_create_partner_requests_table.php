<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_requests', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('cif', 50)->nullable();
            $table->string('reg_com', 50)->nullable();
            $table->string('iban', 50)->nullable();
            $table->string('contact_name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('activity_type', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('new'); // new, in_review, approved, rejected
            $table->unsignedBigInteger('assigned_agent_id')->nullable();
            $table->timestamps();

            $table->foreign('assigned_agent_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_requests');
    }
};
