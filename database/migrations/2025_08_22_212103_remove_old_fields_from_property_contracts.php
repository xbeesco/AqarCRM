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
        Schema::table('property_contracts', function (Blueprint $table) {
            // حذف Foreign Key constraints أولاً
            $table->dropForeign(['owner_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['approved_by']);
            
            // حذف الفهارس القديمة
            $table->dropIndex(['owner_id']);
            $table->dropIndex(['contract_status']);
            $table->dropIndex(['start_date', 'end_date']);
            $table->dropIndex(['contract_status', 'start_date', 'end_date']);

            // حذف الحقول غير المطلوبة
            $table->dropColumn([
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
                'terminated_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_contracts', function (Blueprint $table) {
            // إعادة الحقول المحذوفة
            $table->string('contract_number', 50)->unique()->after('id');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade')->after('contract_number');
            $table->date('start_date')->after('duration_months');
            $table->date('end_date')->after('start_date');
            $table->enum('contract_status', ['draft', 'active', 'suspended', 'expired', 'terminated'])->default('draft')->after('end_date');
            $table->string('notary_number', 100)->nullable()->after('contract_status');
            $table->integer('payment_day')->default(1)->after('notary_number');
            $table->boolean('auto_renew')->default(false)->after('payment_day');
            $table->integer('notice_period_days')->default(30)->after('auto_renew');
            $table->text('terms_and_conditions')->nullable()->after('notice_period_days');
            $table->foreignId('created_by')->nullable()->constrained('users')->after('notes');
            $table->foreignId('approved_by')->nullable()->constrained('users')->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('terminated_reason')->nullable()->after('approved_at');
            $table->timestamp('terminated_at')->nullable()->after('terminated_reason');

            // إعادة الفهارس
            $table->index(['owner_id']);
            $table->index(['contract_status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['contract_status', 'start_date', 'end_date']);
        });
    }
};
