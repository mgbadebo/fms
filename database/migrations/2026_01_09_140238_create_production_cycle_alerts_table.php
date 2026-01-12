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
        Schema::create('production_cycle_alerts', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('production_cycle_id')->constrained('greenhouse_production_cycles')->onDelete('cascade');
            $table->date('log_date');
            $table->string('alert_type')->default('MISSING_DAILY_LOG');
            $table->text('message');
            $table->enum('severity', ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'])->default('MEDIUM');
            $table->boolean('is_resolved')->default(false);
            $table->dateTime('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            
            // Prevent duplicate alerts
            $table->unique(['production_cycle_id', 'log_date', 'alert_type']);
            $table->index(['farm_id', 'is_resolved']);
            $table->index(['production_cycle_id', 'log_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_alerts');
    }
};
