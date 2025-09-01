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
        // التأكد من حذف payment_status_id نهائياً من collection_payments
        Schema::table('collection_payments', function (Blueprint $table) {
            // التحقق من وجود العمود قبل محاولة حذفه
            if (Schema::hasColumn('collection_payments', 'payment_status_id')) {
                // حذف الـ foreign key إن وجد
                try {
                    $table->dropForeign(['payment_status_id']);
                } catch (\Exception $e) {
                    // تجاهل الخطأ إذا لم يكن الـ foreign key موجود
                }
                
                // حذف أي indexes تحتوي على payment_status_id
                $indexes = Schema::getConnection()
                    ->getDoctrineSchemaManager()
                    ->listTableIndexes('collection_payments');
                    
                foreach ($indexes as $index) {
                    if (in_array('payment_status_id', $index->getColumns())) {
                        try {
                            $table->dropIndex($index->getName());
                        } catch (\Exception $e) {
                            // تجاهل إذا كان الـ index غير موجود
                        }
                    }
                }
                
                // حذف العمود نهائياً
                $table->dropColumn('payment_status_id');
            }
            
            // التأكد من حذف أي عمود payment_status أيضاً (لو تم إضافته بالخطأ)
            if (Schema::hasColumn('collection_payments', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
        
        // حذف جدول payment_statuses نهائياً إن كان موجود
        Schema::dropIfExists('payment_statuses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا نريد إعادة هذه الأعمدة أبداً
        // النظام الآن يعتمد على الحساب الديناميكي فقط
    }
};