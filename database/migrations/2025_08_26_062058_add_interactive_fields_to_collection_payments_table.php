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
        Schema::table('collection_payments', function (Blueprint $table) {
            // التحقق من وجود الحقول وإضافتها إذا لم تكن موجودة
            if (!Schema::hasColumn('collection_payments', 'collection_date')) {
                $table->date('collection_date')->nullable()->after('paid_date');
            }
            
            // الحقول الموجودة بالفعل:
            // - due_date_start (بداية التاريخ)
            // - due_date_end (إلى التاريخ)
            // - paid_date (تاريخ الدفع)
            // - delay_reason (سبب التأجيل)
            // - delay_duration (مدة التأجيل بالأيام)
            // - late_payment_notes (ملاحظات تجاوز المدة)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->dropColumn(['collection_date']);
        });
    }
};
