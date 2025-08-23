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
            // جعل الحقول تقبل NULL
            $table->integer('rooms_count')->nullable()->change();
            $table->integer('bathrooms_count')->nullable()->change();
            $table->integer('floor_number')->nullable()->change();
            $table->decimal('area_sqm', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // إرجاعها لعدم قبول NULL (القيم الافتراضية السابقة)
            $table->integer('rooms_count')->nullable(false)->change();
            $table->integer('bathrooms_count')->nullable(false)->change();
            $table->integer('floor_number')->nullable(false)->change();
            $table->decimal('area_sqm', 10, 2)->nullable(false)->change();
        });
    }
};
