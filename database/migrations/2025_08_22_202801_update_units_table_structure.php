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
        Schema::table('units', function (Blueprint $table) {
            // إضافة الأعمدة الجديدة
            $table->string('name')->after('id');
            $table->foreignId('unit_type_id')->nullable()->after('property_id')->constrained('unit_types')->nullOnDelete();
            $table->foreignId('unit_category_id')->nullable()->after('unit_type_id')->constrained('unit_categories')->nullOnDelete();
            $table->integer('balconies_count')->nullable()->after('bathrooms_count');
            $table->boolean('has_laundry_room')->default(false)->after('balconies_count');
            $table->string('electricity_account_number')->nullable()->after('has_laundry_room');
            $table->decimal('water_expenses', 10, 2)->nullable()->after('electricity_account_number');
            $table->string('floor_plan_file')->nullable()->after('water_expenses');
            $table->string('status')->default('available')->after('floor_plan_file');
            
            // حذف الأعمدة القديمة غير المستخدمة
            $table->dropColumn(['unit_number', 'unit_type', 'unit_ranking', 'direction', 'view_type']);
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
            $table->dropForeign(['current_tenant_id']);
            $table->dropColumn('current_tenant_id');
            $table->dropColumn(['furnished', 'has_balcony', 'has_parking', 'has_storage', 'has_maid_room']);
            $table->dropColumn(['available_from', 'last_maintenance_date', 'next_maintenance_date', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // استرجاع الأعمدة القديمة
            $table->string('unit_number')->nullable();
            $table->string('unit_type')->nullable();
            $table->string('unit_ranking')->nullable();
            $table->string('direction')->nullable();
            $table->string('view_type')->nullable();
            $table->foreignId('status_id')->nullable()->constrained('unit_statuses');
            $table->foreignId('current_tenant_id')->nullable();
            $table->boolean('furnished')->default(false);
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_storage')->default(false);
            $table->boolean('has_maid_room')->default(false);
            $table->date('available_from')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->boolean('is_active')->default(true);
            
            // حذف الأعمدة الجديدة
            $table->dropColumn(['name', 'balconies_count', 'has_laundry_room', 'electricity_account_number', 'water_expenses', 'floor_plan_file', 'status']);
            $table->dropForeign(['unit_type_id']);
            $table->dropColumn('unit_type_id');
            $table->dropForeign(['unit_category_id']);
            $table->dropColumn('unit_category_id');
        });
    }
};
