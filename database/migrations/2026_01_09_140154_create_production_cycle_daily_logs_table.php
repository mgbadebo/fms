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
        Schema::create('production_cycle_daily_logs', function (Blueprint $table) {
            $table->id();
            
            // Derived fields (from production_cycle)
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('greenhouse_id')->constrained('greenhouses')->onDelete('cascade');
            $table->foreignId('production_cycle_id')->constrained('greenhouse_production_cycles')->onDelete('cascade');
            
            $table->date('log_date');
            $table->enum('status', ['DRAFT', 'SUBMITTED'])->default('DRAFT');
            $table->dateTime('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('issues_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            
            $table->timestamps();
            
            // One log per cycle per date
            $table->unique(['production_cycle_id', 'log_date']);
            $table->index(['production_cycle_id', 'log_date']);
            $table->index(['production_cycle_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_daily_logs');
    }
};
