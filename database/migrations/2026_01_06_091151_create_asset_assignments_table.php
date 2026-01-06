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
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('assigned_to_type'); // Morph type: App\Models\User, App\Models\Worker, etc.
            $table->unsignedBigInteger('assigned_to_id');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('assigned_at');
            $table->dateTime('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'asset_id']);
            $table->index(['assigned_to_type', 'assigned_to_id']);
            $table->index(['asset_id', 'returned_at']); // For finding active assignments
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
