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
    Schema::table('customers', function (Blueprint $table) {
        $table->unsignedBigInteger('agent_user_id')->nullable()->after('group_id');
        $table->unsignedBigInteger('sales_director_user_id')->nullable()->after('agent_user_id');

        $table->foreign('agent_user_id')->references('id')->on('users')->nullOnDelete();
        $table->foreign('sales_director_user_id')->references('id')->on('users')->nullOnDelete();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            //
        });
    }
};
