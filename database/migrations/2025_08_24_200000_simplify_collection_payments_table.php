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
        // تبسيط جدول دفعات التحصيل ليطابق النظام القديم
        Schema::table('collection_payments', function (Blueprint $table) {
            // إضافة حقل حالة التحصيل البسيط
            $table->enum('collection_status', [
                'collected',        // تم التحصيل
                'due',             // تستحق التحصيل
                'postponed',       // المؤجلة
                'overdue'          // تجاوزت المدة
            ])->default('due')->after('tenant_id');
            
            // إضافة حقل المبلغ البسيط
            $table->decimal('amount_simple', 10, 2)->nullable()->after('collection_status');
            
            // إضافة تواريخ بسيطة
            $table->date('date_from')->nullable()->after('amount_simple');
            $table->date('date_to')->nullable()->after('date_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->dropColumn(['collection_status', 'amount_simple', 'date_from', 'date_to']);
        });
    }
};