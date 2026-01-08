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
        Schema::table('sites', function (Blueprint $table) {
            if (!Schema::hasColumn('sites', 'asset_id')) {
                $table->foreignId('asset_id')->nullable()->after('farm_id')->constrained('assets')->onDelete('set null');
                $table->index('asset_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            if (Schema::hasColumn('sites', 'asset_id')) {
                $table->dropForeign(['asset_id']);
                $table->dropIndex(['asset_id']);
                $table->dropColumn('asset_id');
            }
        });
    }
};
