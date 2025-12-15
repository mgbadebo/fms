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
        Schema::create('weighing_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('scale_device_id')->constrained()->onDelete('restrict');
            $table->string('context_type'); // Polymorphic type: HarvestLot, StorageUnit, SalesOrder
            $table->unsignedBigInteger('context_id'); // Polymorphic ID
            $table->decimal('gross_weight', 10, 2)->nullable();
            $table->decimal('tare_weight', 10, 2)->nullable();
            $table->decimal('net_weight', 10, 2);
            $table->string('unit')->default('kg');
            $table->dateTime('weighed_at');
            $table->foreignId('operator_id')->nullable()->constrained('users')->onDelete('set null');
            $table->json('raw_payload')->nullable();
            $table->timestamps();
            
            $table->index(['context_type', 'context_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weighing_records');
    }
};
