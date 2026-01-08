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
        Schema::table('boreholes', function (Blueprint $table) {
            // Ensure site_id exists
            if (!Schema::hasColumn('boreholes', 'site_id')) {
                $table->foreignId('site_id')->nullable()->after('farm_id')->constrained('sites')->onDelete('cascade');
                $table->index('site_id');
            }
            // Add borehole_code and status
            if (!Schema::hasColumn('boreholes', 'borehole_code')) {
                $table->string('borehole_code')->nullable()->after('site_id');
            }
            if (!Schema::hasColumn('boreholes', 'status')) {
                // Use string instead of enum for SQLite compatibility
                $table->string('status')->default('ACTIVE')->after('borehole_code');
            }
            // Location fields
            if (!Schema::hasColumn('boreholes', 'gps_lat')) {
                $table->decimal('gps_lat', 10, 7)->nullable()->after('status');
            }
            if (!Schema::hasColumn('boreholes', 'gps_lng')) {
                $table->decimal('gps_lng', 10, 7)->nullable()->after('gps_lat');
            }
            if (!Schema::hasColumn('boreholes', 'location_description')) {
                $table->string('location_description')->nullable()->after('gps_lng');
            }
            // Technical specs
            foreach ([
                ['depth_m', 'decimal', [10,2]],
                ['static_water_level_m', 'decimal', [10,2]],
                ['yield_m3_per_hr', 'decimal', [10,2]],
            ] as [$col, $type, $args]) {
                if (!Schema::hasColumn('boreholes', $col)) {
                    $table->decimal($col, $args[0], $args[1])->nullable()->after('location_description');
                }
            }
            if (!Schema::hasColumn('boreholes', 'casing_diameter_mm')) {
                $table->integer('casing_diameter_mm')->nullable()->after('yield_m3_per_hr');
            }
            if (!Schema::hasColumn('boreholes', 'drilling_date')) {
                $table->date('drilling_date')->nullable()->after('casing_diameter_mm');
            }
            if (!Schema::hasColumn('boreholes', 'drilling_contractor')) {
                $table->string('drilling_contractor')->nullable()->after('drilling_date');
            }
            if (!Schema::hasColumn('boreholes', 'borehole_type')) {
                // Use string instead of enum for SQLite compatibility
                $table->string('borehole_type')->nullable()->after('drilling_contractor');
            }
            // Monitoring / compliance
            if (!Schema::hasColumn('boreholes', 'is_metered')) {
                $table->boolean('is_metered')->default(false)->after('borehole_type');
            }
            if (!Schema::hasColumn('boreholes', 'meter_reference')) {
                $table->string('meter_reference')->nullable()->after('is_metered');
            }
            if (!Schema::hasColumn('boreholes', 'next_water_test_due_at')) {
                $table->date('next_water_test_due_at')->nullable()->after('meter_reference');
            }
            if (!Schema::hasColumn('boreholes', 'water_quality_notes')) {
                $table->text('water_quality_notes')->nullable()->after('next_water_test_due_at');
            }
            // Asset links
            foreach (['asset_id','pump_asset_id','power_asset_id','storage_tank_asset_id'] as $assetFk) {
                if (!Schema::hasColumn('boreholes', $assetFk)) {
                    $table->foreignId($assetFk)->nullable()->after('water_quality_notes')->constrained('assets')->onDelete('set null');
                }
            }
            // created_by
            if (!Schema::hasColumn('boreholes', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->onDelete('set null');
            }
            // Unique constraint on (site_id, borehole_code)
            try {
                $table->unique(['site_id', 'borehole_code'], 'boreholes_site_code_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            // Index status
            try {
                $table->index('status');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boreholes', function (Blueprint $table) {
            // Drop unique index
            try {
                $table->dropUnique('boreholes_site_code_unique');
            } catch (\Throwable $e) {
                // ignore
            }
            // Columns could optionally be dropped here; keeping to avoid data loss on rollback
        });
    }
};
