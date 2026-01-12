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
        // For MySQL, modify the enum to include INVOICED and PAID
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('DRAFT', 'CONFIRMED', 'DISPATCHED', 'INVOICED', 'PAID', 'COMPLETED', 'CANCELLED') DEFAULT 'DRAFT'");
        }
        // For PostgreSQL, enum modification is more complex - would need to create new type and migrate
        // For now, we'll handle INVOICED/PAID in application logic if enum doesn't support it
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE sales_orders MODIFY COLUMN status ENUM('DRAFT', 'CONFIRMED', 'DISPATCHED', 'COMPLETED', 'CANCELLED') DEFAULT 'DRAFT'");
        }
    }
};
