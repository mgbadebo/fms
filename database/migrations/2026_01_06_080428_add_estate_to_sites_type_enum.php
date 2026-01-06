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
     * Add 'estate' to the sites type enum
     */
    public function up(): void
    {
        // For SQLite, enum is stored as string, so validation handles it
        // For MySQL/PostgreSQL, alter the enum
        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement("ALTER TABLE sites MODIFY COLUMN type ENUM('farmland', 'warehouse', 'factory', 'greenhouse', 'estate') DEFAULT 'farmland'");
            } catch (\Exception $e) {
                // If enum modification fails, it might already be updated or using a different DB
                // The validation in the controller will handle the constraint
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'estate' from enum
        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement("ALTER TABLE sites MODIFY COLUMN type ENUM('farmland', 'warehouse', 'factory', 'greenhouse') DEFAULT 'farmland'");
            } catch (\Exception $e) {
                // Ignore errors on rollback
            }
        }
    }
};
