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
        // 1. Update farms: location_id -> site_id
        if (Schema::hasColumn('farms', 'location_id')) {
            Schema::table('farms', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('location_id')->constrained('sites')->onDelete('set null');
            });
            
            // Migrate data: find sites by location code/name and update farms
            $farms = DB::table('farms')->whereNotNull('location_id')->get();
            foreach ($farms as $farm) {
                $location = DB::table('locations')->find($farm->location_id);
                if ($location) {
                    $site = DB::table('sites')
                        ->where('code', $location->code)
                        ->orWhere('name', $location->name)
                        ->first();
                    if ($site) {
                        DB::table('farms')->where('id', $farm->id)->update(['site_id' => $site->id]);
                    }
                }
            }
            
            Schema::table('farms', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // 2. Update greenhouses: location_id -> site_id (if not already using site_id)
        if (Schema::hasColumn('greenhouses', 'location_id')) {
            // Migrate data first
            $greenhouses = DB::table('greenhouses')->whereNotNull('location_id')->get();
            foreach ($greenhouses as $greenhouse) {
                $location = DB::table('locations')->find($greenhouse->location_id);
                if ($location) {
                    $site = DB::table('sites')
                        ->where('code', $location->code)
                        ->orWhere('name', $location->name)
                        ->first();
                    if ($site && !$greenhouse->site_id) {
                        DB::table('greenhouses')->where('id', $greenhouse->id)->update(['site_id' => $site->id]);
                    }
                }
            }
            
            Schema::table('greenhouses', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // 3. Update boreholes: location_id -> site_id
        if (Schema::hasColumn('boreholes', 'location_id')) {
            Schema::table('boreholes', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('location_id')->constrained('sites')->onDelete('set null');
            });
            
            // Migrate data
            $boreholes = DB::table('boreholes')->whereNotNull('location_id')->get();
            foreach ($boreholes as $borehole) {
                $location = DB::table('locations')->find($borehole->location_id);
                if ($location) {
                    $site = DB::table('sites')
                        ->where('code', $location->code)
                        ->orWhere('name', $location->name)
                        ->first();
                    if ($site) {
                        DB::table('boreholes')->where('id', $borehole->id)->update(['site_id' => $site->id]);
                    }
                }
            }
            
            Schema::table('boreholes', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // 4. Update admin_zones: location_id -> site_id
        if (Schema::hasColumn('admin_zones', 'location_id')) {
            Schema::table('admin_zones', function (Blueprint $table) {
                $table->foreignId('site_id')->nullable()->after('location_id')->constrained('sites')->onDelete('set null');
            });
            
            // Migrate data
            $adminZones = DB::table('admin_zones')->whereNotNull('location_id')->get();
            foreach ($adminZones as $adminZone) {
                $location = DB::table('locations')->find($adminZone->location_id);
                if ($location) {
                    $site = DB::table('sites')
                        ->where('code', $location->code)
                        ->orWhere('name', $location->name)
                        ->first();
                    if ($site) {
                        DB::table('admin_zones')->where('id', $adminZone->id)->update(['site_id' => $site->id]);
                    }
                }
            }
            
            Schema::table('admin_zones', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // 5. Remove location_id from sites (sites are now locations themselves)
        if (Schema::hasColumn('sites', 'location_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }

        // 6. Drop the locations table (data has been migrated to sites)
        if (Schema::hasTable('locations')) {
            Schema::dropIfExists('locations');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible
        // Would need to recreate location_id columns and restore data
    }
};
