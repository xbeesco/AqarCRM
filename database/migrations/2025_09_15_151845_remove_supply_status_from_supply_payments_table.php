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
            // حذف الفهرس أولاً إذا كان موجوداً
            $table->dropIndex('supply_payments_supply_status_due_date_index');

            // حذف العمود
            $table->dropColumn('supply_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_payments', function (Blueprint $table) {
            // إعادة إضافة العمود في حالة التراجع
            $table->enum('supply_status', ['pending', 'worth_collecting', 'collected', 'on_hold'])
                  ->default('pending')
                  ->after('net_amount');

            // إعادة إضافة الفهرس
            $table->index('supply_status');
        });
    }
};