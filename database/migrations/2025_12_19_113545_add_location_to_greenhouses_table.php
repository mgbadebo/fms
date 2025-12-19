<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('greenhouses', function (Blueprint $table) {
            // Remove old text location field if it exists
            if (Schema::hasColumn('greenhouses', 'location')) {
                $table->dropColumn('location');
            }
            // Add location_id foreign key
            $table->foreignId('location_id')->nullable()->after('amortization_cycles')->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('greenhouses', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
            $table->text('location')->nullable();
        });
    }
};
