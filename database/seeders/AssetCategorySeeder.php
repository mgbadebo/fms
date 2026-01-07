<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetCategory;

class AssetCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['code' => 'LAND', 'name' => 'Land'],
            ['code' => 'BUILDING', 'name' => 'Building'],
            ['code' => 'TRACTOR', 'name' => 'Tractor'],
            ['code' => 'FIELD_MACHINERY', 'name' => 'Field Machinery'],
            ['code' => 'TRANSPORT_VEHICLE', 'name' => 'Transport Vehicle'],
            ['code' => 'IRRIGATION_EQUIPMENT', 'name' => 'Irrigation Equipment'],
            ['code' => 'LIVESTOCK', 'name' => 'Livestock'],
            ['code' => 'POST_HARVEST_EQUIPMENT', 'name' => 'Post-Harvest Equipment'],
            ['code' => 'STORAGE_EQUIPMENT', 'name' => 'Storage Equipment'],
            ['code' => 'TOOL', 'name' => 'Tool'],
            ['code' => 'IT_EQUIPMENT', 'name' => 'IT Equipment'],
            ['code' => 'IOT_DEVICE', 'name' => 'IoT Device'],
            ['code' => 'ENERGY_SYSTEM', 'name' => 'Energy System'],
            ['code' => 'SAFETY_EQUIPMENT', 'name' => 'Safety Equipment'],
            ['code' => 'OTHER', 'name' => 'Other'],
        ];

        foreach ($categories as $categoryData) {
            AssetCategory::firstOrCreate(
                [
                    'code' => $categoryData['code'],
                ],
                [
                    'name' => $categoryData['name'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Global asset categories seeded successfully.');
    }
}
