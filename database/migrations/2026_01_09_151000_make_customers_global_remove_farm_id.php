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
        Schema::table('customers', function (Blueprint $table) {
            // Remove farm_id foreign key constraint and column
            if (Schema::hasColumn('customers', 'farm_id')) {
                $table->dropForeign(['farm_id']);
                $table->dropColumn('farm_id');
            }
            
            // Customers are now global, not tied to any farm
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Add farm_id back
            $table->foreignId('farm_id')->nullable()->constrained('farms')->onDelete('cascade')->after('id');
        });
    }
};
