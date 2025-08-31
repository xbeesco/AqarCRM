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
        Schema::table('supply_payments', function (Blueprint $table) {
            // إضافة حقل الموظف الذي قام بالتوريد
            $table->unsignedBigInteger('collected_by')->nullable()->after('paid_date');
            
            // إضافة foreign key للربط مع جدول users
            $table->foreign('collected_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
                  
            // إضافة index لتسريع البحث
            $table->index('collected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_payments', function (Blueprint $table) {
            // حذف الـ foreign key أولاً
            $table->dropForeign(['collected_by']);
            
            // حذف الـ index
            $table->dropIndex(['collected_by']);
            
            // حذف العمود
            $table->dropColumn('collected_by');
        });
    }
};
