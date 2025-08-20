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
        // First clean up table by dropping unwanted columns
        Schema::table('users', function (Blueprint $table) {
            // List of columns to drop
            $columnsToDrop = [
                'email_verified_at',
                'remember_token',
                'employee_id',
                'department', 
                'joining_date',
                'salary',
                'position',
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
                'secondary_phone',
                'nationality',
                'ownership_documents',
                'legal_representative',
                'company_name',
                'business_type'
            ];
            
            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        // Make email nullable and remove unique constraint
        Schema::table('users', function (Blueprint $table) {
            // Check if email has unique index and drop it
            $indexes = DB::select("SHOW INDEX FROM users WHERE Column_name = 'email' AND Key_name != 'PRIMARY'");
            foreach ($indexes as $index) {
                try {
                    $table->dropUnique($index->Key_name);
                } catch (Exception $e) {
                    // Continue if index doesn't exist
                }
            }
            
            $table->string('email')->nullable()->change();
        });

        // Add new required columns
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->after('name');
            }
            if (!Schema::hasColumn('users', 'phone1')) {
                $table->string('phone1')->after('username');
            }
            if (!Schema::hasColumn('users', 'phone2')) {
                $table->string('phone2')->nullable()->after('phone1');
            }
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default('employee')->after('phone2');
            }
        });

        // Update existing data - ensure unique usernames for all users
        DB::statement("
            UPDATE users 
            SET 
                username = CONCAT(
                    CASE 
                        WHEN email IS NOT NULL AND email != '' THEN SUBSTRING_INDEX(email, '@', 1)
                        ELSE 'user'
                    END,
                    '_', id
                ),
                phone1 = CONCAT('555000', LPAD(id, 3, '0'))
        ");

        // Add unique constraint to username
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is destructive and cannot be easily reversed
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'phone1', 'phone2', 'user_type']);
            $table->string('email')->unique()->change();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
        });
    }
};
