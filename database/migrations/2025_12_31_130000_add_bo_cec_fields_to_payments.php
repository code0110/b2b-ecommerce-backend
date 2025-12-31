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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('bank', 100)->nullable()->after('currency');
            $table->date('due_date')->nullable()->after('payment_date');
            $table->string('series', 20)->nullable()->after('document_number');
            $table->string('number', 50)->nullable()->after('series');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['bank', 'due_date', 'series', 'number']);
        });
    }
};
