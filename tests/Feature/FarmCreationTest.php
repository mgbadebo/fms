<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Farm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FarmCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles
        Role::firstOrCreate(['name' => 'ADMIN', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'OWNER', 'guard_name' => 'web']);
    }

    public function test_farm_creation_succeeds_with_valid_data(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('ADMIN');
        
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = [
            'name' => 'Green Haven Farms',
            'legal_name' => 'Green Haven Farms Ltd',
            'farm_type' => 'MIXED',
            'country' => 'Nigeria',
            'state' => 'Ondo',
            'town' => 'Owo',
            'default_currency' => 'NGN',
            'default_unit_system' => 'METRIC',
            'default_timezone' => 'Africa/Lagos',
            'accounting_method' => 'ACCRUAL',
            'status' => 'ACTIVE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/farms', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'farm_code',
                    'name',
                    'legal_name',
                    'farm_type',
                    'country',
                    'state',
                    'town',
                    'default_currency',
                    'default_unit_system',
                    'default_timezone',
                    'accounting_method',
                    'status',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('farms', [
            'name' => 'Green Haven Farms',
            'legal_name' => 'Green Haven Farms Ltd',
            'farm_type' => 'MIXED',
            'country' => 'Nigeria',
            'state' => 'Ondo',
            'town' => 'Owo',
            'default_currency' => 'NGN',
            'default_unit_system' => 'METRIC',
            'default_timezone' => 'Africa/Lagos',
            'accounting_method' => 'ACCRUAL',
            'status' => 'ACTIVE',
            'created_by' => $user->id,
        ]);

        // Verify farm_code was auto-generated
        $farm = Farm::where('name', 'Green Haven Farms')->first();
        $this->assertNotNull($farm->farm_code);
        $this->assertStringStartsWith('FARM-', $farm->farm_code);
    }

    public function test_farm_creation_fails_without_state(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('ADMIN');
        
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = [
            'name' => 'Green Haven Farms',
            'legal_name' => 'Green Haven Farms Ltd',
            'farm_type' => 'MIXED',
            'country' => 'Nigeria',
            // 'state' => 'Ondo', // Missing
            'town' => 'Owo',
            'default_currency' => 'NGN',
            'default_unit_system' => 'METRIC',
            'default_timezone' => 'Africa/Lagos',
            'accounting_method' => 'ACCRUAL',
            'status' => 'ACTIVE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/farms', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }

    public function test_farm_creation_fails_without_town(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test3@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('ADMIN');
        
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = [
            'name' => 'Green Haven Farms',
            'legal_name' => 'Green Haven Farms Ltd',
            'farm_type' => 'MIXED',
            'country' => 'Nigeria',
            'state' => 'Ondo',
            // 'town' => 'Owo', // Missing
            'default_currency' => 'NGN',
            'default_unit_system' => 'METRIC',
            'default_timezone' => 'Africa/Lagos',
            'accounting_method' => 'ACCRUAL',
            'status' => 'ACTIVE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/farms', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['town']);
    }

    public function test_farm_code_is_auto_generated(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test4@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('ADMIN');
        
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = [
            'name' => 'Test Farm',
            'farm_type' => 'CROP',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'town' => 'Ikeja',
            'default_currency' => 'NGN',
            'default_unit_system' => 'METRIC',
            'default_timezone' => 'Africa/Lagos',
            'accounting_method' => 'ACCRUAL',
            'status' => 'ACTIVE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/farms', $payload);

        $response->assertStatus(201);
        
        $farm = Farm::where('name', 'Test Farm')->first();
        $this->assertNotNull($farm->farm_code);
        $this->assertStringStartsWith('FARM-', $farm->farm_code);
    }

    public function test_unauthorized_user_cannot_create_farm(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test5@example.com',
            'password' => Hash::make('password'),
        ]);
        // User without ADMIN or OWNER role
        
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = [
            'name' => 'Green Haven Farms',
            'farm_type' => 'MIXED',
            'country' => 'Nigeria',
            'state' => 'Ondo',
            'town' => 'Owo',
            'default_currency' => 'NGN',
            'default_unit_system' => 'METRIC',
            'default_timezone' => 'Africa/Lagos',
            'accounting_method' => 'ACCRUAL',
            'status' => 'ACTIVE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/farms', $payload);

        $response->assertStatus(403);
    }

    public function test_owner_role_can_create_farm(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test6@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('OWNER');
        
        $token = $user->createToken('test-token')->plainTextToken;

        $payload = [
            'name' => 'Owner Farm',
            'farm_type' => 'CROP',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'town' => 'Ikeja',
            'default_currency' => 'NGN',
            'default_unit_system' => 'METRIC',
            'default_timezone' => 'Africa/Lagos',
            'accounting_method' => 'ACCRUAL',
            'status' => 'ACTIVE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/farms', $payload);

        $response->assertStatus(201);
    }
}
