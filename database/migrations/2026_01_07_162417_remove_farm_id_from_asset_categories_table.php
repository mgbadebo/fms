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
        // Drop unique(farm_id, code) and farm_id column; make code globally unique
        Schema::table('asset_categories', function (Blueprint $table) {
            // Try dropping the composite unique index if it exists
            try {
                $table->dropUnique(['farm_id', 'code']);
            } catch (\Throwable $e) {
                // ignore - index name may differ; attempt by name
                try {
                    $table->dropUnique('asset_categories_farm_id_code_unique');
                } catch (\Throwable $e2) {
                    // ignore
                }
            }
        });

        // Remove foreign key and column farm_id in a separate call for broader DB compatibility
        Schema::table('asset_categories', function (Blueprint $table) {
            // Drop FK if exists
            try {
                $table->dropForeign(['farm_id']);
            } catch (\Throwable $e) {
                // ignore
            }
            // Drop index if exists
            try {
                $table->dropIndex(['farm_id']);
            } catch (\Throwable $e) {
                // ignore
            }
            // Finally drop the column
            if (Schema::hasColumn('asset_categories', 'farm_id')) {
                $table->dropColumn('farm_id');
            }
        });

        // Add global unique index on code
        Schema::table('asset_categories', function (Blueprint $table) {
            try {
                $table->unique('code');
            } catch (\Throwable $e) {
                // ignore if already unique
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_categories', function (Blueprint $table) {
            // Drop global unique on code
            try {
                $table->dropUnique(['code']);
            } catch (\Throwable $e) {
                try {
                    $table->dropUnique('asset_categories_code_unique');
                } catch (\Throwable $e2) {
                    // ignore
                }
            }
        });

        Schema::table('asset_categories', function (Blueprint $table) {
            // Recreate farm_id (nullable to avoid data loss)
            if (!Schema::hasColumn('asset_categories', 'farm_id')) {
                $table->foreignId('farm_id')->nullable()->after('id')->constrained()->onDelete('cascade');
            }
            // Restore unique(farm_id, code)
            try {
                $table->unique(['farm_id', 'code']);
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
