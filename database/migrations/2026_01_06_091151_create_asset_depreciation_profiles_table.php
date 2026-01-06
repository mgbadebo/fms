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
        Schema::create('asset_depreciation_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->enum('method', ['STRAIGHT_LINE', 'REDUCING_BALANCE'])->default('STRAIGHT_LINE');
            $table->integer('useful_life_months');
            $table->decimal('salvage_value', 14, 2)->nullable();
            $table->date('start_date');
            $table->timestamps();

            $table->unique('asset_id'); // One profile per asset
            $table->index('farm_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_profiles');
    }
};
