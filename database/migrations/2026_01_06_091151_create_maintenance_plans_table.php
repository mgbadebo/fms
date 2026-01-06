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
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->enum('plan_type', ['HOURS', 'DAYS', 'MONTHS', 'USAGE'])->default('MONTHS');
            $table->integer('interval_value'); // e.g., 3 months, 100 hours
            $table->dateTime('last_service_at')->nullable();
            $table->dateTime('next_due_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'asset_id']);
            $table->index(['asset_id', 'is_active']);
            $table->index('next_due_at'); // For finding upcoming maintenance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_plans');
    }
};
