<?php

namespace Database\Seeders;

use App\Models\SiteType;
use Illuminate\Database\Seeder;

class SiteTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siteTypes = [
            [
                'code' => 'farmland',
                'name' => 'Farmland',
                'code_prefix' => 'FL',
                'description' => 'Agricultural land for farming activities',
                'is_active' => true,
            ],
            [
                'code' => 'warehouse',
                'name' => 'Warehouse',
                'code_prefix' => 'WH',
                'description' => 'Storage facility for inventory and products',
                'is_active' => true,
            ],
            [
                'code' => 'factory',
                'name' => 'Factory',
                'code_prefix' => 'FT',
                'description' => 'Manufacturing or processing facility',
                'is_active' => true,
            ],
            [
                'code' => 'greenhouse',
                'name' => 'Greenhouse',
                'code_prefix' => 'GH',
                'description' => 'Controlled environment growing facility',
                'is_active' => true,
            ],
            [
                'code' => 'estate',
                'name' => 'Estate',
                'code_prefix' => 'EST',
                'description' => 'Large agricultural estate or plantation',
                'is_active' => true,
            ],
        ];

        foreach ($siteTypes as $siteTypeData) {
            SiteType::updateOrCreate(
                ['code' => $siteTypeData['code']],
                $siteTypeData
            );
        }
        
        $count = SiteType::count();
        $this->command->info("Site types seeded successfully! Created/updated {$count} site types.");
    }
}
