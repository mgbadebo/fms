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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('category', ['TRACTOR', 'VEHICLE', 'IMPLEMENT', 'PUMP', 'BUILDING', 'OTHER'])->default('OTHER');
            $table->string('serial_number')->nullable();
            $table->date('purchase_date')->nullable();
            $table->enum('status', ['ACTIVE', 'MAINTENANCE', 'RETIRED', 'SOLD'])->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
