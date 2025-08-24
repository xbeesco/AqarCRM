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
        // فحص وتعديل username لو موجود
        if (Schema::hasColumn('users', 'username')) {
            DB::statement('ALTER TABLE users MODIFY username VARCHAR(255) NULL');
        }
        
        // فحص وتعديل email لو موجود
        if (Schema::hasColumn('users', 'email')) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // فحص وإرجاع username لو موجود
        if (Schema::hasColumn('users', 'username')) {
            DB::statement('ALTER TABLE users MODIFY username VARCHAR(255) NOT NULL');
        }
        
        // فحص وإرجاع email لو موجود
        if (Schema::hasColumn('users', 'email')) {
            DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        }
    }
};