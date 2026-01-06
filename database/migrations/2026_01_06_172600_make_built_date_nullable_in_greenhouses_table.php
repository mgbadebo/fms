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
        // For SQLite, we skip since it doesn't support ALTER COLUMN easily
        // The model will ensure built_date is always set
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        // For MySQL/PostgreSQL, make built_date nullable
        Schema::table('greenhouses', function (Blueprint $table) {
            $table->date('built_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'sqlite') {
            return;
        }
        
        Schema::table('greenhouses', function (Blueprint $table) {
            $table->date('built_date')->nullable(false)->change();
        });
    }
};

