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
        Schema::create('storage_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->enum('type', ['BAG', 'PALLET', 'BIN', 'CRATE', 'OTHER'])->default('BAG');
            $table->string('capacity_unit')->default('kg');
            $table->decimal('capacity_value', 10, 2)->nullable();
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
        Schema::dropIfExists('storage_units');
    }
};
