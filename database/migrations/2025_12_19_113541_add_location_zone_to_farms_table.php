<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('description')->constrained('locations')->onDelete('set null');
            $table->foreignId('admin_zone_id')->nullable()->after('location_id')->constrained('admin_zones')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropForeign(['admin_zone_id']);
            $table->dropColumn(['location_id', 'admin_zone_id']);
        });
    }
};
