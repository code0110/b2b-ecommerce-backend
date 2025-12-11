<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('approval_status', 50)->default('none')->after('status'); // none, pending, approved, rejected
            $table->unsignedBigInteger('approved_by_user_id')->nullable()->after('approval_status');
            $table->timestamp('approved_at')->nullable()->after('approved_by_user_id');

            $table->foreign('approved_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['approved_by_user_id']);
            $table->dropColumn(['approval_status', 'approved_by_user_id', 'approved_at']);
        });
    }
};
