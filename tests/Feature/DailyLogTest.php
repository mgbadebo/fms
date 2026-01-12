<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Farm;
use App\Models\Site;
use App\Models\Greenhouse;
use App\Models\GreenhouseProductionCycle;
use App\Models\ActivityType;
use App\Models\ProductionCycleDailyLog;
use App\Models\ProductionCycleDailyLogItem;
use App\Models\InputItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Carbon\Carbon;

class DailyLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = User::factory()->create();
        $this->admin->assignRole('ADMIN');
        
        $this->farm = Farm::factory()->create([
            'daily_log_cutoff_time' => '18:00:00',
            'default_timezone' => 'Africa/Lagos',
        ]);
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
        
        // Create activity types
        $this->irrigationType = ActivityType::factory()->create([
            'farm_id' => $this->farm->id,
            'code' => 'IRRIGATION',
            'name' => 'Irrigation',
            'requires_quantity' => true,
            'requires_time_range' => true,
        ]);
        
        $this->fertigationType = ActivityType::factory()->create([
            'farm_id' => $this->farm->id,
            'code' => 'FERTIGATION',
            'name' => 'Fertigation',
            'requires_quantity' => true,
            'requires_inputs' => true,
        ]);
        
        $this->sprayingType = ActivityType::factory()->create([
            'farm_id' => $this->farm->id,
            'code' => 'SPRAYING',
            'name' => 'Spraying',
            'requires_time_range' => true,
            'requires_inputs' => true,
        ]);
        
        $this->scoutingType = ActivityType::factory()->create([
            'farm_id' => $this->farm->id,
            'code' => 'SCOUTING',
            'name' => 'Scouting',
        ]);
        
        $this->inputItem = InputItem::factory()->create(['farm_id' => $this->farm->id]);
    }

    /** @test */
    public function can_create_draft_daily_log_with_items(): void
    {
        Sanctum::actingAs($this->admin);
        
        $response = $this->postJson("/api/v1/production-cycles/{$this->cycle->id}/daily-logs", [
            'log_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'activity_type_id' => $this->irrigationType->id,
                    'quantity' => 500,
                    'unit' => 'L',
                    'notes' => 'Morning irrigation',
                ],
            ],
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('production_cycle_daily_logs', [
            'production_cycle_id' => $this->cycle->id,
            'status' => 'DRAFT',
        ]);
    }

    /** @test */
    public function irrigation_requires_quantity_or_time_range(): void
    {
        Sanctum::actingAs($this->admin);
        
        // Missing both quantity and time range
        $response = $this->postJson("/api/v1/production-cycles/{$this->cycle->id}/daily-logs", [
            'log_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'activity_type_id' => $this->irrigationType->id,
                    'notes' => 'Irrigation without qty or time',
                ],
            ],
        ]);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function spraying_requires_inputs_and_notes(): void
    {
        Sanctum::actingAs($this->admin);
        
        // Missing inputs
        $response = $this->postJson("/api/v1/production-cycles/{$this->cycle->id}/daily-logs", [
            'log_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'activity_type_id' => $this->sprayingType->id,
                    'started_at' => now()->toISOString(),
                    'ended_at' => now()->addHour()->toISOString(),
                    'notes' => 'Spraying without inputs',
                ],
            ],
        ]);
        
        $response->assertStatus(422);
        
        // With inputs but missing notes
        $response = $this->postJson("/api/v1/production-cycles/{$this->cycle->id}/daily-logs", [
            'log_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'activity_type_id' => $this->sprayingType->id,
                    'started_at' => now()->toISOString(),
                    'ended_at' => now()->addHour()->toISOString(),
                    'inputs' => [
                        [
                            'input_item_id' => $this->inputItem->id,
                            'quantity' => 100,
                            'unit' => 'ml',
                        ],
                    ],
                    // Missing notes
                ],
            ],
        ]);
        
        $response->assertStatus(422);
    }

    /** @test */
    public function scouting_requires_severity_when_pests_observed(): void
    {
        Sanctum::actingAs($this->admin);
        
        $response = $this->postJson("/api/v1/production-cycles/{$this->cycle->id}/daily-logs", [
            'log_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'activity_type_id' => $this->scoutingType->id,
                    'meta' => [
                        'pests_observed' => true,
                        'disease_observed' => false,
                        // Missing severity
                    ],
                ],
            ],
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.meta.severity']);
    }

    /** @test */
    public function daily_log_submission_respects_cutoff_time(): void
    {
        Sanctum::actingAs($this->admin);
        
        $log = ProductionCycleDailyLog::factory()->create([
            'production_cycle_id' => $this->cycle->id,
            'log_date' => today(),
            'status' => 'DRAFT',
            'farm_id' => $this->farm->id,
        ]);
        
        // Mock time to be after cutoff
        Carbon::setTestNow(Carbon::parse('19:00:00', $this->farm->default_timezone));
        
        $response = $this->postJson("/api/v1/daily-logs/{$log->id}/submit");
        
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Daily log submission deadline (18:00:00) has passed. Contact an administrator to override.']);
        
        Carbon::setTestNow(); // Reset
    }

    /** @test */
    public function activity_type_must_belong_to_same_farm_as_cycle(): void
    {
        Sanctum::actingAs($this->admin);
        
        $otherFarm = Farm::factory()->create();
        $otherActivityType = ActivityType::factory()->create([
            'farm_id' => $otherFarm->id,
            'code' => 'IRRIGATION',
        ]);
        
        $response = $this->postJson("/api/v1/production-cycles/{$this->cycle->id}/daily-logs", [
            'log_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'activity_type_id' => $otherActivityType->id,
                    'quantity' => 500,
                    'unit' => 'L',
                ],
            ],
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.activity_type_id']);
    }

    /** @test */
    public function daily_logs_can_only_be_created_for_active_harvesting_cycles(): void
    {
        Sanctum::actingAs($this->admin);
        
        $plannedCycle = GreenhouseProductionCycle::factory()->create([
            'greenhouse_id' => $this->greenhouse->id,
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
            'cycle_status' => 'PLANNED',
        ]);
        
        $response = $this->postJson("/api/v1/production-cycles/{$plannedCycle->id}/daily-logs", [
            'log_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'activity_type_id' => $this->irrigationType->id,
                    'quantity' => 500,
                    'unit' => 'L',
                ],
            ],
        ]);
        
        $response->assertStatus(422);
        $response->assertJson(['message' => 'Daily logs can only be created for ACTIVE or HARVESTING production cycles.']);
    }
}
