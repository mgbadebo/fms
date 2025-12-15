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
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('livestock_group_id')->nullable()->constrained()->onDelete('set null');
            $table->string('tag_id')->unique();
            $table->enum('sex', ['MALE', 'FEMALE', 'UNKNOWN'])->default('UNKNOWN');
            $table->date('birth_date')->nullable();
            $table->enum('status', ['ACTIVE', 'SOLD', 'DEAD', 'CULLED'])->default('ACTIVE');
            $table->text('lineage_info')->nullable(); // JSON or text
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
