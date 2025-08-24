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
        Schema::table('properties', function (Blueprint $table) {
            // فحص وحذف الأعمدة القديمة لو موجودة
            $columnsToRemove = [];
            if (Schema::hasColumn('properties', 'type')) {
                $columnsToRemove[] = 'type';
            }
            if (Schema::hasColumn('properties', 'status')) {
                $columnsToRemove[] = 'status';
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
            
            // إضافة الأعمدة الجديدة لو مش موجودة
            if (!Schema::hasColumn('properties', 'type_id')) {
                $table->foreignId('type_id')->nullable()->constrained('property_types')->after('owner_id');
            }
            if (!Schema::hasColumn('properties', 'status_id')) {
                $table->foreignId('status_id')->nullable()->constrained('property_statuses')->after('type_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // فحص وحذف الأعمدة الجديدة لو موجودة
            if (Schema::hasColumn('properties', 'type_id')) {
                $table->dropForeign(['type_id']);
                $table->dropColumn('type_id');
            }
            if (Schema::hasColumn('properties', 'status_id')) {
                $table->dropForeign(['status_id']);
                $table->dropColumn('status_id');
            }
            
            // إضافة الأعمدة القديمة لو مش موجودة
            if (!Schema::hasColumn('properties', 'status')) {
                $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            }
            if (!Schema::hasColumn('properties', 'type')) {
                $table->enum('type', ['residential', 'commercial', 'mixed'])->default('residential');
            }
        });
    }
};