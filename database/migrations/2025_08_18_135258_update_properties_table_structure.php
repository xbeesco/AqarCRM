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
            // Add new columns
            $table->string('code', 50)->unique()->after('id');
            $table->integer('total_units')->default(0)->after('address');
            $table->text('description')->nullable()->after('total_units');
            
            // Add property type and status relationships
            $table->foreignId('property_type_id')->nullable()->after('owner_id')->constrained('property_types');
            $table->foreignId('property_status_id')->nullable()->after('property_type_id')->constrained('property_statuses');
            
            // Rename columns
            $table->renameColumn('area_sqm', 'total_area');
            $table->renameColumn('build_year', 'built_year');
            
            // Add missing columns
            $table->decimal('building_area', 10, 2)->nullable()->after('total_area');
            
            // Drop old enum columns
            $table->dropColumn(['status', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Re-add old columns
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->enum('type', ['residential', 'commercial', 'mixed'])->default('residential');
            
            // Drop new columns
            $table->dropColumn(['code', 'total_units', 'description', 'building_area']);
            $table->dropForeign(['property_type_id']);
            $table->dropColumn('property_type_id');
            $table->dropForeign(['property_status_id']);
            $table->dropColumn('property_status_id');
            
            // Rename columns back
            $table->renameColumn('total_area', 'area_sqm');
            $table->renameColumn('built_year', 'build_year');
        });
    }
};