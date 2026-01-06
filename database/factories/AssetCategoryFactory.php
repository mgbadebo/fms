<?php

namespace Database\Factories;

use App\Models\AssetCategory;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'name' => $this->faker->words(2, true),
            'parent_id' => null,
            'is_active' => true,
        ];
    }
}
