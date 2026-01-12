<?php

namespace Database\Factories;

use App\Models\ActivityType;
use App\Models\Farm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityType>
 */
class ActivityTypeFactory extends Factory
{
    protected $model = ActivityType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'code' => strtoupper(fake()->unique()->word()),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'category' => fake()->optional()->randomElement(['CULTURAL', 'WATER', 'NUTRITION', 'PROTECTION', 'HYGIENE', 'MAINTENANCE']),
            'requires_quantity' => fake()->boolean(),
            'requires_time_range' => fake()->boolean(),
            'requires_inputs' => fake()->boolean(),
            'requires_photos' => fake()->boolean(),
            'schema' => null,
            'is_active' => true,
        ];
    }
}
