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
        // Check if fuel_logs table exists, if not create it
        if (!Schema::hasTable('fuel_logs')) {
            Schema::create('fuel_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('farm_id')->constrained()->onDelete('cascade');
                $table->foreignId('asset_id')->constrained()->onDelete('cascade');
                $table->dateTime('filled_at');
                $table->decimal('quantity', 10, 2);
                $table->enum('unit', ['LITRE', 'GALLON'])->default('LITRE');
                $table->decimal('cost', 14, 2)->nullable();
                $table->char('currency', 3)->default('NGN');
                $table->string('supplier')->nullable();
                $table->foreignId('operator_id')->nullable()->constrained('users')->onDelete('set null');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['farm_id', 'asset_id']);
                $table->index('filled_at');
            });
        } else {
            // Update existing table
            Schema::table('fuel_logs', function (Blueprint $table) {
                // Ensure all required columns exist
                if (!Schema::hasColumn('fuel_logs', 'unit')) {
                    $table->enum('unit', ['LITRE', 'GALLON'])->default('LITRE')->after('quantity');
                }
                if (!Schema::hasColumn('fuel_logs', 'currency')) {
                    $table->char('currency', 3)->default('NGN')->after('cost');
                }
                if (!Schema::hasColumn('fuel_logs', 'supplier')) {
                    $table->string('supplier')->nullable()->after('currency');
                }
                if (!Schema::hasColumn('fuel_logs', 'operator_id')) {
                    $table->foreignId('operator_id')->nullable()->after('supplier')->constrained('users')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('fuel_logs')) {
            Schema::table('fuel_logs', function (Blueprint $table) {
                if (Schema::hasColumn('fuel_logs', 'operator_id')) {
                    $table->dropForeign(['operator_id']);
                    $table->dropColumn(['unit', 'currency', 'supplier', 'operator_id']);
                }
            });
        }
    }
};
