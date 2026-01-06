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
        Schema::create('content_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('group')->index(); // e.g., 'footer', 'homepage', 'contact'
            $table->string('type')->default('text'); // 'text', 'html', 'json', 'image'
            $table->longText('content')->nullable();
            $table->string('title')->nullable(); // Human readable title for admin
            $table->json('meta')->nullable(); // Extra configuration
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_blocks');
    }
};
