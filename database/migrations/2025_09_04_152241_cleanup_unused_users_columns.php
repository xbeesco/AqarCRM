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
        Schema::table('users', function (Blueprint $table) {
            // إضافة email_verified_at إذا لم يكن موجوداً
            if (!Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            }
        });
        
        // حذف Foreign Key بشكل منفصل للتأكد من عدم حدوث أخطاء
        if (Schema::hasColumn('users', 'supervisor_id')) {
            Schema::table('users', function (Blueprint $table) {
                // محاولة حذف الـ foreign key إذا كان موجوداً
                try {
                    $table->dropForeign(['supervisor_id']);
                } catch (\Exception $e) {
                    // تجاهل الخطأ إذا لم يكن الـ foreign key موجوداً
                }
            });
        }
        
        // حذف الحقول غير المستخدمة
        Schema::table('users', function (Blueprint $table) {
            $columnsToRemove = [
                'supervisor_id',
                'emergency_contact',
                'emergency_phone',
                'address',
                'birth_date',
                'commercial_register',
                'tax_number',
                'bank_name',
                'bank_account_number',
                'iban',
                'nationality',
                'ownership_documents',
                'legal_representative',
                'company_name',
                'business_type'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // حذف email_verified_at إذا كان موجوداً
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            
            // إعادة الحقول المحذوفة
            if (!Schema::hasColumn('users', 'supervisor_id')) {
                $table->foreignId('supervisor_id')->nullable()->after('deleted_at');
            }
            if (!Schema::hasColumn('users', 'emergency_contact')) {
                $table->string('emergency_contact')->nullable()->after('supervisor_id');
            }
            if (!Schema::hasColumn('users', 'emergency_phone')) {
                $table->string('emergency_phone', 20)->nullable()->after('emergency_contact');
            }
            if (!Schema::hasColumn('users', 'address')) {
                $table->text('address')->nullable()->after('emergency_phone');
            }
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('address');
            }
            if (!Schema::hasColumn('users', 'commercial_register')) {
                $table->string('commercial_register')->nullable()->after('birth_date');
            }
            if (!Schema::hasColumn('users', 'tax_number')) {
                $table->string('tax_number')->nullable()->after('commercial_register');
            }
            if (!Schema::hasColumn('users', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('tax_number');
            }
            if (!Schema::hasColumn('users', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('users', 'iban')) {
                $table->string('iban')->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('users', 'nationality')) {
                $table->string('nationality')->nullable()->after('secondary_phone');
            }
            if (!Schema::hasColumn('users', 'ownership_documents')) {
                $table->json('ownership_documents')->nullable()->after('nationality');
            }
            if (!Schema::hasColumn('users', 'legal_representative')) {
                $table->string('legal_representative')->nullable()->after('ownership_documents');
            }
            if (!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable()->after('legal_representative');
            }
            if (!Schema::hasColumn('users', 'business_type')) {
                $table->string('business_type')->nullable()->after('company_name');
            }
            
            // إعادة foreign key
            if (Schema::hasColumn('users', 'supervisor_id')) {
                $table->foreign('supervisor_id')->references('id')->on('users');
            }
        });
    }
};