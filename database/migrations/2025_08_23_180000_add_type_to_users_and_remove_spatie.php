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
        // التحقق من وجود عمود type أولاً
        if (!Schema::hasColumn('users', 'type')) {
            // إضافة عمود type
            Schema::table('users', function (Blueprint $table) {
                $table->string('type', 20)->default('employee')->after('password');
                $table->index('type');
            });
        }
        
        // التحقق من وجود جداول Spatie قبل محاولة الترحيل
        if (Schema::hasTable('model_has_roles') && Schema::hasTable('roles')) {
            // تحديث البيانات الموجودة بناءً على الأدوار الحالية (إن وُجدت)
            try {
                DB::statement("
                    UPDATE users u
                    LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\\\Models\\\\User'
                    LEFT JOIN roles r ON mhr.role_id = r.id
                    SET u.type = CASE
                        WHEN r.name = 'super_admin' THEN 'super_admin'
                        WHEN r.name = 'admin' THEN 'admin'
                        WHEN r.name = 'owner' THEN 'owner'
                        WHEN r.name = 'tenant' THEN 'tenant'
                        WHEN r.name = 'employee' THEN 'employee'
                        ELSE u.type
                    END
                    WHERE r.name IS NOT NULL
                ");
            } catch (\Exception $e) {
                // في حالة فشل الترحيل، نستمر بدون توقف
                // قد تكون الجداول غير موجودة أو البيانات غير متوافقة
            }
        }
        
        // تحديد النوع بناءً على وجود بيانات خاصة بكل نوع
        // للمستخدمين الذين لم يتم تحديد نوعهم بعد
        DB::statement("
            UPDATE users 
            SET type = 'owner' 
            WHERE type = 'employee' 
            AND (commercial_register IS NOT NULL 
                 OR tax_number IS NOT NULL 
                 OR bank_account_number IS NOT NULL)
        ");
        
        DB::statement("
            UPDATE users 
            SET type = 'tenant' 
            WHERE type = 'employee' 
            AND current_property_id IS NOT NULL
        ");
        
        DB::statement("
            UPDATE users 
            SET type = 'employee' 
            WHERE type = 'employee' 
            AND (employee_id IS NOT NULL 
                 OR department IS NOT NULL 
                 OR position IS NOT NULL)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'type')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['type']);
                $table->dropColumn('type');
            });
        }
    }
};