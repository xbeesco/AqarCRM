<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // 1. تصحيح اسم حقل السنة من built_year إلى build_year
            if (Schema::hasColumn('properties', 'built_year') && !Schema::hasColumn('properties', 'build_year')) {
                $table->renameColumn('built_year', 'build_year');
            } elseif (!Schema::hasColumn('properties', 'build_year')) {
                $table->integer('build_year')->nullable()->after('floors_count');
            }
            
            // 2. نقل البيانات من الحقول المكررة القديمة إلى الجديدة (إن وجدت)
            if (Schema::hasColumn('properties', 'property_type_id') && Schema::hasColumn('properties', 'type_id')) {
                // نقل البيانات من property_type_id إلى type_id إذا كان type_id فارغ
                DB::statement('UPDATE properties SET type_id = property_type_id WHERE type_id IS NULL AND property_type_id IS NOT NULL');
            }
            
            if (Schema::hasColumn('properties', 'property_status_id') && Schema::hasColumn('properties', 'status_id')) {
                // نقل البيانات من property_status_id إلى status_id إذا كان status_id فارغ
                DB::statement('UPDATE properties SET status_id = property_status_id WHERE status_id IS NULL AND property_status_id IS NOT NULL');
            }
        });
        
        // 3. حذف الحقول الزائدة والمكررة
        Schema::table('properties', function (Blueprint $table) {
            // حذف الحقول المكررة
            if (Schema::hasColumn('properties', 'property_type_id')) {
                $table->dropForeign(['property_type_id']);
                $table->dropColumn('property_type_id');
            }
            
            if (Schema::hasColumn('properties', 'property_status_id')) {
                $table->dropForeign(['property_status_id']);
                $table->dropColumn('property_status_id');
            }
            
            // حذف الحقول الزائدة غير المستخدمة في Filament
            $columnsToRemove = [
                'code', 'latitude', 'longitude', 'total_units',
                'description', 'has_elevator', 'total_area',
                'building_area', 'garden_area', 'area_sqm'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('properties', $column)) {
                    // معالجة خاصة للحقل code مع unique constraint
                    if ($column === 'code') {
                        try {
                            $table->dropUnique('properties_code_unique');
                        } catch (\Exception $e) {
                            // القيد غير موجود، نتابع
                        }
                    }
                    
                    $table->dropColumn($column);
                }
            }
            
            // حذف enum columns القديمة إن وجدت
            if (Schema::hasColumn('properties', 'status') && Schema::getColumnType('properties', 'status') == 'string') {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('properties', 'type') && Schema::getColumnType('properties', 'type') == 'string') {
                $table->dropColumn('type');
            }
        });
        
        // 4. التأكد من وجود type_id و status_id مع العلاقات الصحيحة
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'type_id')) {
                $table->foreignId('type_id')->nullable()->after('owner_id')->constrained('property_types');
            }
            
            if (!Schema::hasColumn('properties', 'status_id')) {
                $table->foreignId('status_id')->nullable()->after('type_id')->constrained('property_statuses');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // إعادة الحقول المحذوفة (اختياري)
            if (!Schema::hasColumn('properties', 'built_year') && Schema::hasColumn('properties', 'build_year')) {
                $table->renameColumn('build_year', 'built_year');
            }
        });
    }
};