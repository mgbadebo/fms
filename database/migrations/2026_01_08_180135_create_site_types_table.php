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
        Schema::create('site_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., 'farmland', 'warehouse', 'factory', 'greenhouse'
            $table->string('name'); // Display name
            $table->string('code_prefix', 10)->nullable(); // Prefix for generating site codes (e.g., 'FL', 'WH', 'FT', 'GH')
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_types');
    }
};
