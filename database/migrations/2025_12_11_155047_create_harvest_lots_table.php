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
        Schema::create('harvest_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('crop_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('field_id')->constrained()->onDelete('cascade');
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('season_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->dateTime('harvested_at');
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('net_weight', 10, 2)->nullable();
            $table->string('weight_unit')->default('kg');
            $table->string('quality_grade')->nullable();
            $table->unsignedBigInteger('storage_location_id')->nullable();
            $table->string('traceability_id')->unique()->nullable();
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
        Schema::dropIfExists('harvest_lots');
    }
};
