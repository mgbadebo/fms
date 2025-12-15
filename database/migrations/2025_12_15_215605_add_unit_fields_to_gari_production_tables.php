<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add cassava quantity in tonnes (keeping kg for backward compatibility)
        Schema::table('gari_production_batches', function (Blueprint $table) {
            $table->decimal('cassava_quantity_tonnes', 10, 3)->nullable()->after('cassava_quantity_kg');
            $table->decimal('cassava_cost_per_tonne', 10, 2)->nullable()->after('cassava_cost_per_kg');
        });

        // Add cassava quantity in tonnes to cassava_inputs
        Schema::table('cassava_inputs', function (Blueprint $table) {
            $table->decimal('quantity_tonnes', 10, 3)->nullable()->after('quantity_kg');
            $table->decimal('cost_per_tonne', 10, 2)->nullable()->after('cost_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('gari_production_batches', function (Blueprint $table) {
            $table->dropColumn(['cassava_quantity_tonnes', 'cassava_cost_per_tonne']);
        });

        Schema::table('cassava_inputs', function (Blueprint $table) {
            $table->dropColumn(['quantity_tonnes', 'cost_per_tonne']);
        });
    }
};
