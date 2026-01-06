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
        Schema::create('asset_insurance_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->constrained()->onDelete('cascade');
            $table->string('insurer_name');
            $table->string('policy_number');
            $table->date('coverage_start');
            $table->date('coverage_end');
            $table->decimal('insured_value', 14, 2);
            $table->char('currency', 3)->default('NGN');
            $table->decimal('premium', 14, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['farm_id', 'asset_id']);
            $table->index('coverage_end'); // For finding expiring policies
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_insurance_policies');
    }
};
