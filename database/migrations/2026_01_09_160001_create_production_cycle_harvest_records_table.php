<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_cycle_harvest_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('greenhouse_id')->constrained('greenhouses')->onDelete('cascade');
            $table->foreignId('production_cycle_id')->constrained('greenhouse_production_cycles')->onDelete('cascade');
            $table->date('harvest_date');
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'APPROVED'])->default('DRAFT');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('restrict');
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('approved_at')->nullable();
            
            // Totals (computed from crates)
            $table->decimal('total_weight_kg_a', 14, 2)->default(0);
            $table->decimal('total_weight_kg_b', 14, 2)->default(0);
            $table->decimal('total_weight_kg_c', 14, 2)->default(0);
            $table->decimal('total_weight_kg_total', 14, 2)->default(0);
            $table->integer('crate_count_a')->default(0);
            $table->integer('crate_count_b')->default(0);
            $table->integer('crate_count_c')->default(0);
            $table->integer('crate_count_total')->default(0);
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Constraints
            $table->unique(['production_cycle_id', 'harvest_date']);
            $table->index(['farm_id', 'harvest_date']);
            $table->index(['greenhouse_id', 'harvest_date']);
            $table->index('production_cycle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_harvest_records');
    }
};
