<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Farm;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Payment;
use App\Models\GreenhouseProductionCycle;
use App\Models\Greenhouse;
use App\Models\Site;
use App\Models\BellPepperHarvest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class SalesModuleTest extends TestCase
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
        
        $this->customer = Customer::factory()->create();
        
        $this->cycle = GreenhouseProductionCycle::factory()->create([
            'greenhouse_id' => $this->greenhouse->id,
            'farm_id' => $this->farm->id,
            'site_id' => $this->site->id,
            'cycle_status' => 'ACTIVE',
        ]);
    }

    /** @test */
    public function can_create_sales_order_with_items_linked_to_production_cycle(): void
    {
        Sanctum::actingAs($this->admin);
        
        $response = $this->postJson('/api/v1/sales-orders', [
            'farm_id' => $this->farm->id,
            'customer_id' => $this->customer->id,
            'order_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'production_cycle_id' => $this->cycle->id,
                    'product_name' => 'Bell Pepper - Grade A',
                    'quantity' => 100,
                    'unit' => 'KG',
                    'unit_price' => 500,
                    'discount_amount' => 0,
                ],
            ],
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('sales_orders', [
            'farm_id' => $this->farm->id,
            'customer_id' => $this->customer->id,
            'status' => 'DRAFT',
        ]);
        
        $order = SalesOrder::latest()->first();
        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $order->id,
            'production_cycle_id' => $this->cycle->id,
            'quantity' => 100,
            'unit_price' => 500,
        ]);
    }

    /** @test */
    public function sales_order_item_must_link_to_production_cycle_or_harvest(): void
    {
        Sanctum::actingAs($this->admin);
        
        $response = $this->postJson('/api/v1/sales-orders', [
            'farm_id' => $this->farm->id,
            'customer_id' => $this->customer->id,
            'order_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    // Missing production_cycle_id, harvest_record_id, harvest_lot_id
                    'product_name' => 'Bell Pepper',
                    'quantity' => 100,
                    'unit' => 'KG',
                    'unit_price' => 500,
                ],
            ],
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.production_cycle_id']);
    }

    /** @test */
    public function payment_updates_sales_order_payment_status(): void
    {
        Sanctum::actingAs($this->admin);
        
        $order = SalesOrder::factory()->create([
            'farm_id' => $this->farm->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 1000,
            'payment_status' => 'UNPAID',
        ]);
        
        // Record partial payment
        $response = $this->postJson("/api/v1/sales-orders/{$order->id}/payments", [
            'payment_date' => today()->format('Y-m-d'),
            'amount' => 500,
            'method' => 'CASH',
        ]);
        
        $response->assertStatus(201);
        $order->refresh();
        $this->assertEquals('PART_PAID', $order->payment_status);
        
        // Record full payment
        $this->postJson("/api/v1/sales-orders/{$order->id}/payments", [
            'payment_date' => today()->format('Y-m-d'),
            'amount' => 500,
            'method' => 'CASH',
        ]);
        
        $order->refresh();
        $this->assertEquals('PAID', $order->payment_status);
    }

    /** @test */
    public function kpi_endpoints_return_correct_aggregates(): void
    {
        Sanctum::actingAs($this->admin);
        
        // Create test data
        $order1 = SalesOrder::factory()->create([
            'farm_id' => $this->farm->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 1000,
            'status' => 'CONFIRMED',
        ]);
        
        $order2 = SalesOrder::factory()->create([
            'farm_id' => $this->farm->id,
            'customer_id' => $this->customer->id,
            'total_amount' => 2000,
            'status' => 'DISPATCHED',
        ]);
        
        $response = $this->getJson('/api/v1/kpis/sales-summary?farm_id=' . $this->farm->id);
        
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(3000, $data['total_revenue']);
        $this->assertEquals(2, $data['number_of_orders']);
        $this->assertEquals(1500, $data['average_order_value']);
    }

    /** @test */
    public function customers_are_global_and_not_farm_scoped(): void
    {
        $user = User::factory()->create();
        $user->assignRole('USER');
        
        // User belongs to a different farm
        $otherFarm = Farm::factory()->create();
        $user->farms()->attach($otherFarm->id);
        
        // Create a global customer (not tied to any farm)
        $globalCustomer = Customer::factory()->create();
        
        Sanctum::actingAs($user);
        
        // User should be able to use any customer since they're global
        // But they can only create orders for farms they belong to
        $response = $this->postJson('/api/v1/sales-orders', [
            'farm_id' => $otherFarm->id, // User's farm
            'customer_id' => $globalCustomer->id, // Global customer
            'order_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'production_cycle_id' => $this->cycle->id, // This cycle belongs to $this->farm, not $otherFarm
                    'product_name' => 'Test',
                    'quantity' => 100,
                    'unit' => 'KG',
                    'unit_price' => 500,
                ],
            ],
        ]);
        
        // This should fail because the production_cycle belongs to a different farm
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['items.0.production_cycle_id']);
    }

    /** @test */
    public function sales_order_totals_are_computed_server_side(): void
    {
        Sanctum::actingAs($this->admin);
        
        $response = $this->postJson('/api/v1/sales-orders', [
            'farm_id' => $this->farm->id,
            'customer_id' => $this->customer->id,
            'order_date' => today()->format('Y-m-d'),
            'items' => [
                [
                    'production_cycle_id' => $this->cycle->id,
                    'product_name' => 'Bell Pepper',
                    'quantity' => 100,
                    'unit' => 'KG',
                    'unit_price' => 500,
                    'discount_amount' => 100,
                ],
                [
                    'production_cycle_id' => $this->cycle->id,
                    'product_name' => 'Bell Pepper',
                    'quantity' => 50,
                    'unit' => 'KG',
                    'unit_price' => 400,
                    'discount_amount' => 0,
                ],
            ],
            'discount_total' => 50,
            'tax_total' => 100,
        ]);
        
        $response->assertStatus(201);
        $order = SalesOrder::latest()->first();
        
        // Subtotal should be sum of line_totals: (100*500-100) + (50*400-0) = 49900 + 20000 = 69900
        // Total should be: 69900 - 50 + 100 = 69950
        $this->assertEquals(69900, $order->subtotal);
        $this->assertEquals(69950, $order->total_amount);
    }
}
