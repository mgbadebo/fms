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
        Schema::create('label_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique(); // e.g., "HARVEST_LOT_LABEL"
            $table->enum('target_type', ['HARVEST_LOT', 'STORAGE_UNIT', 'SALES_ORDER'])->default('HARVEST_LOT');
            $table->enum('template_engine', ['ZPL', 'BLADE', 'RAW'])->default('BLADE');
            $table->text('template_body'); // Template with placeholders like {{traceability_id}}, {{net_weight}}
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_templates');
    }
};
