<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Convert sites.type from enum to string so it can reference site_types.code
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't enforce enum constraints at the database level
            // The column is already stored as TEXT, so no migration needed
            // However, we'll verify the column exists
            if (!Schema::hasColumn('sites', 'type')) {
                Schema::table('sites', function (Blueprint $table) {
                    $table->string('type', 50)->default('farmland')->after('code');
                });
            }
        } else {
            // For MySQL/MariaDB, use MODIFY COLUMN to change enum to varchar
            if (Schema::hasColumn('sites', 'type')) {
                DB::statement("ALTER TABLE sites MODIFY COLUMN type VARCHAR(50) NOT NULL DEFAULT 'farmland'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite: nothing to revert, enum constraint wasn't enforced anyway
        } else {
            // Convert back to enum for MySQL/MariaDB if needed (optional)
            if (Schema::hasColumn('sites', 'type')) {
                DB::statement("ALTER TABLE sites MODIFY COLUMN type ENUM('farmland', 'warehouse', 'factory', 'greenhouse', 'estate') NOT NULL DEFAULT 'farmland'");
            }
        }
    }
};
