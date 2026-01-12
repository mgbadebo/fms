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
        Schema::create('production_cycle_daily_log_item_photos', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('farm_id')->constrained('farms')->onDelete('cascade');
            $table->foreignId('daily_log_item_id')->constrained('production_cycle_daily_log_items')->onDelete('cascade');
            
            // Store file path (match existing upload approach)
            $table->string('file_path');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->dateTime('uploaded_at');
            
            $table->timestamps();
            
            $table->index('daily_log_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_cycle_daily_log_item_photos');
    }
};
