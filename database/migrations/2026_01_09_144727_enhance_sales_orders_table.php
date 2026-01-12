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
        Schema::table('sales_orders', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (!Schema::hasColumn('sales_orders', 'site_id')) {
                $table->foreignId('site_id')->nullable()->constrained('sites')->onDelete('set null')->after('farm_id');
            }
            if (!Schema::hasColumn('sales_orders', 'currency')) {
                $table->char('currency', 3)->default('USD')->after('status');
            }
            if (!Schema::hasColumn('sales_orders', 'subtotal')) {
                $table->decimal('subtotal', 14, 2)->default(0)->after('currency');
            }
            if (!Schema::hasColumn('sales_orders', 'discount_total')) {
                $table->decimal('discount_total', 14, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('sales_orders', 'tax_total')) {
                $table->decimal('tax_total', 14, 2)->default(0)->after('discount_total');
            }
            if (!Schema::hasColumn('sales_orders', 'total_amount')) {
                $table->decimal('total_amount', 14, 2)->default(0)->after('tax_total');
            }
            if (!Schema::hasColumn('sales_orders', 'payment_status')) {
                $table->enum('payment_status', ['UNPAID', 'PART_PAID', 'PAID'])->default('UNPAID')->after('total_amount');
            }
            if (!Schema::hasColumn('sales_orders', 'due_date')) {
                $table->date('due_date')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('sales_orders', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('notes');
            }
            
            // Update status enum - Note: Laravel doesn't support modifying enum easily
            // The existing enum has: DRAFT, CONFIRMED, DISPATCHED, COMPLETED, CANCELLED
            // We'll use COMPLETED for PAID status, or add INVOICED/PAID in code validation
            // For now, we'll use the existing statuses and add INVOICED/PAID via validation
            
            // Add indexes
            $table->index(['farm_id', 'status']);
            // Ensure unique order_number per farm (modify if needed)
            if (Schema::hasColumn('sales_orders', 'order_number')) {
                // Drop existing unique if exists, we'll recreate with farm_id
                $table->dropUnique(['order_number']);
            }
        });
        
        // Add unique constraint for order_number per farm
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->unique(['farm_id', 'order_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropUnique(['farm_id', 'order_number']);
            $table->dropColumn([
                'site_id',
                'currency',
                'subtotal',
                'discount_total',
                'tax_total',
                'total_amount',
                'payment_status',
                'due_date',
                'created_by',
            ]);
            // Restore original unique on order_number
            $table->unique('order_number');
        });
    }
};
