<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gari_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->string('sale_code')->unique();
            $table->date('sale_date');
            
            // Customer Information
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null');
            $table->string('customer_name')->nullable(); // For walk-in customers
            $table->string('customer_contact')->nullable();
            $table->enum('customer_type', ['RETAIL', 'BULK_BUYER', 'DISTRIBUTOR', 'CATERING', 'HOTEL', 'OTHER'])->default('RETAIL');
            
            // Product Details
            $table->enum('gari_type', ['WHITE', 'YELLOW'])->default('WHITE');
            $table->enum('gari_grade', ['FINE', 'COARSE', 'MIXED'])->default('FINE');
            $table->enum('packaging_type', ['1KG_POUCH', '2KG_POUCH', '5KG_PACK', '50KG_BAG', 'BULK'])->default('1KG_POUCH');
            
            // Quantity & Pricing
            $table->decimal('quantity_kg', 10, 2);
            $table->integer('quantity_units')->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            
            // Financial
            $table->decimal('cost_per_kg', 10, 2)->nullable(); // From inventory
            $table->decimal('total_cost', 10, 2)->nullable();
            $table->decimal('gross_margin', 10, 2)->nullable(); // final_amount - total_cost
            $table->decimal('gross_margin_percent', 5, 2)->nullable(); // (margin / final_amount) * 100
            
            // Payment
            $table->enum('payment_method', ['CASH', 'TRANSFER', 'POS', 'CHEQUE', 'CREDIT'])->default('CASH');
            $table->enum('payment_status', ['PAID', 'PARTIAL', 'OUTSTANDING'])->default('PAID');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_outstanding', 10, 2)->default(0);
            
            // Channel/Sales Point
            $table->string('sales_channel')->nullable(); // e.g., "Warehouse", "Shop", "Distributor", "Short-let Supply"
            $table->string('sales_person')->nullable();
            
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['farm_id', 'sale_date']);
            $table->index('customer_type');
            $table->index('payment_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gari_sales');
    }
};

