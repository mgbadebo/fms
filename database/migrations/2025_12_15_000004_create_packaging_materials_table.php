<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packaging_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            
            $table->string('name'); // e.g., "1kg Pouch", "50kg Sack"
            $table->enum('material_type', ['POUCH', 'SACK', 'LABEL', 'SEALING_ROLL', 'CARTON', 'OTHER'])->default('POUCH');
            $table->string('size')->nullable(); // e.g., "1kg", "2kg", "50kg"
            $table->string('unit')->default('pieces'); // pieces, rolls, etc.
            
            // Inventory Tracking
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('quantity_purchased', 10, 2)->default(0);
            $table->decimal('quantity_used', 10, 2)->default(0);
            $table->decimal('closing_balance', 10, 2)->default(0); // Calculated: opening + purchased - used
            
            // Costing
            $table->decimal('cost_per_unit', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            
            $table->foreignId('location_id')->nullable()->constrained('inventory_locations')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['farm_id', 'material_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packaging_materials');
    }
};

