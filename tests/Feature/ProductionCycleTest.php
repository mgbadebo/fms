<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Farm;
use App\Models\Site;
use App\Models\Greenhouse;
use App\Models\GreenhouseProductionCycle;
use App\Models\Season;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ProductionCycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('ADMIN');
        
        // Create farm and related entities
        $this->farm = Farm::factory()->create();
        $this->site = Site::factory()->create(['farm_id' => $this->farm->id]);
        $this->greenhouse = Greenhouse::factory()->create([
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
        ]);
        $this->season = Season::factory()->create(['farm_id' => $this->farm->id]);
    }

    /** @test */
    public function production_cycle_creation_fails_if_required_fields_missing(): void
    {
        Sanctum::actingAs($this->admin);
        
        // Missing Section 1 fields
        $response = $this->postJson('/api/v1/production-cycles', [
            'greenhouse_id' => $this->greenhouse->id,
            'responsible_supervisor_user_id' => $this->admin->id,
            // Missing planting_date, seed_supplier_name, etc.
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['planting_date', 'seed_supplier_name', 'seed_batch_number']);
    }

    /** @test */
    public function production_cycle_creation_requires_all_sections(): void
    {
        Sanctum::actingAs($this->admin);
        
        $data = [
            'greenhouse_id' => $this->greenhouse->id,
            'responsible_supervisor_user_id' => $this->admin->id,
            'crop' => 'BELL_PEPPER',
            // Section 1: Planting & Establishment
            'planting_date' => '2024-01-01',
            'establishment_method' => 'TRANSPLANT',
            'seed_supplier_name' => 'Test Supplier',
            'seed_batch_number' => 'BATCH001',
            'plant_spacing_cm' => 30.0,
            'row_spacing_cm' => 60.0,
            'initial_plant_count' => 1000,
            // Section 2: Growing medium & setup
            'cropping_system' => 'COCOPEAT',
            'medium_type' => 'Coco Peat',
            'bed_count' => 4,
            'mulching_used' => true,
            'support_system' => 'TRELLIS',
            // Section 3: Environmental targets
            'target_day_temperature_c' => 25.0,
            'target_night_temperature_c' => 18.0,
            'target_humidity_percent' => 70.0,
            'target_light_hours' => 12.0,
            'ventilation_strategy' => 'NATURAL',
        ];
        
        $response = $this->postJson('/api/v1/production-cycles', $data);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('greenhouse_production_cycles', [
            'greenhouse_id' => $this->greenhouse->id,
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
        ]);
    }

    /** @test */
    public function only_one_active_harvesting_cycle_per_greenhouse(): void
    {
        Sanctum::actingAs($this->admin);
        
        // Create first active cycle
        $cycle1 = GreenhouseProductionCycle::factory()->create([
            'greenhouse_id' => $this->greenhouse->id,
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
            'cycle_status' => 'ACTIVE',
        ]);
        
        // Try to create another active cycle
        $data = $this->getValidCycleData();
        $response = $this->postJson('/api/v1/production-cycles', $data);
        
        $response->assertStatus(201); // Creation succeeds (PLANNED status)
        
        // Try to start the second cycle (should fail)
        $cycle2 = GreenhouseProductionCycle::where('greenhouse_id', $this->greenhouse->id)
            ->where('id', '!=', $cycle1->id)
            ->latest()
            ->first();
        
        $response = $this->postJson("/api/v1/production-cycles/{$cycle2->id}/start");
        $response->assertStatus(422);
        $response->assertJson(['message' => 'This greenhouse already has an active or harvesting production cycle.']);
    }

    /** @test */
    public function farm_id_and_site_id_are_derived_from_greenhouse(): void
    {
        Sanctum::actingAs($this->admin);
        
        $data = $this->getValidCycleData();
        
        // Try to provide farm_id and site_id (should be rejected)
        $data['farm_id'] = 999;
        $data['site_id'] = 999;
        
        $response = $this->postJson('/api/v1/production-cycles', $data);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['farm_id', 'site_id']);
    }

    protected function getValidCycleData(): array
    {
        return [
            'greenhouse_id' => $this->greenhouse->id,
            'responsible_supervisor_user_id' => $this->admin->id,
            'crop' => 'BELL_PEPPER',
            'planting_date' => '2024-01-01',
            'establishment_method' => 'TRANSPLANT',
            'seed_supplier_name' => 'Test Supplier',
            'seed_batch_number' => 'BATCH001',
            'plant_spacing_cm' => 30.0,
            'row_spacing_cm' => 60.0,
            'initial_plant_count' => 1000,
            'cropping_system' => 'COCOPEAT',
            'medium_type' => 'Coco Peat',
            'bed_count' => 4,
            'mulching_used' => true,
            'support_system' => 'TRELLIS',
            'target_day_temperature_c' => 25.0,
            'target_night_temperature_c' => 18.0,
            'target_humidity_percent' => 70.0,
            'target_light_hours' => 12.0,
            'ventilation_strategy' => 'NATURAL',
        ];
    }
}
