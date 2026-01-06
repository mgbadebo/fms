<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite, we need to recreate the table
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN, so we need to recreate the table
            // But since the model always sets code = greenhouse_code, we can skip this
            // The model will ensure code is never null
            return;
        }
        
        // For MySQL/PostgreSQL, we can alter the column
        Schema::table('greenhouses', function (Blueprint $table) {
            // Make code nullable since we're using greenhouse_code now
            // But keep it for backward compatibility
            $table->string('code')->nullable()->change();
        });
        
        // Try to drop unique constraint if it exists (different DBs have different index names)
        try {
            Schema::table('greenhouses', function (Blueprint $table) {
                $table->dropUnique(['code']);
            });
        } catch (\Exception $e) {
            // Try alternative index names
            try {
                DB::statement('DROP INDEX IF EXISTS greenhouses_code_unique');
            } catch (\Exception $e2) {
                // Index might not exist, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('greenhouses', function (Blueprint $table) {
            // Restore unique constraint and make NOT NULL
            $table->string('code')->nullable(false)->change();
            try {
                $table->unique('code');
            } catch (\Exception $e) {
                // Constraint might already exist
            }
        });
    }
};
