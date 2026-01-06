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
        Schema::table('assets', function (Blueprint $table) {
            // Drop old category enum column if it exists
            if (Schema::hasColumn('assets', 'category')) {
                $table->dropColumn('category');
            }

            // Add new asset_category_id foreign key
            $table->foreignId('asset_category_id')->nullable()->after('farm_id')->constrained('asset_categories')->onDelete('set null');
            
            // Add asset_code (unique per farm)
            $table->string('asset_code')->nullable()->after('asset_category_id');
            
            // Add description
            $table->text('description')->nullable()->after('name');
            
            // Update status enum to match requirements
            // Note: We'll need to handle existing data migration separately if needed
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'UNDER_REPAIR', 'DISPOSED', 'SOLD', 'LOST'])->default('ACTIVE')->change();
            
            // Add acquisition_type
            $table->enum('acquisition_type', ['PURCHASED', 'LEASED', 'RENTED', 'DONATED'])->nullable()->after('status');
            
            // Add purchase details
            $table->date('purchase_date')->nullable()->change();
            $table->decimal('purchase_cost', 14, 2)->nullable()->after('purchase_date');
            $table->char('currency', 3)->default('NGN')->after('purchase_cost');
            $table->string('supplier_name')->nullable()->after('currency');
            
            // Add equipment details
            $table->string('serial_number')->nullable()->change();
            $table->string('model')->nullable()->after('serial_number');
            $table->string('manufacturer')->nullable()->after('model');
            $table->smallInteger('year_of_make')->nullable()->after('manufacturer');
            $table->date('warranty_expiry')->nullable()->after('year_of_make');
            
            // Add location fields
            $table->text('location_text')->nullable()->after('warranty_expiry');
            $table->foreignId('location_field_id')->nullable()->after('location_text')->constrained('fields')->onDelete('set null');
            $table->foreignId('location_zone_id')->nullable()->after('location_field_id')->constrained('zones')->onDelete('set null');
            $table->decimal('gps_lat', 10, 7)->nullable()->after('location_zone_id');
            $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            
            // Add tracking flag
            $table->boolean('is_trackable')->default(true)->after('gps_lng');
            
            // Add created_by user reference
            $table->foreignId('created_by')->nullable()->after('is_trackable')->constrained('users')->onDelete('set null');
            
            // Update metadata to be nullable if not already
            $table->json('metadata')->nullable()->change();
        });

        // Add indexes
        Schema::table('assets', function (Blueprint $table) {
            $table->unique(['farm_id', 'asset_code']);
            $table->index(['farm_id', 'status']);
            $table->index(['farm_id', 'asset_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Drop indexes first
            $table->dropUnique(['farm_id', 'asset_code']);
            $table->dropIndex(['farm_id', 'status']);
            $table->dropIndex(['farm_id', 'asset_category_id']);
            
            // Drop foreign keys
            $table->dropForeign(['asset_category_id']);
            $table->dropForeign(['location_field_id']);
            $table->dropForeign(['location_zone_id']);
            $table->dropForeign(['created_by']);
            
            // Drop columns
            $table->dropColumn([
                'asset_category_id',
                'asset_code',
                'description',
                'acquisition_type',
                'purchase_cost',
                'currency',
                'supplier_name',
                'model',
                'manufacturer',
                'year_of_make',
                'warranty_expiry',
                'location_text',
                'location_field_id',
                'location_zone_id',
                'gps_lat',
                'gps_lng',
                'is_trackable',
                'created_by',
            ]);
            
            // Restore old category enum
            $table->enum('category', ['TRACTOR', 'VEHICLE', 'IMPLEMENT', 'PUMP', 'BUILDING', 'OTHER'])->default('OTHER')->after('name');
        });
    }
};
