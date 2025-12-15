<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Farm;
use App\Models\ScaleDevice;
use App\Models\HarvestLot;
use App\Models\Field;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScaleReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_weight_from_mock_scale(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $farm = Farm::create([
            'name' => 'Test Farm',
            'is_active' => true,
        ]);

        $scaleDevice = ScaleDevice::create([
            'farm_id' => $farm->id,
            'name' => 'Mock Scale',
            'connection_type' => 'MOCK',
            'connection_config' => ['unit' => 'kg'],
            'is_active' => true,
        ]);

        $season = Season::create([
            'farm_id' => $farm->id,
            'name' => '2024 Season',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'status' => 'ACTIVE',
        ]);

        $field = Field::create([
            'farm_id' => $farm->id,
            'name' => 'Field A',
            'area' => 10.0,
        ]);

        $harvestLot = HarvestLot::create([
            'farm_id' => $farm->id,
            'field_id' => $field->id,
            'season_id' => $season->id,
            'harvested_at' => now(),
        ]);

        $response = $this->postJson('/api/v1/scale-readings', [
            'scale_device_id' => $scaleDevice->id,
            'context_type' => 'App\Models\HarvestLot',
            'context_id' => $harvestLot->id,
            'unit' => 'kg',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'scale_device_id',
                'context_type',
                'context_id',
                'gross_weight',
                'tare_weight',
                'net_weight',
                'unit',
                'weighed_at',
            ],
        ]);

        $response->assertJson([
            'data' => [
                'context_type' => 'App\Models\HarvestLot',
                'context_id' => $harvestLot->id,
                'unit' => 'kg',
            ],
        ]);

        // Verify net_weight is set
        $this->assertNotNull($response->json('data.net_weight'));
        $this->assertGreaterThan(0, $response->json('data.net_weight'));

        // Verify HarvestLot was updated
        $harvestLot->refresh();
        $this->assertNotNull($harvestLot->net_weight);
    }
}
