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
        // Get the first farm (or create a default one if none exists)
        $defaultFarm = DB::table('farms')->first();
        
        if (!$defaultFarm) {
            // Create a default farm if none exists
            $defaultFarmId = DB::table('farms')->insertGetId([
                'name' => 'Default Farm',
                'description' => 'Default farm for migrated locations',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultFarmId = $defaultFarm->id;
        }

        // Migrate all locations to sites
        $locations = DB::table('locations')->whereNull('deleted_at')->get();
        
        foreach ($locations as $location) {
            // Check if a site with this code already exists
            $existingSite = DB::table('sites')
                ->where('code', $location->code)
                ->whereNull('deleted_at')
                ->first();
            
            if (!$existingSite) {
                DB::table('sites')->insert([
                    'farm_id' => $defaultFarmId,
                    'name' => $location->name,
                    'code' => $location->code,
                    'type' => 'warehouse', // Default type for migrated locations
                    'description' => $location->description,
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'notes' => $location->notes,
                    'is_active' => $location->is_active ?? true,
                    'created_at' => $location->created_at ?? now(),
                    'updated_at' => $location->updated_at ?? now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not easily reversible
        // Data would need to be manually restored if needed
    }
};
