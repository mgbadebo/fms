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
        Schema::create('printed_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('label_template_id')->constrained()->onDelete('restrict');
            $table->string('target_type'); // Polymorphic type
            $table->unsignedBigInteger('target_id'); // Polymorphic ID
            $table->dateTime('printed_at');
            $table->string('printer_name')->nullable();
            $table->text('payload_sent')->nullable(); // ZPL/EPL or rendered string
            $table->timestamps();
            
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('printed_labels');
    }
};
