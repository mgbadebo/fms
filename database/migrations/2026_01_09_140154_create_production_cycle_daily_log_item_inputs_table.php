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
        Schema::create('production_cycle_daily_log_item_inputs', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('daily_log_item_id')->constrained('production_cycle_daily_log_items')->onDelete('cascade');
            $table->foreignId('input_item_id')->nullable()->constrained('input_items')->onDelete('set null');
            
            // Store name if input_item_id is null (for flexibility)
            $table->string('input_name')->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            $table->index('daily_log_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_daily_log_item_inputs');
    }
};
