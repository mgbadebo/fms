<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bell_pepper_harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('bell_pepper_cycle_id')->constrained()->onDelete('cascade');
            $table->foreignId('greenhouse_id')->constrained()->onDelete('cascade');
            $table->string('harvest_code')->unique();
            $table->date('harvest_date');
            $table->decimal('weight_kg', 10, 2); // Total weight harvested
            $table->integer('crates_count')->default(0); // Number of crates (9-10kg each)
            $table->enum('grade', ['A', 'B', 'C', 'MIXED'])->default('MIXED');
            $table->enum('status', ['HARVESTED', 'PACKED', 'IN_TRANSIT', 'DELIVERED', 'SOLD'])->default('HARVESTED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bell_pepper_harvests');
    }
};
