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
        Schema::table('sales_order_items', function (Blueprint $table) {
            // Add farm_id for scoping (will be derived from sales_order in model boot)
            if (!Schema::hasColumn('sales_order_items', 'farm_id')) {
                $table->foreignId('farm_id')->nullable()->constrained('farms')->onDelete('cascade')->after('id');
            }
            
            // Add product_id (if products table exists)
            if (!Schema::hasColumn('sales_order_items', 'product_id')) {
                $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null')->after('sales_order_id');
            }
            
            // Add production cycle and harvest record links
            if (!Schema::hasColumn('sales_order_items', 'production_cycle_id')) {
                $table->foreignId('production_cycle_id')->nullable()->constrained('greenhouse_production_cycles')->onDelete('set null')->after('product_id');
            }
            if (!Schema::hasColumn('sales_order_items', 'harvest_record_id')) {
                // Link to production_cycle_harvest_records (new system) or bell_pepper_harvests (legacy)
                // We'll use a nullable foreign key - can link to either table
                // Note: In practice, we may need to check which table the ID belongs to
                $table->unsignedBigInteger('harvest_record_id')->nullable()->after('production_cycle_id');
                // Index will be added below with other indexes to avoid duplication
            }
            
            // Rename product_description to product_name if product_id is used
            // Keep both for flexibility
            if (!Schema::hasColumn('sales_order_items', 'product_name')) {
                $table->string('product_name')->nullable()->after('product_id');
            }
            
            // Enhance quantity and price precision
            if (Schema::hasColumn('sales_order_items', 'quantity')) {
                $table->decimal('quantity', 14, 2)->change();
            }
            if (Schema::hasColumn('sales_order_items', 'unit_price')) {
                $table->decimal('unit_price', 14, 2)->change();
            }
            
            // Add discount and line_total
            if (!Schema::hasColumn('sales_order_items', 'discount_amount')) {
                $table->decimal('discount_amount', 14, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('sales_order_items', 'line_total')) {
                $table->decimal('line_total', 14, 2)->default(0)->after('discount_amount');
            }
            
            // Add quality_grade
            if (!Schema::hasColumn('sales_order_items', 'quality_grade')) {
                $table->string('quality_grade')->nullable()->after('line_total');
            }
            
            // Add indexes (only if columns exist and indexes don't already exist)
            // For SQLite, check if index exists using raw query
            $driver = DB::getDriverName();
            
            if (Schema::hasColumn('sales_order_items', 'farm_id')) {
                $indexExists = false;
                if ($driver === 'sqlite') {
                    $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name='sales_order_items_farm_id_index'");
                    $indexExists = !empty($indexes);
                }
                if (!$indexExists) {
                    try {
                        $table->index('farm_id');
                    } catch (\Exception $e) {
                        // Index might already exist, continue
                    }
                }
            }
            
            if (Schema::hasColumn('sales_order_items', 'production_cycle_id')) {
                $indexExists = false;
                if ($driver === 'sqlite') {
                    $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name='sales_order_items_production_cycle_id_index'");
                    $indexExists = !empty($indexes);
                }
                if (!$indexExists) {
                    try {
                        $table->index('production_cycle_id');
                    } catch (\Exception $e) {
                        // Index might already exist, continue
                    }
                }
            }
            
            if (Schema::hasColumn('sales_order_items', 'harvest_record_id')) {
                $indexExists = false;
                if ($driver === 'sqlite') {
                    $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name='sales_order_items_harvest_record_id_index'");
                    $indexExists = !empty($indexes);
                }
                if (!$indexExists) {
                    try {
                        $table->index('harvest_record_id');
                    } catch (\Exception $e) {
                        // Index might already exist, continue
                    }
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropForeign(['farm_id']);
            $table->dropForeign(['product_id']);
            $table->dropForeign(['production_cycle_id']);
            $table->dropForeign(['harvest_record_id']);
            $table->dropColumn([
                'farm_id',
                'product_id',
                'production_cycle_id',
                'harvest_record_id',
                'product_name',
                'discount_amount',
                'line_total',
                'quality_grade',
            ]);
        });
    }
};
