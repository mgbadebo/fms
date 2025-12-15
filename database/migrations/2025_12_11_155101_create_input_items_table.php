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
        Schema::create('input_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('category', ['SEED', 'FERTILIZER', 'PESTICIDE', 'HERBICIDE', 'FEED', 'FUEL', 'OTHER'])->default('OTHER');
            $table->string('unit')->default('kg');
            $table->decimal('default_cost', 10, 2)->nullable();
            $table->string('currency')->default('USD');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('input_items');
    }
};
