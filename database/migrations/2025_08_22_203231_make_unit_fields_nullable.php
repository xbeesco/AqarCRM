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
        Schema::table('units', function (Blueprint $table) {
            // جعل الحقول الاختيارية تقبل القيم الفارغة
            $table->integer('rooms_count')->nullable()->change();
            $table->integer('bathrooms_count')->nullable()->change();
            $table->integer('balconies_count')->nullable()->change();
            $table->integer('floor_number')->nullable()->change();
            $table->decimal('area_sqm', 10, 2)->nullable()->change();
            $table->decimal('water_expenses', 10, 2)->nullable()->change();
            $table->string('electricity_account_number')->nullable()->change();
            $table->string('floor_plan_file')->nullable()->change();
            $table->text('notes')->nullable()->change();
            
            // إضافة حقل rent_price إذا لم يكن موجوداً أو جعله nullable
            $table->decimal('rent_price', 10, 2)->nullable()->change();
            
            // التأكد من أن status له قيمة افتراضية
            $table->string('status')->default('available')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // إرجاع الحقول إلى NOT NULL
            $table->integer('rooms_count')->nullable(false)->change();
            $table->integer('bathrooms_count')->nullable(false)->change();
            $table->integer('balconies_count')->nullable(false)->change();
            $table->integer('floor_number')->nullable(false)->change();
            $table->decimal('area_sqm', 10, 2)->nullable(false)->change();
            $table->decimal('rent_price', 10, 2)->nullable(false)->change();
        });
    }
};
