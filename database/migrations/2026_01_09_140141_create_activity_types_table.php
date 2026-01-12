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
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            
            $table->string('code'); // IRRIGATION, FERTIGATION, PRUNING, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // CULTURAL, WATER, NUTRITION, PROTECTION, HYGIENE, MAINTENANCE
            
            // Field requirements flags
            $table->boolean('requires_quantity')->default(false);
            $table->boolean('requires_time_range')->default(false);
            $table->boolean('requires_inputs')->default(false);
            $table->boolean('requires_photos')->default(false);
            
            // Optional schema for future dynamic validation
            $table->json('schema')->nullable();
            
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Unique code per farm
            $table->unique(['farm_id', 'code']);
            $table->index(['farm_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
