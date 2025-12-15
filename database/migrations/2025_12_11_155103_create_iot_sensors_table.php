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
        Schema::create('iot_sensors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['WEATHER', 'SOIL', 'FLOW', 'OTHER'])->default('OTHER');
            $table->string('external_id')->unique()->nullable();
            $table->foreignId('location_field_id')->nullable()->constrained('fields')->onDelete('set null');
            $table->foreignId('location_zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->json('meta')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iot_sensors');
    }
};
