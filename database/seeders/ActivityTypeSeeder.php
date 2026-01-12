<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityType;
use App\Models\Farm;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all farms or create default if none exist
        $farms = Farm::all();
        
        if ($farms->isEmpty()) {
            $this->command->warn('No farms found. Activity types will not be seeded.');
            return;
        }
        
        $commonTypes = [
            [
                'code' => 'IRRIGATION',
                'name' => 'Irrigation',
                'description' => 'Watering activities',
                'category' => 'WATER',
                'requires_quantity' => true,
                'requires_time_range' => true,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
            [
                'code' => 'FERTIGATION',
                'name' => 'Fertigation',
                'description' => 'Fertilizer application through irrigation',
                'category' => 'NUTRITION',
                'requires_quantity' => true,
                'requires_time_range' => false,
                'requires_inputs' => true,
                'requires_photos' => false,
            ],
            [
                'code' => 'PRUNING',
                'name' => 'Pruning',
                'description' => 'Plant pruning activities',
                'category' => 'CULTURAL',
                'requires_quantity' => false,
                'requires_time_range' => true,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
            [
                'code' => 'TRELLISING',
                'name' => 'Trellising',
                'description' => 'Trellis setup and maintenance',
                'category' => 'CULTURAL',
                'requires_quantity' => false,
                'requires_time_range' => true,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
            [
                'code' => 'DELEAFING',
                'name' => 'Deleafing',
                'description' => 'Removal of old or diseased leaves',
                'category' => 'CULTURAL',
                'requires_quantity' => false,
                'requires_time_range' => true,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
            [
                'code' => 'SCOUTING',
                'name' => 'Scouting',
                'description' => 'Pest and disease monitoring',
                'category' => 'PROTECTION',
                'requires_quantity' => false,
                'requires_time_range' => false,
                'requires_inputs' => false,
                'requires_photos' => true,
            ],
            [
                'code' => 'SPRAYING',
                'name' => 'Spraying',
                'description' => 'Pesticide/fungicide application',
                'category' => 'PROTECTION',
                'requires_quantity' => false,
                'requires_time_range' => true,
                'requires_inputs' => true,
                'requires_photos' => false,
            ],
            [
                'code' => 'POLLINATION_SUPPORT',
                'name' => 'Pollination Support',
                'description' => 'Manual pollination or pollinator support',
                'category' => 'CULTURAL',
                'requires_quantity' => false,
                'requires_time_range' => false,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
            [
                'code' => 'CLEANING_SANITATION',
                'name' => 'Cleaning & Sanitation',
                'description' => 'Greenhouse cleaning and sanitization',
                'category' => 'HYGIENE',
                'requires_quantity' => false,
                'requires_time_range' => false,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
            [
                'code' => 'EQUIPMENT_CHECK',
                'name' => 'Equipment Check',
                'description' => 'Equipment inspection and maintenance',
                'category' => 'MAINTENANCE',
                'requires_quantity' => false,
                'requires_time_range' => false,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
            [
                'code' => 'OTHER',
                'name' => 'Other',
                'description' => 'Other activities',
                'category' => null,
                'requires_quantity' => false,
                'requires_time_range' => false,
                'requires_inputs' => false,
                'requires_photos' => false,
            ],
        ];
        
        foreach ($farms as $farm) {
            foreach ($commonTypes as $typeData) {
                ActivityType::updateOrCreate(
                    [
                        'farm_id' => $farm->id,
                        'code' => $typeData['code'],
                    ],
                    array_merge($typeData, [
                        'farm_id' => $farm->id,
                        'is_active' => true,
                    ])
                );
            }
        }
        
        $this->command->info('Activity types seeded successfully for ' . $farms->count() . ' farm(s).');
    }
}
