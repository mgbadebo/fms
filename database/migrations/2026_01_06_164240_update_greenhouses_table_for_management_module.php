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
        Schema::table('greenhouses', function (Blueprint $table) {
            // Rename code to greenhouse_code for clarity, or add greenhouse_code if code should remain
            // We'll keep both: code (existing) and greenhouse_code (new, preferred)
            if (!Schema::hasColumn('greenhouses', 'greenhouse_code')) {
                $table->string('greenhouse_code')->nullable()->after('code');
            }
            
            // Add type enum
            if (!Schema::hasColumn('greenhouses', 'type')) {
                $table->enum('type', ['TUNNEL', 'GLASSHOUSE', 'POLYHOUSE', 'SHADE_HOUSE'])->nullable()->after('greenhouse_code');
            }
            
            // Add status enum
            if (!Schema::hasColumn('greenhouses', 'status')) {
                $table->enum('status', ['ACTIVE', 'INACTIVE', 'MAINTENANCE', 'DECOMMISSIONED'])->default('ACTIVE')->after('type');
            }
            
            // Add dimension fields
            if (!Schema::hasColumn('greenhouses', 'length')) {
                $table->decimal('length', 10, 2)->nullable()->after('size_sqm');
            }
            if (!Schema::hasColumn('greenhouses', 'width')) {
                $table->decimal('width', 10, 2)->nullable()->after('length');
            }
            if (!Schema::hasColumn('greenhouses', 'height')) {
                $table->decimal('height', 10, 2)->nullable()->after('width');
            }
            if (!Schema::hasColumn('greenhouses', 'total_area')) {
                $table->decimal('total_area', 12, 2)->nullable()->after('height');
            }
            
            // Add orientation enum
            if (!Schema::hasColumn('greenhouses', 'orientation')) {
                $table->enum('orientation', ['N_S', 'E_W', 'NE_SW', 'NW_SE'])->nullable()->after('total_area');
            }
            
            // Add plant capacity
            if (!Schema::hasColumn('greenhouses', 'plant_capacity')) {
                $table->integer('plant_capacity')->nullable()->after('orientation');
            }
            
            // Add operational config
            if (!Schema::hasColumn('greenhouses', 'primary_crop_type')) {
                $table->string('primary_crop_type', 100)->nullable()->after('plant_capacity');
            }
            if (!Schema::hasColumn('greenhouses', 'cropping_system')) {
                $table->enum('cropping_system', ['SOIL', 'COCOPEAT', 'HYDROPONIC'])->nullable()->after('primary_crop_type');
            }
            
            // Add created_by
            if (!Schema::hasColumn('greenhouses', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('farm_id')->constrained('users')->onDelete('set null');
            }
        });
        
        // Update uniqueness constraint: greenhouse_code should be unique per site
        // Drop existing unique constraint on code if it exists (try-catch for safety)
        try {
            Schema::table('greenhouses', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        } catch (\Exception $e) {
            // Constraint might not exist, continue
        }
        
        // Add unique constraint on (site_id, greenhouse_code)
        Schema::table('greenhouses', function (Blueprint $table) {
            $table->unique(['site_id', 'greenhouse_code'], 'greenhouses_site_code_unique');
        });
        
        // Ensure site_id is required (not nullable) for new records
        // But we'll handle this in validation, not at DB level to allow migration of existing data
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('greenhouses', function (Blueprint $table) {
            // Drop unique constraint
            try {
                $table->dropUnique('greenhouses_site_code_unique');
            } catch (\Exception $e) {
                // Constraint might not exist
            }
            
            // Restore original unique constraint on code
            try {
                $table->unique('code', 'greenhouses_code_unique');
            } catch (\Exception $e) {
                // Constraint might already exist
            }
            
            // Drop new columns
            $columns = [
                'greenhouse_code', 'type', 'status', 'length', 'width', 'height', 
                'total_area', 'orientation', 'plant_capacity', 'primary_crop_type', 
                'cropping_system', 'created_by'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('greenhouses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
