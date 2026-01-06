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
        Schema::table('gari_production_batches', function (Blueprint $table) {
            $table->foreignId('factory_id')->nullable()->after('farm_id')->constrained('factories')->onDelete('set null');
            $table->index('factory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gari_production_batches', function (Blueprint $table) {
            $table->dropForeign(['factory_id']);
            $table->dropIndex(['factory_id']);
            $table->dropColumn('factory_id');
        });
    }
};
