<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add 'estate' to the sites type enum and ensure location_id is removed
     */
    public function up(): void
    {
        // Add 'estate' to the type enum if using SQLite, we need to recreate the column
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN for enum, so we need to recreate
            // But since we're using string type in the migration, we can just update the constraint
            // For SQLite, enum is stored as string, so we just need to ensure validation allows it
        } else {
            // For MySQL/PostgreSQL, alter the enum
            DB::statement("ALTER TABLE sites MODIFY COLUMN type ENUM('farmland', 'warehouse', 'factory', 'greenhouse', 'estate') DEFAULT 'farmland'");
        }

        // Ensure location_id is removed (should already be done by previous migration, but double-check)
        if (Schema::hasColumn('sites', 'location_id')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'estate' from enum
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE sites MODIFY COLUMN type ENUM('farmland', 'warehouse', 'factory', 'greenhouse') DEFAULT 'farmland'");
        }
    }
};
