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
        Schema::create('production_cycle_harvest_crates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('harvest_record_id')->constrained('production_cycle_harvest_records')->onDelete('cascade');
            $table->enum('grade', ['A', 'B', 'C'])->default('A');
            $table->integer('crate_number');
            $table->decimal('weight_kg', 10, 2);
            $table->dateTime('weighed_at')->nullable();
            $table->foreignId('weighed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('label_code')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Constraints
            $table->unique(['harvest_record_id', 'crate_number']);
            $table->index('harvest_record_id');
            $table->index('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_harvest_crates');
    }
};
