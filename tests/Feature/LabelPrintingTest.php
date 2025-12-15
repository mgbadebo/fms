<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Farm;
use App\Models\LabelTemplate;
use App\Models\HarvestLot;
use App\Models\Field;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LabelPrintingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_print_label_for_harvest_lot(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $farm = Farm::create([
            'name' => 'Test Farm',
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
            'net_weight' => 25.5,
            'weight_unit' => 'kg',
        ]);

        $labelTemplate = LabelTemplate::create([
            'farm_id' => $farm->id,
            'name' => 'Harvest Lot Label',
            'code' => 'HARVEST_LOT_LABEL',
            'target_type' => 'HARVEST_LOT',
            'template_engine' => 'RAW',
            'template_body' => 'Harvest Lot: {{code}}\nWeight: {{net_weight}} {{weight_unit}}\nTraceability ID: {{traceability_id}}',
            'is_default' => true,
        ]);

        $response = $this->postJson('/api/v1/labels/print', [
            'label_template_id' => $labelTemplate->id,
            'target_type' => 'App\Models\HarvestLot',
            'target_id' => $harvestLot->id,
            'printer_name' => 'Mock Printer',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'printed_label' => [
                    'id',
                    'farm_id',
                    'label_template_id',
                    'target_type',
                    'target_id',
                    'printed_at',
                ],
                'rendered_content',
                'success',
            ],
        ]);

        $response->assertJson([
            'data' => [
                'success' => true,
            ],
        ]);

        // Verify rendered content contains expected values
        $renderedContent = $response->json('data.rendered_content');
        $this->assertStringContainsString($harvestLot->code, $renderedContent);
        $this->assertStringContainsString('25.5', $renderedContent);
        $this->assertStringContainsString('kg', $renderedContent);
    }
}
