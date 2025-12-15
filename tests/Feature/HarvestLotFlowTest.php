<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Farm;
use App\Models\Season;
use App\Models\Crop;
use App\Models\Field;
use App\Models\Zone;
use App\Models\CropPlan;
use App\Models\HarvestLot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HarvestLotFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_farm_field_cropplan_harvestlot_flow(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create Farm
        $farm = Farm::create([
            'name' => 'Test Farm',
            'location' => 'Test Location',
            'is_active' => true,
        ]);

        // Attach user to farm
        $farm->users()->attach($user->id, ['role' => 'OWNER']);

        // Create Season
        $season = Season::create([
            'farm_id' => $farm->id,
            'name' => '2024 Season',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        // Create Crop
        $crop = Crop::create([
            'name' => 'Tomato',
            'category' => 'VEGETABLE',
            'default_maturity_days' => 90,
        ]);

        // Create Field
        $field = Field::create([
            'farm_id' => $farm->id,
            'name' => 'Field A',
            'area' => 10.5,
            'area_unit' => 'hectares',
        ]);

        // Create Zone
        $zone = Zone::create([
            'field_id' => $field->id,
            'name' => 'Zone 1',
            'relative_area_percent' => 50.0,
        ]);

        // Create CropPlan
        $cropPlan = CropPlan::create([
            'farm_id' => $farm->id,
            'field_id' => $field->id,
            'zone_id' => $zone->id,
            'season_id' => $season->id,
            'crop_id' => $crop->id,
            'planting_date' => '2024-03-01',
            'target_yield' => 5000.0,
            'yield_unit' => 'kg',
            'status' => 'GROWING',
        ]);

        // Create HarvestLot via API
        $response = $this->postJson('/api/v1/harvest-lots', [
            'farm_id' => $farm->id,
            'crop_plan_id' => $cropPlan->id,
            'field_id' => $field->id,
            'zone_id' => $zone->id,
            'season_id' => $season->id,
            'harvested_at' => '2024-12-11 10:00:00',
            'quality_grade' => 'A',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'code',
                'traceability_id',
                'farm_id',
                'field_id',
                'harvested_at',
            ],
        ]);

        $harvestLot = HarvestLot::where('farm_id', $farm->id)->first();
        $this->assertNotNull($harvestLot);
        $this->assertNotNull($harvestLot->traceability_id);
        $this->assertNotNull($harvestLot->code);
    }
}
