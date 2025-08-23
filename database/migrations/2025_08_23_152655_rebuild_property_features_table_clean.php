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
        
        // حذف الجداول المرتبطة أولاً
        Schema::dropIfExists('property_property_feature');
        Schema::dropIfExists('property_features');
        
        // إنشاء جدول المميزات بالهيكل النهائي الشامل
        Schema::create('property_features', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 100)->index();
            $table->string('name_en', 100)->index();
            $table->string('slug', 120)->unique();
            $table->enum('category', ['basics', 'amenities', 'security', 'extras'])->default('basics')->index();
            $table->string('icon', 50)->nullable()->default('heroicon-o-star');
            $table->boolean('requires_value')->default(false)->index();
            $table->enum('value_type', ['boolean', 'number', 'text', 'select'])->default('boolean');
            $table->json('value_options')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->integer('properties_count')->default(0);
            $table->timestamps();

            // فهارس لتحسين الأداء
            $table->index(['category', 'sort_order'], 'idx_property_features_category');
            $table->index(['is_active'], 'idx_property_features_active');
            $table->index(['value_type'], 'idx_property_features_value_type');
            $table->index(['name_ar', 'name_en'], 'idx_property_features_name_search');
        });

        // إنشاء جدول الربط بين العقارات والمميزات
        Schema::create('property_property_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_feature_id')->constrained('property_features')->onDelete('cascade');
            $table->string('value')->nullable(); // قيمة الميزة إذا كانت تتطلب قيمة
            $table->timestamps();

            $table->unique(['property_id', 'property_feature_id'], 'uk_property_feature');
            $table->index('property_id', 'idx_property_property_feature_property');
            $table->index('property_feature_id', 'idx_property_property_feature_feature');
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
        Schema::dropIfExists('property_property_feature');
        Schema::dropIfExists('property_features');
        Schema::enableForeignKeyConstraints();
    }
};