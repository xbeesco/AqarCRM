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
            // Drop old columns
            $table->dropColumn(['type', 'status']);
            
            // Add new foreign key columns
            $table->foreignId('type_id')->nullable()->constrained('property_types')->after('owner_id');
            $table->foreignId('status_id')->nullable()->constrained('property_statuses')->after('type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop foreign keys and columns
            $table->dropForeign(['type_id']);
            $table->dropForeign(['status_id']);
            $table->dropColumn(['type_id', 'status_id']);
            
            // Add back old columns
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->enum('type', ['residential', 'commercial', 'mixed'])->default('residential');
        });
    }
};