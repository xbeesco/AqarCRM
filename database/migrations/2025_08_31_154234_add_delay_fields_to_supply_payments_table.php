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
            // إضافة حقول التأجيل مثل دفعات التحصيل
            $table->integer('delay_duration')->default(0)->after('paid_date');
            $table->text('delay_reason')->nullable()->after('delay_duration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supply_payments', function (Blueprint $table) {
            $table->dropColumn(['delay_duration', 'delay_reason']);
        });
    }
};
