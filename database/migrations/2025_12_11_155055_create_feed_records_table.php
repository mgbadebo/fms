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
        Schema::create('feed_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('livestock_group_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('animal_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('recorded_at');
            $table->string('feed_item');
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('kg');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feed_records');
    }
};
