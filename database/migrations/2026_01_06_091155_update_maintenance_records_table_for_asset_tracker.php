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
        Schema::table('maintenance_records', function (Blueprint $table) {
            // Update type enum to match requirements
            $table->enum('type', ['SERVICE', 'REPAIR', 'INSPECTION'])->default('SERVICE')->change();
            
            // Add vendor_name
            $table->string('vendor_name')->nullable()->after('type');
            
            // Ensure cost and currency exist (they should already)
            if (!Schema::hasColumn('maintenance_records', 'cost')) {
                $table->decimal('cost', 14, 2)->nullable()->after('vendor_name');
            }
            if (!Schema::hasColumn('maintenance_records', 'currency')) {
                $table->char('currency', 3)->default('NGN')->after('cost');
            }
            
            // Add odometer_or_hours
            $table->decimal('odometer_or_hours', 10, 2)->nullable()->after('currency');
            
            // Update description/notes
            if (!Schema::hasColumn('maintenance_records', 'description')) {
                $table->text('description')->nullable()->after('odometer_or_hours');
            }
            
            // Add parts_used JSON
            $table->json('parts_used')->nullable()->after('description');
            
            // Add created_by user reference
            if (!Schema::hasColumn('maintenance_records', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('parts_used')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_records', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn([
                'vendor_name',
                'odometer_or_hours',
                'parts_used',
                'created_by',
            ]);
        });
    }
};
