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
        Schema::create('inventory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('input_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('inventory_location_id')->constrained()->onDelete('cascade');
            $table->enum('movement_type', ['IN', 'OUT', 'ADJUSTMENT']);
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->string('reference_type')->nullable(); // Polymorphic type
            $table->unsignedBigInteger('reference_id')->nullable(); // Polymorphic ID
            $table->dateTime('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_movements');
    }
};
