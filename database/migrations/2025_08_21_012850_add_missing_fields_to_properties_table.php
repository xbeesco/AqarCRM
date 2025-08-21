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
            // Add coordinates only if they don't exist
            if (!Schema::hasColumn('properties', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('address');
            }
            if (!Schema::hasColumn('properties', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            
            // Add has_elevator boolean only if it doesn't exist
            if (!Schema::hasColumn('properties', 'has_elevator')) {
                $table->boolean('has_elevator')->default(false)->after('elevators');
            }
            
            // Add garden_area only if it doesn't exist
            if (!Schema::hasColumn('properties', 'garden_area')) {
                $table->decimal('garden_area', 10, 2)->nullable()->after('floors_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'latitude', 
                'longitude', 
                'has_elevator', 
                'garden_area'
            ]);
        });
    }
};