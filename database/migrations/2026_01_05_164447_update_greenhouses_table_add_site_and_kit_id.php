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
        Schema::table('greenhouses', function (Blueprint $table) {
            // Add site_id (link to Site where type is 'greenhouse')
            $table->foreignId('site_id')->nullable()->after('farm_id')->constrained('sites')->onDelete('cascade');
            
            // Add kit_id/kit_number
            $table->string('kit_id')->nullable()->after('code');
            $table->string('kit_number')->nullable()->after('kit_id'); // Alternative name
            
            // Add index for kit_id lookups
            $table->index('kit_id');
            $table->index('site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('greenhouses', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropIndex(['kit_id']);
            $table->dropIndex(['site_id']);
            $table->dropColumn(['site_id', 'kit_id', 'kit_number']);
        });
    }
};
