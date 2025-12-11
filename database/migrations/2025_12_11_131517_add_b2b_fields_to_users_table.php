<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // presupunem că ai deja customer_id
            $table->string('company_role', 50)->nullable()->after('remember_token'); // owner, approver, buyer
            $table->boolean('requires_approval')->default(false)->after('company_role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['company_role', 'requires_approval']);
        });
    }
};
