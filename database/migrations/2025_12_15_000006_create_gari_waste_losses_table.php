<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gari_waste_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->onDelete('cascade');
            $table->foreignId('gari_production_batch_id')->nullable()->constrained('gari_production_batches')->onDelete('set null');
            $table->foreignId('gari_inventory_id')->nullable()->constrained('gari_inventory')->onDelete('set null');
            
            $table->date('loss_date');
            $table->enum('loss_type', ['SPOILAGE', 'MOISTURE_DAMAGE', 'SPILLAGE', 'REJECTED_BATCH', 'CUSTOMER_RETURN', 'THEFT', 'OTHER'])->default('SPOILAGE');
            
            $table->enum('gari_type', ['WHITE', 'YELLOW'])->nullable();
            $table->enum('packaging_type', ['1KG_POUCH', '2KG_POUCH', '5KG_PACK', '50KG_BAG', 'BULK'])->nullable();
            
            $table->decimal('quantity_kg', 10, 2);
            $table->integer('quantity_units')->default(0);
            $table->decimal('cost_per_kg', 10, 2)->nullable();
            $table->decimal('total_loss_value', 10, 2)->nullable();
            
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['farm_id', 'loss_date']);
            $table->index('loss_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gari_waste_losses');
    }
};

