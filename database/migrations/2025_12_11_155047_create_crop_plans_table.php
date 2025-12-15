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
        Schema::create('crop_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('field_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('season_id')->constrained()->onDelete('cascade');
            $table->foreignId('crop_id')->constrained()->onDelete('restrict');
            $table->date('planting_date');
            $table->decimal('target_yield', 10, 2)->nullable();
            $table->string('yield_unit')->default('kg');
            $table->enum('status', ['PLANNED', 'PLANTED', 'GROWING', 'HARVESTED', 'CANCELLED'])->default('PLANNED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_plans');
    }
};
