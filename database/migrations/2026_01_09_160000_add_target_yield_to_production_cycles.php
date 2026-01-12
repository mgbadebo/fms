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
        Schema::table('greenhouse_production_cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('greenhouse_production_cycles', 'target_total_yield_kg')) {
                $table->decimal('target_total_yield_kg', 14, 2)->nullable()->after('notes');
            }
            if (!Schema::hasColumn('greenhouse_production_cycles', 'target_yield_per_plant_kg')) {
                $table->decimal('target_yield_per_plant_kg', 10, 3)->nullable()->after('target_total_yield_kg');
            }
            if (!Schema::hasColumn('greenhouse_production_cycles', 'target_grade_a_pct')) {
                $table->decimal('target_grade_a_pct', 5, 2)->nullable()->after('target_yield_per_plant_kg');
            }
            if (!Schema::hasColumn('greenhouse_production_cycles', 'target_grade_b_pct')) {
                $table->decimal('target_grade_b_pct', 5, 2)->nullable()->after('target_grade_a_pct');
            }
            if (!Schema::hasColumn('greenhouse_production_cycles', 'target_grade_c_pct')) {
                $table->decimal('target_grade_c_pct', 5, 2)->nullable()->after('target_grade_b_pct');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('greenhouse_production_cycles', function (Blueprint $table) {
            $table->dropColumn([
                'target_total_yield_kg',
                'target_yield_per_plant_kg',
                'target_grade_a_pct',
                'target_grade_b_pct',
                'target_grade_c_pct',
            ]);
        });
    }
};
