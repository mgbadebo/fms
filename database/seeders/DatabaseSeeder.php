<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Farm;
use App\Models\Season;
use App\Models\Crop;
use App\Models\Field;
use App\Models\ScaleDevice;
use App\Models\LabelTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create roles
        $roles = ['OWNER', 'MANAGER', 'WORKER', 'FINANCE', 'AUDITOR', 'ADMIN', 'HARVESTER'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@fms.test'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('ADMIN');

        // Create demo farm
        $farm = Farm::firstOrCreate(
            ['name' => 'Demo Farm'],
            [
                'location' => '123 Farm Road',
                'description' => 'Demo farm for testing',
                'total_area' => 100.0,
                'area_unit' => 'hectares',
                'is_active' => true,
            ]
        );

        // Attach admin to farm
        if (!$farm->users->contains($admin->id)) {
            $farm->users()->attach($admin->id, ['role' => 'OWNER']);
        }

        // Create demo season
        $season = Season::firstOrCreate(
            [
                'farm_id' => $farm->id,
                'name' => '2024 Season',
            ],
            [
                'start_date' => '2024-01-01',
                'end_date' => '2024-12-31',
                'status' => 'ACTIVE',
            ]
        );

        // Create demo crops
        $crops = [
            ['name' => 'Tomato', 'category' => 'VEGETABLE', 'default_maturity_days' => 90],
            ['name' => 'Corn', 'category' => 'GRAIN', 'default_maturity_days' => 120],
            ['name' => 'Wheat', 'category' => 'GRAIN', 'default_maturity_days' => 150],
        ];

        foreach ($crops as $cropData) {
            Crop::firstOrCreate(['name' => $cropData['name']], $cropData);
        }

        // Create demo field
        $field = Field::firstOrCreate(
            [
                'farm_id' => $farm->id,
                'name' => 'Field A',
            ],
            [
                'area' => 25.5,
                'area_unit' => 'hectares',
                'soil_type' => 'Loam',
            ]
        );

        // Create demo scale device
        $scaleDevice = ScaleDevice::firstOrCreate(
            [
                'farm_id' => $farm->id,
                'name' => 'Main Weighing Scale',
            ],
            [
                'connection_type' => 'MOCK',
                'connection_config' => ['unit' => 'kg'],
                'is_active' => true,
            ]
        );

        // Create demo label template
        $labelTemplate = LabelTemplate::firstOrCreate(
            [
                'farm_id' => $farm->id,
                'code' => 'HARVEST_LOT_LABEL',
            ],
            [
                'name' => 'Harvest Lot Label',
                'target_type' => 'HARVEST_LOT',
                'template_engine' => 'RAW',
                'template_body' => "Harvest Lot: {{code}}\nWeight: {{net_weight}} {{weight_unit}}\nTraceability ID: {{traceability_id}}\nField: {{field_name}}\nHarvested: {{harvested_at}}",
                'is_default' => true,
            ]
        );

        $this->command->info('Database seeded successfully!');
        $this->command->info('Admin user: admin@fms.test / password');
    }
}
