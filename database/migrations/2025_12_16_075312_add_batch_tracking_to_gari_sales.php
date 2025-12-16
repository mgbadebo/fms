<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gari_sales', function (Blueprint $table) {
            $table->foreignId('gari_production_batch_id')->nullable()->after('farm_id')->constrained('gari_production_batches')->onDelete('set null');
            $table->foreignId('gari_inventory_id')->nullable()->after('gari_production_batch_id')->constrained('gari_inventory')->onDelete('set null');
            $table->index('gari_production_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('gari_sales', function (Blueprint $table) {
            $table->dropForeign(['gari_production_batch_id']);
            $table->dropForeign(['gari_inventory_id']);
            $table->dropColumn(['gari_production_batch_id', 'gari_inventory_id']);
        });
    }
};
