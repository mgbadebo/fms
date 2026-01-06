<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Farm;
use App\Models\Site;
use App\Models\Greenhouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GreenhouseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        Permission::firstOrCreate(['name' => 'greenhouses.view', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'greenhouses.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'greenhouses.update', 'guard_name' => 'web']);
    }

    public function test_farm_id_is_derived_from_site_when_creating_greenhouse(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['greenhouses.create', 'greenhouses.view']);
        Sanctum::actingAs($user);

        $farm = Farm::factory()->create();
        $user->farms()->attach($farm->id);
        
        $site = Site::factory()->create([
            'farm_id' => $farm->id,
            'type' => 'greenhouse',
        ]);

        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'name' => 'Test Greenhouse',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(201);
        
        $greenhouse = Greenhouse::where('site_id', $site->id)->first();
        $this->assertNotNull($greenhouse);
        $this->assertEquals($site->farm_id, $greenhouse->farm_id, 'farm_id should be derived from site');
        $this->assertEquals($site->id, $greenhouse->site_id);
    }

    public function test_farm_id_is_rejected_when_provided_in_request(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['greenhouses.create', 'greenhouses.view']);
        Sanctum::actingAs($user);

        $farm = Farm::factory()->create();
        $user->farms()->attach($farm->id);
        
        $site = Site::factory()->create([
            'farm_id' => $farm->id,
            'type' => 'greenhouse',
        ]);

        // Try to provide farm_id (should be rejected)
        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'farm_id' => $farm->id, // This should be rejected
            'name' => 'Test Greenhouse',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['farm_id']);
    }

    public function test_farm_id_is_re_derived_when_site_changes(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['greenhouses.create', 'greenhouses.update', 'greenhouses.view']);
        Sanctum::actingAs($user);

        $farm1 = Farm::factory()->create();
        $farm2 = Farm::factory()->create();
        $user->farms()->attach([$farm1->id, $farm2->id]);
        
        $site1 = Site::factory()->create([
            'farm_id' => $farm1->id,
            'type' => 'greenhouse',
        ]);
        
        $site2 = Site::factory()->create([
            'farm_id' => $farm2->id,
            'type' => 'greenhouse',
        ]);

        // Create greenhouse with site1
        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site1->id,
            'name' => 'Test Greenhouse',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(201);
        $greenhouse = Greenhouse::where('site_id', $site1->id)->first();
        $this->assertEquals($farm1->id, $greenhouse->farm_id);

        // Update to site2
        $response = $this->patchJson("/api/v1/greenhouses/{$greenhouse->id}", [
            'site_id' => $site2->id,
        ]);

        $response->assertStatus(200);
        $greenhouse->refresh();
        $this->assertEquals($farm2->id, $greenhouse->farm_id, 'farm_id should be re-derived from new site');
    }

    public function test_user_cannot_create_greenhouse_for_site_they_dont_belong_to(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['greenhouses.create', 'greenhouses.view']);
        Sanctum::actingAs($user);

        $farm = Farm::factory()->create();
        // User is NOT attached to this farm
        
        $site = Site::factory()->create([
            'farm_id' => $farm->id,
            'type' => 'greenhouse',
        ]);

        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'name' => 'Test Greenhouse',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(403);
    }

    public function test_greenhouse_code_must_be_unique_per_site(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['greenhouses.create', 'greenhouses.view']);
        Sanctum::actingAs($user);

        $farm = Farm::factory()->create();
        $user->farms()->attach($farm->id);
        
        $site = Site::factory()->create([
            'farm_id' => $farm->id,
            'type' => 'greenhouse',
        ]);

        // Create first greenhouse (will auto-generate GH-01)
        $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'name' => 'Test Greenhouse 1',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ])->assertStatus(201);

        // Create second greenhouse (will auto-generate GH-02)
        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'name' => 'Test Greenhouse 2',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(201);
        
        // Verify codes are unique and sequential
        $greenhouse1 = Greenhouse::where('site_id', $site->id)->orderBy('id')->first();
        $greenhouse2 = Greenhouse::where('site_id', $site->id)->orderBy('id', 'desc')->first();
        $this->assertEquals('GH-01', $greenhouse1->greenhouse_code);
        $this->assertEquals('GH-02', $greenhouse2->greenhouse_code);

        // Different site should start from GH-01 again
        $site2 = Site::factory()->create([
            'farm_id' => $farm->id,
            'type' => 'greenhouse',
        ]);

        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site2->id,
            'name' => 'Test Greenhouse 3',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(201);
    }

    public function test_total_area_is_computed_from_length_and_width(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['greenhouses.create', 'greenhouses.view']);
        Sanctum::actingAs($user);

        $farm = Farm::factory()->create();
        $user->farms()->attach($farm->id);
        
        $site = Site::factory()->create([
            'farm_id' => $farm->id,
            'type' => 'greenhouse',
        ]);

        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'name' => 'Test Greenhouse',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(201);
        
        $greenhouse = Greenhouse::where('site_id', $site->id)->first();
        $this->assertEquals(240.0, $greenhouse->total_area, 'total_area should be length * width');
    }

    public function test_greenhouse_code_is_auto_generated(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['greenhouses.create', 'greenhouses.view']);
        Sanctum::actingAs($user);

        $farm = Farm::factory()->create();
        $user->farms()->attach($farm->id);
        
        $site = Site::factory()->create([
            'farm_id' => $farm->id,
            'type' => 'greenhouse',
        ]);

        // Create greenhouse without providing greenhouse_code
        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'name' => 'Test Greenhouse',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(201);
        
        $greenhouse = Greenhouse::where('site_id', $site->id)->first();
        $this->assertNotNull($greenhouse->greenhouse_code, 'greenhouse_code should be auto-generated');
        $this->assertEquals('GH-01', $greenhouse->greenhouse_code, 'First greenhouse should be GH-01');
        
        // Create second greenhouse - should be GH-02
        $response = $this->postJson('/api/v1/greenhouses', [
            'site_id' => $site->id,
            'name' => 'Test Greenhouse 2',
            'type' => 'POLYHOUSE',
            'status' => 'ACTIVE',
            'length' => 30.0,
            'width' => 8.0,
        ]);

        $response->assertStatus(201);
        
        $greenhouse2 = Greenhouse::where('site_id', $site->id)
            ->where('name', 'Test Greenhouse 2')
            ->first();
        $this->assertEquals('GH-02', $greenhouse2->greenhouse_code, 'Second greenhouse should be GH-02');
    }
}
