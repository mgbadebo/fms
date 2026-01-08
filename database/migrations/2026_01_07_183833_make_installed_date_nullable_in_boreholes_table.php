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
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            // SQLite doesn't support ALTER COLUMN directly, so we skip this for SQLite
            // The model will handle null values for installed_date
            // Note: If you need to enforce this at DB level for SQLite, you'd need to recreate the table
            return;
        }
        
        Schema::table('boreholes', function (Blueprint $table) {
            $table->date('installed_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'sqlite') {
            return;
        }
        
        Schema::table('boreholes', function (Blueprint $table) {
            $table->date('installed_date')->nullable(false)->change();
        });
    }
};
