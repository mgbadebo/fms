<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gari_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('gari_production_batch_id')->nullable()->constrained('gari_production_batches')->onDelete('set null');
            
            // Product Identification
            $table->enum('gari_type', ['WHITE', 'YELLOW'])->default('WHITE');
            $table->enum('gari_grade', ['FINE', 'COARSE', 'MIXED'])->default('FINE');
            $table->enum('packaging_type', ['1KG_POUCH', '2KG_POUCH', '5KG_PACK', '50KG_BAG', 'BULK'])->default('1KG_POUCH');
            
            // Inventory Tracking
            $table->decimal('quantity_kg', 10, 2)->default(0);
            $table->integer('quantity_units')->default(0); // Number of pouches/packs/bags
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            
            // Costing
            $table->decimal('cost_per_kg', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            
            // Status
            $table->enum('status', ['IN_STOCK', 'RESERVED', 'SOLD', 'SPOILED', 'DAMAGED'])->default('IN_STOCK');
            $table->date('production_date')->nullable();
            $table->date('expiry_date')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['farm_id', 'gari_type', 'packaging_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gari_inventory');
    }
};

