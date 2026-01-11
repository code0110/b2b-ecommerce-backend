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
        // 1. Îmbunătățirea tabelei `product_units` pentru UOM avansat
        Schema::table('product_units', function (Blueprint $table) {
            // Adăugăm suport pentru variante
            if (!Schema::hasColumn('product_units', 'product_variant_id')) {
                $table->unsignedBigInteger('product_variant_id')->nullable()->after('product_id');
                $table->foreign('product_variant_id')->references('id')->on('product_variants')->cascadeOnDelete();
            }

            // Flag pentru unitatea de bază (cea din ERP)
            if (!Schema::hasColumn('product_units', 'is_base')) {
                $table->boolean('is_base')->default(false)->after('conversion_factor');
            }
            
            // Unitatea de măsură afișată (ex: buc, cutie, bax)
            if (!Schema::hasColumn('product_units', 'unit')) {
                $table->string('unit')->nullable()->after('name'); 
            }
            
            // Renunțăm la 'is_default' în favoarea logicii 'is_base' + selecție user
            // Dar îl păstrăm pentru backward compatibility sau default UI selection
        });

        // 2. Îmbunătățirea tabelei `product_variants` pentru a se comporta ca produse
        Schema::table('product_variants', function (Blueprint $table) {
            // Asigurăm unicitatea slug-ului (dacă nu e deja setat corect în DB)
            // Notă: `slug` există deja, dar trebuie să fim siguri că e indexat pentru performanță
            // $table->string('slug')->unique()->change(); // Atenție la duplicate existente
        });

        // 3. Adăugare coloane de ambalare în `products` și `product_variants` (opțional, pentru info rapid)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'packaging_unit')) {
                $table->string('packaging_unit')->nullable(); // ex: cutie
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_units', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
            $table->dropColumn('is_base');
            $table->dropColumn('unit');
        });
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('packaging_unit');
        });
    }
};
