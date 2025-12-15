<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cassava_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('gari_production_batch_id')->nullable()->constrained('gari_production_batches')->onDelete('set null');
            
            $table->enum('source_type', ['HARVESTED', 'PURCHASED'])->default('HARVESTED');
            
            // If harvested
            $table->foreignId('harvest_lot_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('field_id')->nullable()->constrained()->onDelete('set null');
            
            // If purchased
            $table->string('supplier_name')->nullable();
            $table->string('supplier_contact')->nullable();
            $table->date('purchase_date')->nullable();
            
            // Common fields
            $table->decimal('quantity_kg', 10, 2);
            $table->decimal('cost_per_kg', 10, 2)->nullable();
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->string('variety')->nullable(); // Cassava variety
            $table->enum('quality_grade', ['A', 'B', 'C'])->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['farm_id', 'source_type']);
            $table->index('gari_production_batch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cassava_inputs');
    }
};

