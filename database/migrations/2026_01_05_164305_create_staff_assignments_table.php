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
        Schema::create('staff_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->onDelete('cascade');
            $table->string('assignable_type'); // Site, Factory, Greenhouse, FarmZone
            $table->unsignedBigInteger('assignable_id');
            $table->string('role')->nullable(); // e.g., 'supervisor', 'operator', 'manager'
            $table->text('core_responsibilities')->nullable();
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable(); // NULL means currently assigned
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['assignable_type', 'assignable_id']);
            $table->index(['worker_id', 'is_current']);
            $table->index('assigned_from');
            $table->index('assigned_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_assignments');
    }
};
