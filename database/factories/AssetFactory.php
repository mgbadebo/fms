<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\Farm;
use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'asset_category_id' => AssetCategory::factory(),
            'asset_code' => $this->faker->unique()->bothify('AST-#####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['ACTIVE', 'INACTIVE', 'UNDER_REPAIR']),
            'acquisition_type' => $this->faker->randomElement(['PURCHASED', 'LEASED', 'RENTED', 'DONATED']),
            'purchase_date' => $this->faker->optional()->date(),
            'purchase_cost' => $this->faker->optional()->randomFloat(2, 10000, 10000000),
            'currency' => 'NGN',
            'serial_number' => $this->faker->optional()->bothify('SN-#######'),
            'is_trackable' => true,
        ];
    }
}
