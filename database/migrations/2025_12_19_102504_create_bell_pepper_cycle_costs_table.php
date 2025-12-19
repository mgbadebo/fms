<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bell_pepper_cycle_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bell_pepper_cycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->enum('cost_type', [
                'SEEDS',
                'FERTILIZER_CHEMICALS',
                'FUEL_WATER_PUMPING',
                'LABOUR_DEDICATED',
                'LABOUR_SHARED',
                'SPRAY_GUNS',
                'IRRIGATION_EQUIPMENT',
                'PROTECTIVE_CLOTHING',
                'GREENHOUSE_AMORTIZATION',
                'BOREHOLE_AMORTIZATION',
                'LOGISTICS',
                'OTHER'
            ]);
            $table->string('description')->nullable();
            $table->decimal('quantity', 10, 2)->nullable(); // For items like seeds, fertilizer
            $table->string('unit')->nullable(); // kg, litres, units, etc.
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->date('cost_date');
            $table->foreignId('staff_id')->nullable()->constrained('users')->onDelete('set null'); // For labour costs
            $table->decimal('hours_allocated', 5, 2)->nullable(); // For shared labour
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bell_pepper_cycle_costs');
    }
};
