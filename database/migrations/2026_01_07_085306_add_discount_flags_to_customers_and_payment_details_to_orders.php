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
            if (!Schema::hasColumn('customers', 'allow_global_discount')) {
                $table->boolean('allow_global_discount')->default(false)->after('credit_limit');
            }
            if (!Schema::hasColumn('customers', 'allow_line_discount')) {
                $table->boolean('allow_line_discount')->default(false)->after('allow_global_discount');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_document')) {
                $table->string('payment_document')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('orders', 'global_discount_percent')) {
                $table->decimal('global_discount_percent', 5, 2)->default(0)->after('subtotal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['allow_global_discount', 'allow_line_discount']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_document', 'global_discount_percent']);
        });
    }
};
