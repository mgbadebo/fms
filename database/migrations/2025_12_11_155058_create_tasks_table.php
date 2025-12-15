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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['FIELD', 'LIVESTOCK', 'EQUIPMENT', 'OTHER'])->default('FIELD');
            $table->foreignId('related_field_id')->nullable()->constrained('fields')->onDelete('set null');
            $table->foreignId('related_zone_id')->nullable()->constrained('zones')->onDelete('set null');
            $table->foreignId('related_crop_plan_id')->nullable()->constrained('crop_plans')->onDelete('set null');
            $table->foreignId('related_livestock_group_id')->nullable()->constrained('livestock_groups')->onDelete('set null');
            $table->dateTime('due_date')->nullable();
            $table->enum('priority', ['LOW', 'MEDIUM', 'HIGH'])->default('MEDIUM');
            $table->enum('status', ['PENDING', 'IN_PROGRESS', 'DONE', 'CANCELLED'])->default('PENDING');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
