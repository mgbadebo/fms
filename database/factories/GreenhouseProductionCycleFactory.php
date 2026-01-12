<?php

namespace Database\Factories;

use App\Models\GreenhouseProductionCycle;
use App\Models\Farm;
use App\Models\Site;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GreenhouseProductionCycle>
 */
class GreenhouseProductionCycleFactory extends Factory
{
    protected $model = GreenhouseProductionCycle::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'farm_id' => Farm::factory(),
            'site_id' => Site::factory(),
            'greenhouse_id' => Greenhouse::factory(),
            'season_id' => Season::factory(),
            'production_cycle_code' => 'PC-' . strtoupper(fake()->unique()->bothify('???####')),
            'crop' => 'BELL_PEPPER',
            'variety' => fake()->words(2, true),
            'cycle_status' => 'PLANNED',
            'planting_date' => fake()->date(),
            'establishment_method' => fake()->randomElement(['DIRECT_SEED', 'TRANSPLANT']),
            'seed_supplier_name' => fake()->company(),
            'seed_batch_number' => fake()->bothify('BATCH-####'),
            'nursery_start_date' => fake()->optional()->date(),
            'transplant_date' => fake()->optional()->date(),
            'plant_spacing_cm' => fake()->randomFloat(2, 20, 50),
            'row_spacing_cm' => fake()->randomFloat(2, 40, 80),
            'plant_density_per_sqm' => fake()->optional()->randomFloat(2, 1, 10),
            'initial_plant_count' => fake()->numberBetween(100, 10000),
            'cropping_system' => fake()->randomElement(['SOIL', 'COCOPEAT', 'HYDROPONIC']),
            'medium_type' => fake()->words(2, true),
            'bed_count' => fake()->numberBetween(1, 20),
            'bench_count' => fake()->optional()->numberBetween(0, 50),
            'mulching_used' => fake()->boolean(),
            'support_system' => fake()->randomElement(['STAKES', 'TRELLIS', 'STRING', 'NONE']),
            'target_day_temperature_c' => fake()->randomFloat(2, 20, 30),
            'target_night_temperature_c' => fake()->randomFloat(2, 15, 25),
            'target_humidity_percent' => fake()->randomFloat(2, 50, 90),
            'target_light_hours' => fake()->randomFloat(2, 8, 16),
            'ventilation_strategy' => fake()->randomElement(['NATURAL', 'FORCED']),
            'shade_net_percentage' => fake()->optional()->randomFloat(2, 0, 100),
            'responsible_supervisor_user_id' => User::factory(),
            'created_by' => User::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
