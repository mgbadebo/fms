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
        Schema::table('customers', function (Blueprint $table) {
            // Add new fields if they don't exist
            if (!Schema::hasColumn('customers', 'customer_type')) {
                $table->enum('customer_type', ['INDIVIDUAL', 'BUSINESS', 'DISTRIBUTOR', 'RETAILER', 'EXPORTER'])->nullable()->after('name');
            }
            if (!Schema::hasColumn('customers', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('customer_type');
            }
            if (!Schema::hasColumn('customers', 'address_line1')) {
                $table->string('address_line1')->nullable()->after('address');
            }
            if (!Schema::hasColumn('customers', 'address_line2')) {
                $table->string('address_line2')->nullable()->after('address_line1');
            }
            if (!Schema::hasColumn('customers', 'city')) {
                $table->string('city')->nullable()->after('address_line2');
            }
            if (!Schema::hasColumn('customers', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('customers', 'country')) {
                $table->string('country')->nullable()->after('state');
            }
            if (!Schema::hasColumn('customers', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('country');
            }
            if (!Schema::hasColumn('customers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('is_active');
            }
            
            // Add indexes
            if (!Schema::hasColumn('customers', 'name')) {
                $table->index('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_type',
                'contact_name',
                'address_line1',
                'address_line2',
                'city',
                'state',
                'country',
                'is_active',
                'created_by',
            ]);
        });
    }
};
