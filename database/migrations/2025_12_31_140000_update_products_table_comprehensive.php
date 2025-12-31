<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update Products Table
        Schema::table('products', function (Blueprint $table) {
            // Identity
            if (!Schema::hasColumn('products', 'type')) {
                $table->enum('type', ['simple', 'variant'])->default('simple')->after('slug');
            }
            if (!Schema::hasColumn('products', 'visibility')) {
                $table->enum('visibility', ['public', 'logged_in', 'b2b'])->default('public')->after('status');
            }

            // Classification
            if (!Schema::hasColumn('products', 'tags')) {
                $table->json('tags')->nullable()->after('brand_id'); // For: nou, promotie, best seller, recomandat, exclusiv
            }

            // Content
            if (!Schema::hasColumn('products', 'key_benefits')) {
                $table->json('key_benefits')->nullable()->after('long_description');
            }
            if (!Schema::hasColumn('products', 'technical_specs')) {
                $table->json('technical_specs')->nullable()->after('key_benefits');
            }
            if (!Schema::hasColumn('products', 'meta_title')) {
                $table->string('meta_title')->nullable();
            }
            if (!Schema::hasColumn('products', 'meta_description')) {
                $table->text('meta_description')->nullable();
            }
            if (!Schema::hasColumn('products', 'meta_keywords')) {
                $table->string('meta_keywords')->nullable();
            }

            // Media
            if (!Schema::hasColumn('products', 'video_url')) {
                $table->string('video_url')->nullable();
            }

            // Pricing & Fiscality
            if (!Schema::hasColumn('products', 'vat_included')) {
                $table->boolean('vat_included')->default(false)->after('vat_rate');
            }
            if (!Schema::hasColumn('products', 'currency')) {
                $table->string('currency', 3)->default('RON')->after('price_override');
            }

            // Stock & Availability
            if (!Schema::hasColumn('products', 'min_stock_limit')) {
                $table->integer('min_stock_limit')->default(0)->after('stock_qty');
            }
            if (!Schema::hasColumn('products', 'allow_backorder')) {
                $table->boolean('allow_backorder')->default(false);
            }
            if (!Schema::hasColumn('products', 'overstock_policy')) {
                $table->string('overstock_policy')->default('block')->comment('block, warning');
            }
            if (!Schema::hasColumn('products', 'estimated_delivery_text')) {
                $table->string('estimated_delivery_text')->nullable();
            }

            // UOM
            if (!Schema::hasColumn('products', 'unit_of_measure')) {
                $table->string('unit_of_measure')->default('buc');
            }

            // Restrictions
            if (!Schema::hasColumn('products', 'min_order_quantity')) {
                $table->integer('min_order_quantity')->default(1);
            }
            if (!Schema::hasColumn('products', 'order_quantity_step')) {
                $table->integer('order_quantity_step')->default(1);
            }
            if (!Schema::hasColumn('products', 'requires_quote')) {
                $table->boolean('requires_quote')->default(false);
            }

            // Audit
            if (!Schema::hasColumn('products', 'erp_sync_status')) {
                $table->enum('erp_sync_status', ['synced', 'pending', 'error'])->default('pending');
            }
            if (!Schema::hasColumn('products', 'erp_last_sync_at')) {
                $table->timestamp('erp_last_sync_at')->nullable();
            }
        });

        // 2. Create Product Units Table
        if (!Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->string('name'); // box, pallet
                $table->decimal('conversion_factor', 10, 4); // 1 box = 12 pcs
                $table->boolean('is_default')->default(false);
                $table->string('price_calculation_mode')->default('per_unit'); // per_unit (calculated), fixed (specific price)
                $table->decimal('specific_price', 15, 2)->nullable();
                $table->timestamps();
            });
        }

        // 3. Update Attributes Table
        Schema::table('attributes', function (Blueprint $table) {
            if (!Schema::hasColumn('attributes', 'is_comparable')) {
                $table->boolean('is_comparable')->default(false);
            }
        });

        // 4. Update Product Documents Table
        Schema::table('product_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('product_documents', 'name')) {
                $table->string('name')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('product_documents', 'version')) {
                $table->string('version')->nullable();
            }
            if (!Schema::hasColumn('product_documents', 'language')) {
                $table->string('language', 5)->default('ro');
            }
        });

        // 5. Update Related Products Type
        // Note: SQLite/MySQL enum modification is complex. 
        // We will just allow the application to handle 'upsell' string, assuming standard DBs might not strict check ENUMs if not set strictly, 
        // or we simply accept that for now we might need to recreate column if it was strict.
        // But for safety in Laravel migrations:
        // If driver is sqlite, enums are text. If mysql, they are actual enums.
        
        /* 
        Schema::table('related_products', function (Blueprint $table) {
             // Modifying ENUMs is driver dependent and tricky in migrations. 
             // We'll skip forcing database level enum change for 'upsell' to avoid breakage 
             // and handle it in application logic / validation.
             // If this was a fresh project we'd just change the original migration.
        });
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
        
        Schema::table('products', function (Blueprint $table) {
            $columns = [
                'type', 'visibility', 'tags', 'key_benefits', 'technical_specs',
                'meta_title', 'meta_description', 'meta_keywords', 'video_url',
                'vat_included', 'currency', 'min_stock_limit', 'allow_backorder',
                'overstock_policy', 'estimated_delivery_text', 'unit_of_measure',
                'min_order_quantity', 'order_quantity_step', 'requires_quote',
                'erp_sync_status', 'erp_last_sync_at'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('attributes', function (Blueprint $table) {
            if (Schema::hasColumn('attributes', 'is_comparable')) {
                $table->dropColumn('is_comparable');
            }
        });

        Schema::table('product_documents', function (Blueprint $table) {
             if (Schema::hasColumn('product_documents', 'name')) $table->dropColumn('name');
             if (Schema::hasColumn('product_documents', 'version')) $table->dropColumn('version');
             if (Schema::hasColumn('product_documents', 'language')) $table->dropColumn('language');
        });
    }
};
