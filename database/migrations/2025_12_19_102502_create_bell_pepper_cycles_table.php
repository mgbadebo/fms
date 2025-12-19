<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bell_pepper_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('greenhouse_id')->constrained()->onDelete('cascade');
            $table->string('cycle_code')->unique();
            $table->date('start_date');
            $table->date('expected_end_date');
            $table->date('actual_end_date')->nullable();
            $table->enum('status', ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'])->default('PLANNED');
            $table->decimal('expected_yield_kg', 10, 2)->default(0); // Expected total yield in kg
            $table->decimal('expected_yield_per_sqm', 10, 2)->default(0); // Expected yield per sqm
            $table->decimal('actual_yield_kg', 10, 2)->default(0); // Actual total yield in kg
            $table->decimal('actual_yield_per_sqm', 10, 2)->default(0); // Actual yield per sqm
            $table->decimal('yield_variance_percent', 5, 2)->default(0); // Variance percentage
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bell_pepper_cycles');
    }
};
