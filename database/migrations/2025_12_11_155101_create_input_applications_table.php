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
        Schema::create('input_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('input_item_id')->constrained()->onDelete('restrict');
            $table->foreignId('field_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('zone_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('crop_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('livestock_group_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('applied_at');
            $table->decimal('quantity', 10, 2);
            $table->string('unit');
            $table->string('method')->nullable(); // e.g., SPRAY, BROADCAST, INJECTION
            $table->foreignId('operator_id')->nullable()->constrained('users')->onDelete('set null');
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
        Schema::dropIfExists('input_applications');
    }
};
