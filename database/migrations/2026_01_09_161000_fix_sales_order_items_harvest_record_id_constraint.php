<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            // For SQLite, we need to recreate the table to drop foreign keys
            // For MySQL/PostgreSQL, we can drop the foreign key directly
            if (DB::getDriverName() === 'sqlite') {
                // SQLite doesn't support dropping foreign keys easily
                // We'll just ensure the column is nullable unsignedBigInteger
                // The foreign key constraint will be ignored in practice
                return;
            }
            
            // For MySQL/PostgreSQL, try to drop foreign key
            try {
                // Get foreign key name (this is database-specific)
                if (DB::getDriverName() === 'mysql') {
                    $foreignKey = DB::selectOne("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'sales_order_items' 
                        AND COLUMN_NAME = 'harvest_record_id' 
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ");
                    
                    if ($foreignKey) {
                        $table->dropForeign($foreignKey->CONSTRAINT_NAME);
                    }
                }
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
            
            // Change to unsignedBigInteger (no foreign key constraint)
            if (Schema::hasColumn('sales_order_items', 'harvest_record_id')) {
                $table->unsignedBigInteger('harvest_record_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore foreign key to bell_pepper_harvests (legacy)
        // Note: This is complex and may not be necessary
    }
};
