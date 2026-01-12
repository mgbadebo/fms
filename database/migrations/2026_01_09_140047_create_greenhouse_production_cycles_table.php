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
        Schema::create('greenhouse_production_cycles', function (Blueprint $table) {
            $table->id();
            
            // Derived fields (from greenhouse->site->farm)
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('greenhouse_id')->constrained('greenhouses')->onDelete('cascade');
            $table->foreignId('season_id')->nullable()->constrained('seasons')->onDelete('set null');
            
            // Cycle identification
            $table->string('production_cycle_code')->unique();
            $table->string('crop')->default('BELL_PEPPER'); // Allow future crops
            $table->string('variety')->nullable();
            $table->enum('cycle_status', ['PLANNED', 'ACTIVE', 'HARVESTING', 'COMPLETED', 'ABANDONED'])->default('PLANNED');
            
            // Section 1: Planting & Establishment (required)
            $table->date('planting_date');
            $table->enum('establishment_method', ['DIRECT_SEED', 'TRANSPLANT'])->default('TRANSPLANT');
            $table->string('seed_supplier_name');
            $table->string('seed_batch_number');
            $table->date('nursery_start_date')->nullable();
            $table->date('transplant_date')->nullable();
            $table->decimal('plant_spacing_cm', 10, 2);
            $table->decimal('row_spacing_cm', 10, 2);
            $table->decimal('plant_density_per_sqm', 10, 2)->nullable();
            $table->integer('initial_plant_count');
            
            // Section 2: Growing medium & setup (required)
            $table->enum('cropping_system', ['SOIL', 'COCOPEAT', 'HYDROPONIC']);
            $table->string('medium_type');
            $table->integer('bed_count');
            $table->integer('bench_count')->nullable();
            $table->boolean('mulching_used')->default(false);
            $table->enum('support_system', ['STAKES', 'TRELLIS', 'STRING', 'NONE'])->default('TRELLIS');
            
            // Section 3: Environmental targets (required)
            $table->decimal('target_day_temperature_c', 5, 2);
            $table->decimal('target_night_temperature_c', 5, 2);
            $table->decimal('target_humidity_percent', 5, 2);
            $table->decimal('target_light_hours', 5, 2);
            $table->enum('ventilation_strategy', ['NATURAL', 'FORCED'])->default('NATURAL');
            $table->decimal('shade_net_percentage', 5, 2)->nullable();
            
            // Management
            $table->foreignId('responsible_supervisor_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            
            // Lifecycle dates
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            
            // Additional fields
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Constraints
            $table->unique(['greenhouse_id', 'production_cycle_code']);
            
            // Indexes for performance
            $table->index(['greenhouse_id', 'cycle_status']);
            $table->index(['farm_id', 'cycle_status']);
            $table->index('cycle_status');
        });
        
        // Add partial unique index for only one ACTIVE/HARVESTING cycle per greenhouse
        // Note: MySQL doesn't support partial unique indexes, so we'll enforce this in code
        // For PostgreSQL, you could use: CREATE UNIQUE INDEX ... WHERE cycle_status IN ('ACTIVE', 'HARVESTING')
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('greenhouse_production_cycles');
    }
};
