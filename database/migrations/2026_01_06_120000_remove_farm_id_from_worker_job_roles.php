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
        // SQLite doesn't support dropping columns directly, so we need to recreate the table
        if (config('database.default') === 'sqlite') {
            Schema::dropIfExists('worker_job_roles');
            Schema::create('worker_job_roles', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique(); // Global unique code
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        } else {
            Schema::table('worker_job_roles', function (Blueprint $table) {
                // Drop foreign key constraint
                $table->dropForeign(['farm_id']);
                
                // Drop unique constraint on (farm_id, code)
                $table->dropUnique(['farm_id', 'code']);
                
                // Drop farm_id column
                $table->dropColumn('farm_id');
                
                // Add unique constraint on code (now global)
                $table->unique('code');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('worker_job_roles', function (Blueprint $table) {
            // Add farm_id back
            $table->foreignId('farm_id')->after('id')->constrained()->onDelete('cascade');
            
            // Drop unique constraint on code
            $table->dropUnique(['code']);
            
            // Add back unique constraint on (farm_id, code)
            $table->unique(['farm_id', 'code']);
        });
    }
};

