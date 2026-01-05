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
        Schema::table('bell_pepper_harvests', function (Blueprint $table) {
            // Add grade-specific weight columns
            $table->decimal('grade_a_kg', 10, 2)->default(0)->after('weight_kg');
            $table->decimal('grade_b_kg', 10, 2)->default(0)->after('grade_a_kg');
            $table->decimal('grade_c_kg', 10, 2)->default(0)->after('grade_b_kg');
            
            // Add harvest number to track which harvest in the cycle (1st, 2nd, 3rd, 4th)
            $table->integer('harvest_number')->nullable()->after('harvest_code');
            
            // Add harvester ID to track who recorded the harvest
            $table->foreignId('harvester_id')->nullable()->constrained('users')->onDelete('set null')->after('greenhouse_id');
            
            // Make weight_kg nullable (will be calculated from grades)
            $table->decimal('weight_kg', 10, 2)->nullable()->change();
            
            // Remove the grade enum column (we track all three grades separately)
            $table->dropColumn('grade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bell_pepper_harvests', function (Blueprint $table) {
            // Restore grade enum
            $table->enum('grade', ['A', 'B', 'C', 'MIXED'])->default('MIXED')->after('crates_count');
            
            // Make weight_kg required again
            $table->decimal('weight_kg', 10, 2)->nullable(false)->change();
            
            // Remove new columns
            $table->dropForeign(['harvester_id']);
            $table->dropColumn(['grade_a_kg', 'grade_b_kg', 'grade_c_kg', 'harvest_number', 'harvester_id']);
        });
    }
};
