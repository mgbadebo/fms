<?php

namespace Database\Factories;

use App\Models\ProductionCycleDailyLog;
use App\Models\Farm;
use App\Models\Site;
use App\Models\Greenhouse;
use App\Models\GreenhouseProductionCycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionCycleDailyLog>
 */
class ProductionCycleDailyLogFactory extends Factory
{
    protected $model = ProductionCycleDailyLog::class;

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
            'production_cycle_id' => GreenhouseProductionCycle::factory(),
            'log_date' => fake()->date(),
            'status' => 'DRAFT',
            'submitted_at' => null,
            'submitted_by' => null,
            'issues_notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }
}
