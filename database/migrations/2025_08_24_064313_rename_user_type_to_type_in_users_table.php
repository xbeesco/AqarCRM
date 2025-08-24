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
        // فحص لو العمود user_type موجود ونغير اسمه لـ type
        if (Schema::hasColumn('users', 'user_type') && !Schema::hasColumn('users', 'type')) {
            // استخدام SQL مباشر لتغيير اسم العمود
            DB::statement('ALTER TABLE users CHANGE COLUMN user_type type VARCHAR(20)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // إرجاع الاسم القديم
        if (Schema::hasColumn('users', 'type') && !Schema::hasColumn('users', 'user_type')) {
            DB::statement('ALTER TABLE users CHANGE COLUMN type user_type VARCHAR(20)');
        }
    }
};
