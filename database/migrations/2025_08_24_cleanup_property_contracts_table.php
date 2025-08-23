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
        // حذف foreign keys أولاً باستخدام SQL مباشرة
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        Schema::table('property_contracts', function (Blueprint $table) {
            // حذف الأعمدة غير المطلوبة واحدة واحدة
            $columnsToRemove = [
                'contract_number',
                'owner_id',
                'start_date',
                'end_date',
                'contract_status',
                'notary_number',
                'payment_day',
                'auto_renew',
                'notice_period_days',
                'terms_and_conditions',
                'created_by',
                'approved_by',
                'approved_at',
                'terminated_reason',
                'terminated_at',
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('property_contracts', $column)) {
                    try {
                        $table->dropColumn($column);
                    } catch (\Exception $e) {
                        // Skip if cannot drop
                    }
                }
            }
        });
        
        // إضافة الحقول المطلوبة
        Schema::table('property_contracts', function (Blueprint $table) {
            if (!Schema::hasColumn('property_contracts', 'contract_date')) {
                $table->date('contract_date')->nullable()->after('property_id');
            }
            
            if (!Schema::hasColumn('property_contracts', 'payment_frequency')) {
                $table->string('payment_frequency')->default('monthly')->after('commission_rate');
            }
            
            if (!Schema::hasColumn('property_contracts', 'payments_count')) {
                $table->integer('payments_count')->nullable()->after('payment_frequency');
            }
            
            if (!Schema::hasColumn('property_contracts', 'contract_file')) {
                $table->string('contract_file')->nullable()->after('payments_count');
            }
        });
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // في حالة التراجع، لا نعيد الحقول القديمة
        Schema::table('property_contracts', function (Blueprint $table) {
            if (Schema::hasColumn('property_contracts', 'contract_date')) {
                $table->dropColumn('contract_date');
            }
            if (Schema::hasColumn('property_contracts', 'payment_frequency')) {
                $table->dropColumn('payment_frequency');
            }
            if (Schema::hasColumn('property_contracts', 'payments_count')) {
                $table->dropColumn('payments_count');
            }
            if (Schema::hasColumn('property_contracts', 'contract_file')) {
                $table->dropColumn('contract_file');
            }
        });
    }
};