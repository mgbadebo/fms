<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkerJobRole;
use App\Models\Farm;

class WorkerJobRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['code' => 'FARM_MGR', 'name' => 'Farm Manager', 'description' => 'Oversees overall farm operations and management'],
            ['code' => 'SITE_SUP', 'name' => 'Site Supervisor', 'description' => 'Supervises daily operations at a specific site'],
            ['code' => 'FLD_WRK', 'name' => 'Field Worker', 'description' => 'Performs field work including planting, weeding, and harvesting'],
            ['code' => 'TRAC_OP', 'name' => 'Tractor Operator', 'description' => 'Operates tractors and other farm machinery'],
            ['code' => 'IRR_TECH', 'name' => 'Irrigation Technician', 'description' => 'Maintains and operates irrigation systems'],
            ['code' => 'STORE_KPR', 'name' => 'Storekeeper', 'description' => 'Manages inventory and storage facilities'],
            ['code' => 'SECURITY', 'name' => 'Security', 'description' => 'Provides security services for the farm'],
            ['code' => 'DRIVER', 'name' => 'Driver', 'description' => 'Drives vehicles for transportation of goods and personnel'],
            ['code' => 'CLEANER', 'name' => 'Cleaner', 'description' => 'Maintains cleanliness of facilities and equipment'],
            ['code' => 'MECHANIC', 'name' => 'Mechanic', 'description' => 'Repairs and maintains farm machinery and equipment'],
            ['code' => 'ACC_ADMIN', 'name' => 'Accountant / Admin', 'description' => 'Handles accounting and administrative tasks'],
            ['code' => 'VET_HAND', 'name' => 'Vet / Animal Handler', 'description' => 'Provides veterinary care and handles livestock'],
            ['code' => 'PKH_STF', 'name' => 'Packhouse Staff', 'description' => 'Works in packhouse for sorting, grading, and packaging produce'],
        ];

        // Job roles are now global, not farm-specific
        foreach ($roles as $role) {
            WorkerJobRole::firstOrCreate(
                [
                    'code' => $role['code'],
                ],
                [
                    'code' => $role['code'],
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Worker job roles seeded successfully (' . count($roles) . ' roles).');
    }
}

