<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Farm;
use App\Models\AssetCategory;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\MaintenancePlan;
use App\Models\MaintenanceRecord;
use App\Models\AssetDepreciationProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class AssetTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Farm $farm;
    protected Farm $otherFarm;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test farms
        $this->farm = Farm::factory()->create();
        $this->otherFarm = Farm::factory()->create();
        
        // Create test user and assign to farm
        $this->user = User::factory()->create();
        $this->user->farms()->attach($this->farm->id, ['role' => 'MANAGER']);
        $this->user->assignRole('MANAGER');
        
        Sanctum::actingAs($this->user);
    }

    public function test_user_cannot_access_other_farm_assets(): void
    {
        $otherFarmCategory = AssetCategory::factory()->create(['farm_id' => $this->otherFarm->id]);
        $otherFarmAsset = Asset::factory()->create([
            'farm_id' => $this->otherFarm->id,
            'asset_category_id' => $otherFarmCategory->id,
        ]);

        $response = $this->getJson("/api/v1/assets/{$otherFarmAsset->id}");
        
        $response->assertStatus(404); // Should not find asset from other farm
    }

    public function test_can_create_asset_category(): void
    {
        $response = $this->postJson('/api/v1/asset-categories', [
            'farm_id' => $this->farm->id,
            'code' => 'TR',
            'name' => 'Tractor',
            'is_active' => true,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'code', 'name', 'farm_id']]);
        
        $this->assertDatabaseHas('asset_categories', [
            'farm_id' => $this->farm->id,
            'code' => 'TR',
            'name' => 'Tractor',
        ]);
    }

    public function test_can_create_asset_with_auto_generated_code(): void
    {
        $category = AssetCategory::factory()->create(['farm_id' => $this->farm->id, 'code' => 'TR']);

        $response = $this->postJson('/api/v1/assets', [
            'farm_id' => $this->farm->id,
            'asset_category_id' => $category->id,
            'name' => 'John Deere 5055E',
            'status' => 'ACTIVE',
            'purchase_cost' => 2500000,
            'currency' => 'NGN',
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertNotNull($data['asset_code']);
        $this->assertStringStartsWith('TR-', $data['asset_code']);
    }

    public function test_can_assign_asset(): void
    {
        $category = AssetCategory::factory()->create(['farm_id' => $this->farm->id]);
        $asset = Asset::factory()->create([
            'farm_id' => $this->farm->id,
            'asset_category_id' => $category->id,
        ]);
        $worker = \App\Models\Worker::factory()->create(['farm_id' => $this->farm->id]);

        $response = $this->postJson("/api/v1/assets/{$asset->id}/assign", [
            'assigned_to_type' => \App\Models\Worker::class,
            'assigned_to_id' => $worker->id,
            'notes' => 'Field work assignment',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('asset_assignments', [
            'asset_id' => $asset->id,
            'assigned_to_type' => \App\Models\Worker::class,
            'assigned_to_id' => $worker->id,
            'returned_at' => null,
        ]);
    }

    public function test_cannot_assign_already_assigned_asset(): void
    {
        $category = AssetCategory::factory()->create(['farm_id' => $this->farm->id]);
        $asset = Asset::factory()->create([
            'farm_id' => $this->farm->id,
            'asset_category_id' => $category->id,
        ]);
        $worker1 = \App\Models\Worker::factory()->create(['farm_id' => $this->farm->id]);
        $worker2 = \App\Models\Worker::factory()->create(['farm_id' => $this->farm->id]);

        // First assignment
        $this->postJson("/api/v1/assets/{$asset->id}/assign", [
            'assigned_to_type' => \App\Models\Worker::class,
            'assigned_to_id' => $worker1->id,
        ])->assertStatus(201);

        // Second assignment should fail
        $response = $this->postJson("/api/v1/assets/{$asset->id}/assign", [
            'assigned_to_type' => \App\Models\Worker::class,
            'assigned_to_id' => $worker2->id,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Asset is already assigned. Please return it first.']);
    }

    public function test_maintenance_record_updates_maintenance_plans(): void
    {
        $category = AssetCategory::factory()->create(['farm_id' => $this->farm->id]);
        $asset = Asset::factory()->create([
            'farm_id' => $this->farm->id,
            'asset_category_id' => $category->id,
        ]);

        $plan = MaintenancePlan::create([
            'farm_id' => $this->farm->id,
            'asset_id' => $asset->id,
            'plan_type' => 'MONTHS',
            'interval_value' => 3,
            'last_service_at' => now()->subMonths(6),
            'next_due_at' => now()->subMonths(3),
            'is_active' => true,
        ]);

        $performedAt = now();
        $response = $this->postJson("/api/v1/assets/{$asset->id}/maintenance-records", [
            'performed_at' => $performedAt->toDateTimeString(),
            'type' => 'SERVICE',
            'cost' => 50000,
            'currency' => 'NGN',
        ]);

        $response->assertStatus(201);
        
        $plan->refresh();
        $this->assertEquals($performedAt->format('Y-m-d H:i:s'), $plan->last_service_at->format('Y-m-d H:i:s'));
        $this->assertEquals($performedAt->addMonths(3)->format('Y-m-d'), $plan->next_due_at->format('Y-m-d'));
    }

    public function test_depreciation_schedule_endpoint(): void
    {
        $category = AssetCategory::factory()->create(['farm_id' => $this->farm->id]);
        $asset = Asset::factory()->create([
            'farm_id' => $this->farm->id,
            'asset_category_id' => $category->id,
            'purchase_cost' => 1000000,
            'currency' => 'NGN',
        ]);

        AssetDepreciationProfile::create([
            'farm_id' => $this->farm->id,
            'asset_id' => $asset->id,
            'method' => 'STRAIGHT_LINE',
            'useful_life_months' => 12,
            'salvage_value' => 100000,
            'start_date' => now()->subMonths(6),
        ]);

        $response = $this->getJson("/api/v1/assets/{$asset->id}/depreciation-schedule?to=" . now()->addMonths(6)->format('Y-m-d'));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('schedule', $data);
        $this->assertNotEmpty($data['schedule']);
        
        // Check first entry
        $firstEntry = $data['schedule'][0];
        $this->assertArrayHasKey('month', $firstEntry);
        $this->assertArrayHasKey('depreciation_amount', $firstEntry);
        $this->assertArrayHasKey('accumulated_depreciation', $firstEntry);
        $this->assertArrayHasKey('book_value', $firstEntry);
    }

    public function test_can_upload_attachment(): void
    {
        $category = AssetCategory::factory()->create(['farm_id' => $this->farm->id]);
        $asset = Asset::factory()->create([
            'farm_id' => $this->farm->id,
            'asset_category_id' => $category->id,
        ]);

        $file = \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson("/api/v1/assets/{$asset->id}/attachments", [
            'file' => $file,
            'notes' => 'Purchase receipt',
        ]);

        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertNotNull($data['file_path']);
        $this->assertEquals('document.pdf', $data['file_name']);
    }
}
