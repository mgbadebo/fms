<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Farm;
use App\Models\Site;
use App\Models\Greenhouse;
use App\Models\GreenhouseProductionCycle;
use App\Models\ProductionCycleHarvestRecord;
use App\Models\ProductionCycleHarvestCrate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class HarvestRecordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('ADMIN');
        
        $this->farm = Farm::factory()->create();
        $this->site = Site::factory()->create(['farm_id' => $this->farm->id]);
        $this->greenhouse = Greenhouse::factory()->create([
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
        ]);
        
        $this->cycle = GreenhouseProductionCycle::factory()->create([
            'greenhouse_id' => $this->greenhouse->id,
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
            'cycle_status' => 'ACTIVE',
        ]);
    }

    /** @test */
    public function can_create_harvest_record_with_production_cycle_farm_site_greenhouse_derived(): void
    {
        Sanctum::actingAs($this->admin);
        
        $response = $this->postJson('/api/v1/harvest-records', [
            'production_cycle_id' => $this->cycle->id,
            'harvest_date' => today()->format('Y-m-d'),
            'notes' => 'Test harvest',
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('production_cycle_harvest_records', [
            'production_cycle_id' => $this->cycle->id,
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
            'greenhouse_id' => $this->greenhouse->id,
            'harvest_date' => today()->format('Y-m-d'),
            'status' => 'DRAFT',
        ]);
    }

    /** @test */
    public function harvest_record_requires_active_or_harvesting_cycle(): void
    {
        Sanctum::actingAs($this->admin);
        
        $this->cycle->update(['cycle_status' => 'COMPLETED']);
        
        $response = $this->postJson('/api/v1/harvest-records', [
            'production_cycle_id' => $this->cycle->id,
            'harvest_date' => today()->format('Y-m-d'),
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['production_cycle_id']);
    }

    /** @test */
    public function cannot_create_duplicate_harvest_record_for_same_cycle_and_date(): void
    {
        Sanctum::actingAs($this->admin);
        
        ProductionCycleHarvestRecord::factory()->create([
            'production_cycle_id' => $this->cycle->id,
            'harvest_date' => today()->format('Y-m-d'),
        ]);
        
        $response = $this->postJson('/api/v1/harvest-records', [
            'production_cycle_id' => $this->cycle->id,
            'harvest_date' => today()->format('Y-m-d'),
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['harvest_date']);
    }

    /** @test */
    public function adding_crates_recalculates_totals_accurately(): void
    {
        Sanctum::actingAs($this->admin);
        
        $record = ProductionCycleHarvestRecord::factory()->create([
            'production_cycle_id' => $this->cycle->id,
            'harvest_date' => today()->format('Y-m-d'),
        ]);
        
        // Add crates
        $this->postJson("/api/v1/harvest-records/{$record->id}/crates", [
            'grade' => 'A',
            'weight_kg' => 10.5,
        ]);
        
        $this->postJson("/api/v1/harvest-records/{$record->id}/crates", [
            'grade' => 'A',
            'weight_kg' => 15.3,
        ]);
        
        $this->postJson("/api/v1/harvest-records/{$record->id}/crates", [
            'grade' => 'B',
            'weight_kg' => 8.2,
        ]);
        
        $this->postJson("/api/v1/harvest-records/{$record->id}/crates", [
            'grade' => 'C',
            'weight_kg' => 5.0,
        ]);
        
        $record->refresh();
        
        $this->assertEquals(25.8, (float)$record->total_weight_kg_a);
        $this->assertEquals(8.2, (float)$record->total_weight_kg_b);
        $this->assertEquals(5.0, (float)$record->total_weight_kg_c);
        $this->assertEquals(39.0, (float)$record->total_weight_kg_total);
        $this->assertEquals(2, $record->crate_count_a);
        $this->assertEquals(1, $record->crate_count_b);
        $this->assertEquals(1, $record->crate_count_c);
        $this->assertEquals(4, $record->crate_count_total);
    }

    /** @test */
    public function submit_requires_at_least_one_crate(): void
    {
        Sanctum::actingAs($this->admin);
        
        $record = ProductionCycleHarvestRecord::factory()->create([
            'production_cycle_id' => $this->cycle->id,
            'harvest_date' => today()->format('Y-m-d'),
        ]);
        
        $response = $this->postJson("/api/v1/harvest-records/{$record->id}/submit");
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['crates']);
    }

    /** @test */
    public function submitted_record_cannot_be_edited_without_override_permission(): void
    {
        Sanctum::actingAs($this->admin);
        
        $record = ProductionCycleHarvestRecord::factory()->create([
            'production_cycle_id' => $this->cycle->id,
            'harvest_date' => today()->format('Y-m-d'),
            'status' => 'SUBMITTED',
        ]);
        
        ProductionCycleHarvestCrate::factory()->create([
            'harvest_record_id' => $record->id,
            'grade' => 'A',
            'weight_kg' => 10,
        ]);
        
        $response = $this->patchJson("/api/v1/harvest-records/{$record->id}", [
            'notes' => 'Updated notes',
        ]);
        
        // Should fail without override permission
        $response->assertStatus(403);
    }

    /** @test */
    public function daily_totals_endpoint_returns_correct_aggregates(): void
    {
        Sanctum::actingAs($this->admin);
        
        $greenhouse2 = Greenhouse::factory()->create([
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
        ]);
        
        $cycle2 = GreenhouseProductionCycle::factory()->create([
            'greenhouse_id' => $greenhouse2->id,
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
            'cycle_status' => 'ACTIVE',
        ]);
        
        $date = today()->format('Y-m-d');
        
        $record1 = ProductionCycleHarvestRecord::factory()->create([
            'production_cycle_id' => $this->cycle->id,
            'greenhouse_id' => $this->greenhouse->id,
            'harvest_date' => $date,
            'status' => 'SUBMITTED',
            'total_weight_kg_a' => 10,
            'total_weight_kg_b' => 5,
            'total_weight_kg_c' => 3,
            'total_weight_kg_total' => 18,
            'crate_count_total' => 5,
        ]);
        
        $record2 = ProductionCycleHarvestRecord::factory()->create([
            'production_cycle_id' => $cycle2->id,
            'greenhouse_id' => $greenhouse2->id,
            'harvest_date' => $date,
            'status' => 'SUBMITTED',
            'total_weight_kg_a' => 15,
            'total_weight_kg_b' => 8,
            'total_weight_kg_c' => 2,
            'total_weight_kg_total' => 25,
            'crate_count_total' => 6,
        ]);
        
        $response = $this->getJson("/api/v1/harvest-totals/daily?farm_id={$this->farm->id}&date={$date}");
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(2, $data['per_greenhouse']);
        $this->assertEquals(25, $data['all_greenhouses_total']['a_kg']);
        $this->assertEquals(13, $data['all_greenhouses_total']['b_kg']);
        $this->assertEquals(5, $data['all_greenhouses_total']['c_kg']);
        $this->assertEquals(43, $data['all_greenhouses_total']['total_kg']);
        $this->assertEquals(11, $data['all_greenhouses_total']['crates_total']);
    }

    /** @test */
    public function kpi_endpoint_includes_target_vs_actual_yield_variance(): void
    {
        Sanctum::actingAs($this->admin);
        
        $this->cycle->update([
            'target_total_yield_kg' => 100,
        ]);
        
        ProductionCycleHarvestRecord::factory()->create([
            'production_cycle_id' => $this->cycle->id,
            'status' => 'SUBMITTED',
            'total_weight_kg_total' => 120,
        ]);
        
        $response = $this->getJson("/api/v1/kpis/production-profitability?farm_id={$this->farm->id}");
        
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $cycleData = collect($data)->firstWhere('production_cycle_id', $this->cycle->id);
        $this->assertNotNull($cycleData);
        $this->assertEquals(100, $cycleData['target_total_yield_kg']);
        $this->assertEquals(120, $cycleData['actual_total_yield_kg']);
        $this->assertEquals(20, $cycleData['yield_variance_kg']);
        $this->assertEquals(20, $cycleData['yield_variance_pct']);
    }
}
