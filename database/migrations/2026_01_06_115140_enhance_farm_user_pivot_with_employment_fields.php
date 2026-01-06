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
        Schema::table('farm_user', function (Blueprint $table) {
            // Add employment fields if they don't exist
            if (!Schema::hasColumn('farm_user', 'membership_status')) {
                $table->enum('membership_status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE')->after('role');
            }
            if (!Schema::hasColumn('farm_user', 'employment_category')) {
                $table->enum('employment_category', ['PERMANENT', 'CASUAL', 'CONTRACTOR', 'SEASONAL'])->nullable()->after('membership_status');
            }
            if (!Schema::hasColumn('farm_user', 'pay_type')) {
                $table->enum('pay_type', ['MONTHLY', 'DAILY', 'HOURLY', 'TASK'])->nullable()->after('employment_category');
            }
            if (!Schema::hasColumn('farm_user', 'pay_rate')) {
                $table->decimal('pay_rate', 14, 2)->nullable()->after('pay_type');
            }
            if (!Schema::hasColumn('farm_user', 'start_date')) {
                $table->date('start_date')->nullable()->after('pay_rate');
            }
            if (!Schema::hasColumn('farm_user', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
            if (!Schema::hasColumn('farm_user', 'notes')) {
                $table->text('notes')->nullable()->after('end_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('farm_user', function (Blueprint $table) {
            $columns = [
                'membership_status',
                'employment_category',
                'pay_type',
                'pay_rate',
                'start_date',
                'end_date',
                'notes',
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('farm_user', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
