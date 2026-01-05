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
        Schema::create('offer_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // Sender (Agent, Director, or Client via User?) 
            // NOTE: Client might not be in 'users' table if it's a separate auth guard, but typically B2B clients are users.
            // If Client is separate, we might need polymorphic relation or just user_id if they are all Users.
            // Assuming B2B 'customers' table has linked 'users' or clients log in as users.
            // Checking: 'customers' table exists. Usually clients have a user account.
            $table->text('message');
            $table->boolean('is_internal')->default(false); // If true, only visible to Agent/Director
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offer_messages');
    }
};
