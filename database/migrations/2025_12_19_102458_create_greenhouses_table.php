<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greenhouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->string('name');
            $table->decimal('size_sqm', 10, 2); // Size in square metres
            $table->date('built_date'); // When greenhouse was built
            $table->decimal('construction_cost', 12, 2)->default(0); // Total construction cost
            $table->integer('amortization_cycles')->default(6); // Number of cycles to amortize (default 6)
            $table->text('location')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greenhouses');
    }
};
