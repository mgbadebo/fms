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
        Schema::create('production_cycle_daily_log_items', function (Blueprint $table) {
            $table->id();
            
            // Derived field
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('daily_log_id')->constrained('production_cycle_daily_logs')->onDelete('cascade');
            $table->foreignId('activity_type_id')->constrained('activity_types')->onDelete('restrict');
            
            $table->foreignId('performed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->decimal('quantity', 12, 2)->nullable();
            $table->string('unit')->nullable(); // L, KG, HOURS, etc.
            $table->text('notes')->nullable();
            $table->json('meta')->nullable(); // Type-specific fields (pests, severity, etc.)
            
            $table->timestamps();
            
            $table->index('daily_log_id');
            $table->index('activity_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_daily_log_items');
    }
};
