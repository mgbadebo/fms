<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bell_pepper_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('bell_pepper_harvest_id')->nullable()->constrained()->onDelete('set null');
            $table->string('sale_code')->unique();
            $table->date('sale_date');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('customer_name')->nullable();
            $table->string('customer_contact')->nullable();
            $table->enum('customer_type', ['RETAIL', 'BULK_BUYER', 'DISTRIBUTOR', 'CATERING', 'HOTEL', 'OTHER'])->default('RETAIL');
            $table->decimal('quantity_kg', 10, 2);
            $table->integer('crates_count')->default(0);
            $table->enum('grade', ['A', 'B', 'C', 'MIXED'])->default('MIXED');
            $table->decimal('unit_price', 10, 2); // Price per kg
            $table->decimal('total_amount', 12, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('final_amount', 12, 2);
            $table->decimal('logistics_cost', 10, 2)->default(0); // Transport cost
            $table->enum('payment_method', ['CASH', 'TRANSFER', 'POS', 'CHEQUE', 'CREDIT'])->default('CASH');
            $table->enum('payment_status', ['PAID', 'PARTIAL', 'OUTSTANDING'])->default('OUTSTANDING');
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_outstanding', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bell_pepper_sales');
    }
};
