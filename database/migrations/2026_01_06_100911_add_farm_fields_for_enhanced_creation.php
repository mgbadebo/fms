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
        Schema::table('farms', function (Blueprint $table) {
            // Add farm_code if it doesn't exist
            if (!Schema::hasColumn('farms', 'farm_code')) {
                $table->string('farm_code')->unique()->nullable()->after('id');
            }
            
            // Add legal_name if it doesn't exist
            if (!Schema::hasColumn('farms', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('name');
            }
            
            // Add farm_type if it doesn't exist
            if (!Schema::hasColumn('farms', 'farm_type')) {
                $table->enum('farm_type', ['CROP', 'LIVESTOCK', 'MIXED', 'AQUACULTURE', 'HORTICULTURE'])->nullable()->after('legal_name');
            }
            
            // Add country if it doesn't exist
            if (!Schema::hasColumn('farms', 'country')) {
                $table->string('country', 100)->nullable()->after('farm_type');
            }
            
            // Add state if it doesn't exist
            if (!Schema::hasColumn('farms', 'state')) {
                $table->string('state', 100)->nullable()->after('country');
            }
            
            // Add town if it doesn't exist
            if (!Schema::hasColumn('farms', 'town')) {
                $table->string('town', 100)->nullable()->after('state');
            }
            
            // Add default_currency if it doesn't exist
            if (!Schema::hasColumn('farms', 'default_currency')) {
                $table->char('default_currency', 3)->default('NGN')->after('town');
            }
            
            // Add default_unit_system if it doesn't exist
            if (!Schema::hasColumn('farms', 'default_unit_system')) {
                $table->enum('default_unit_system', ['METRIC', 'IMPERIAL'])->default('METRIC')->after('default_currency');
            }
            
            // Add default_timezone if it doesn't exist
            if (!Schema::hasColumn('farms', 'default_timezone')) {
                $table->string('default_timezone')->default('Africa/Lagos')->after('default_unit_system');
            }
            
            // Add accounting_method if it doesn't exist
            if (!Schema::hasColumn('farms', 'accounting_method')) {
                $table->enum('accounting_method', ['CASH', 'ACCRUAL'])->default('ACCRUAL')->after('default_timezone');
            }
            
            // Add status if it doesn't exist (replace is_active if needed)
            if (!Schema::hasColumn('farms', 'status')) {
                $table->enum('status', ['ACTIVE', 'INACTIVE', 'ARCHIVED'])->default('ACTIVE')->after('accounting_method');
            }
            
            // Add created_by if it doesn't exist
            if (!Schema::hasColumn('farms', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
            
            // Rename metadata to meta if needed, or add meta if metadata doesn't exist
            if (!Schema::hasColumn('farms', 'meta') && !Schema::hasColumn('farms', 'metadata')) {
                $table->json('meta')->nullable()->after('created_by');
            } elseif (Schema::hasColumn('farms', 'metadata') && !Schema::hasColumn('farms', 'meta')) {
                // Keep metadata, but also add meta for consistency
                $table->json('meta')->nullable()->after('created_by');
            }
        });
        
        // Add indexes
        Schema::table('farms', function (Blueprint $table) {
            if (!Schema::hasColumn('farms', 'farm_code')) {
                $table->index('farm_code');
            }
            if (Schema::hasColumn('farms', 'country')) {
                $table->index('country');
            }
            if (Schema::hasColumn('farms', 'state')) {
                $table->index('state');
            }
            if (Schema::hasColumn('farms', 'status')) {
                $table->index('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farms', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['farm_code']);
            $table->dropIndex(['country']);
            $table->dropIndex(['state']);
            $table->dropIndex(['status']);
            
            // Drop foreign key
            if (Schema::hasColumn('farms', 'created_by')) {
                $table->dropForeign(['created_by']);
            }
            
            // Drop columns
            $columns = [
                'farm_code',
                'legal_name',
                'farm_type',
                'country',
                'state',
                'town',
                'default_currency',
                'default_unit_system',
                'default_timezone',
                'accounting_method',
                'status',
                'created_by',
                'meta',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('farms', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
