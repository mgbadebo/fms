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
        Schema::create('breeding_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('livestock_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('animal_id')->nullable()->constrained()->onDelete('set null');
            $table->date('event_date');
            $table->enum('type', ['MATING', 'AI', 'PREG_CHECK', 'BIRTH']);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('breeding_events');
    }
};
