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
        // التحقق من وجود الجداول قبل محاولة حذفها
        // لدعم جميع السيناريوهات: مستخدم جديد، قديم، أو بنسخة حديثة
        
        // Drop pivot tables first if they exist (foreign key constraints)
        if (Schema::hasTable('model_has_permissions')) {
            Schema::dropIfExists('model_has_permissions');
        }
        
        if (Schema::hasTable('model_has_roles')) {
            Schema::dropIfExists('model_has_roles');
        }
        
        if (Schema::hasTable('role_has_permissions')) {
            Schema::dropIfExists('role_has_permissions');
        }
        
        // Drop main tables if they exist
        if (Schema::hasTable('permissions')) {
            Schema::dropIfExists('permissions');
        }
        
        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't recreate the tables as we're no longer using Spatie
        // If needed, restore from a backup or re-install the package
    }
};