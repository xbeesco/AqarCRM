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
        Schema::table('locations', function (Blueprint $table) {
            $table->string('path')->nullable()->after('level');
            $table->string('code')->nullable()->after('name_en');
            $table->string('coordinates')->nullable()->after('path');
            $table->string('postal_code')->nullable()->after('coordinates');
            $table->boolean('is_active')->default(true)->after('postal_code');
            
            $table->index('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropIndex(['path']);
            $table->dropColumn(['path', 'code', 'coordinates', 'postal_code', 'is_active']);
        });
    }
};
