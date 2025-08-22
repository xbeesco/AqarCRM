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
        Schema::table('properties', function (Blueprint $table) {
            // حذف الحقول غير المطلوبة (فقط الموجودة في الجدول)
            $table->dropColumn(['area_sqm']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // إعادة إضافة الحقول في حالة التراجع
            $table->decimal('area_sqm', 10, 2)->nullable();
        });
    }
};
