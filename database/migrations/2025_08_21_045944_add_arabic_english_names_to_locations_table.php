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
        // First, let's copy existing name data to a temporary column
        Schema::table('locations', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('id');
            $table->string('name_en')->nullable()->after('name_ar');
        });
        
        // Copy existing name data to name_ar
        DB::statement('UPDATE locations SET name_ar = name WHERE name_ar IS NULL');
        DB::statement('UPDATE locations SET name_en = name WHERE name_en IS NULL');
        
        // Make the columns not nullable
        Schema::table('locations', function (Blueprint $table) {
            $table->string('name_ar')->nullable(false)->change();
            $table->string('name_en')->nullable(false)->change();
        });
        
        // Drop the old name column
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->string('name')->after('id');
        });
        
        // Copy name_ar back to name
        DB::statement('UPDATE locations SET name = name_ar');
        
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en']);
        });
    }
};