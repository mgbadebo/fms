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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['INCOME', 'EXPENSE', 'LOAN', 'REPAYMENT'])->default('EXPENSE');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->string('currency')->default('USD');
            $table->dateTime('occurred_at');
            $table->string('reference_type')->nullable(); // Polymorphic type
            $table->unsignedBigInteger('reference_id')->nullable(); // Polymorphic ID
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
        Schema::dropIfExists('financial_transactions');
    }
};
