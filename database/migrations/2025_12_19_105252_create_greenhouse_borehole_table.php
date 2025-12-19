<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greenhouse_borehole', function (Blueprint $table) {
            $table->id();
            $table->foreignId('greenhouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('borehole_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Ensure a greenhouse can only be linked to a borehole once
            $table->unique(['greenhouse_id', 'borehole_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greenhouse_borehole');
    }
};
