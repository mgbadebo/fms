<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gari_production_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('batch_code')->unique();
            $table->date('processing_date');
            $table->enum('cassava_source', ['HARVESTED', 'PURCHASED', 'MIXED'])->default('HARVESTED');
            
            // Raw Material Input
            $table->decimal('cassava_quantity_kg', 10, 2); // Total cassava used
            $table->decimal('cassava_cost_per_kg', 10, 2)->nullable(); // Cost per kg
            $table->decimal('total_cassava_cost', 10, 2)->nullable(); // Total cost
            
            // Processing Output
            $table->decimal('gari_produced_kg', 10, 2); // Gari produced
            $table->enum('gari_type', ['WHITE', 'YELLOW'])->default('WHITE');
            $table->enum('gari_grade', ['FINE', 'COARSE', 'MIXED'])->default('FINE');
            $table->decimal('conversion_yield_percent', 5, 2)->nullable(); // Calculated: (gari/cassava) * 100
            
            // Processing Costs
            $table->decimal('labour_cost', 10, 2)->default(0);
            $table->decimal('fuel_cost', 10, 2)->default(0); // Firewood/gas/diesel
            $table->decimal('equipment_cost', 10, 2)->default(0); // Grating/pressing fees, maintenance
            $table->decimal('water_cost', 10, 2)->default(0);
            $table->decimal('transport_cost', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);
            $table->decimal('total_processing_cost', 10, 2)->default(0); // Sum of all processing costs
            
            // Waste & Loss
            $table->decimal('waste_kg', 10, 2)->default(0);
            $table->decimal('waste_percent', 5, 2)->nullable(); // Calculated
            
            // Financial
            $table->decimal('total_cost', 10, 2)->nullable(); // cassava + processing
            $table->decimal('cost_per_kg_gari', 10, 2)->nullable(); // total_cost / gari_produced
            
            $table->text('notes')->nullable();
            $table->enum('status', ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->default('PLANNED');
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['farm_id', 'processing_date']);
            $table->index('batch_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gari_production_batches');
    }
};

