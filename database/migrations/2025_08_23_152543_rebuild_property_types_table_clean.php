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
        // تعطيل فحص المفاتيح الأجنبية مؤقتاً
        Schema::disableForeignKeyConstraints();
        
        // حذف الجدول بالكامل إذا كان موجود
        Schema::dropIfExists('property_types');
        
        // إنشاء الجدول بالهيكل النهائي الشامل
        Schema::create('property_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 100)->index();
            $table->string('name_en', 100)->index();
            $table->string('slug', 120)->unique();
            $table->string('icon', 50)->nullable()->default('heroicon-o-building-office');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->foreignId('parent_id')->nullable()
                  ->constrained('property_types')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->integer('properties_count')->default(0);
            $table->timestamps();

            // فهارس لتحسين الأداء
            $table->index(['is_active', 'sort_order'], 'idx_property_types_active_sort');
            $table->index(['parent_id'], 'idx_property_types_parent');
            $table->index(['name_ar', 'name_en'], 'idx_property_types_name_search');
        });
        
        // إعادة تفعيل فحص المفاتيح الأجنبية
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('property_types');
        Schema::enableForeignKeyConstraints();
    }
};