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
        // 1. حذف حقل payment_status_id من جدول collection_payments
        Schema::table('collection_payments', function (Blueprint $table) {
            // حذف الـ foreign key أولاً
            $table->dropForeign(['payment_status_id']);
            
            // حذف الـ index
            $table->dropIndex(['payment_status_id', 'due_date_end']);
            
            // حذف العمود
            $table->dropColumn('payment_status_id');
        });
        
        // 2. حذف جدول payment_statuses بالكامل
        Schema::dropIfExists('payment_statuses');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. إعادة إنشاء جدول payment_statuses
        Schema::create('payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 50);
            $table->string('name_en', 50);
            $table->string('slug', 30)->unique();
            $table->string('color', 20)->nullable();
            $table->string('icon', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_paid_status')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('slug');
            $table->index(['is_active', 'sort_order']);
        });
        
        // 2. إضافة البيانات الافتراضية
        \DB::table('payment_statuses')->insert([
            ['id' => 1, 'name_ar' => 'تستحق التحصيل', 'name_en' => 'Due', 'slug' => 'due', 'color' => 'warning', 'icon' => 'heroicon-o-clock', 'is_paid_status' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name_ar' => 'محصل', 'name_en' => 'Collected', 'slug' => 'collected', 'color' => 'success', 'icon' => 'heroicon-o-check-circle', 'is_paid_status' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name_ar' => 'مؤجل', 'name_en' => 'Postponed', 'slug' => 'postponed', 'color' => 'info', 'icon' => 'heroicon-o-pause-circle', 'is_paid_status' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name_ar' => 'متأخر', 'name_en' => 'Overdue', 'slug' => 'overdue', 'color' => 'danger', 'icon' => 'heroicon-o-exclamation-circle', 'is_paid_status' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
        
        // 3. إضافة حقل payment_status_id مرة أخرى
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_status_id')->default(1)->after('tenant_id');
            
            // إضافة الـ foreign key
            $table->foreign('payment_status_id')->references('id')->on('payment_statuses');
            
            // إضافة الـ index
            $table->index(['payment_status_id', 'due_date_end']);
        });
    }
};