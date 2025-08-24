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
        
        // حذف الجداول المرتبطة أولاً لتجنب أخطاء المفاتيح الأجنبية
        Schema::dropIfExists('unit_feature_unit');
        
        // حذف الجدول بالكامل إذا كان موجود (للتخلص من أي هيكل قديم)
        Schema::dropIfExists('units');
        
        // إنشاء الجدول بالهيكل النهائي النظيف
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            
            // معلومات أساسية
            $table->string('name');
            $table->foreignId('property_id')->constrained('properties')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->nullOnDelete();
            $table->foreignId('unit_category_id')->nullable()->constrained('unit_categories')->nullOnDelete();
            
            // التفاصيل الفيزيائية
            $table->integer('floor_number')->index();
            $table->decimal('area_sqm', 8, 2);
            $table->integer('rooms_count')->index();
            $table->integer('bathrooms_count');
            $table->integer('balconies_count')->nullable();
            
            // المميزات
            $table->boolean('has_laundry_room')->default(false);
            
            // المعلومات المالية
            $table->decimal('rent_price', 10, 2)->index();
            $table->string('electricity_account_number')->nullable();
            $table->decimal('water_expenses', 10, 2)->nullable();
            
            // الملفات والوثائق
            $table->string('floor_plan_file')->nullable();
            
            // الحالة
            $table->string('status')->default('available')->index();
            
            // ملاحظات
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // الفهارس
            $table->index(['property_id', 'floor_number'], 'idx_units_property_floor');
            $table->index(['rent_price', 'rooms_count'], 'idx_units_rent_range');
            $table->index(['status'], 'idx_units_status');
            
            // قيد فريد على الاسم داخل العقار
            $table->unique(['property_id', 'name'], 'uk_units_property_name');
        });
        
        // إعادة تفعيل فحص المفاتيح الأجنبية
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};