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
            // جعل parking_spots و elevators يقبلان NULL
            $table->integer('parking_spots')->nullable()->default(null)->change();
            $table->integer('elevators')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // إرجاعهما للقيمة الافتراضية 0
            $table->integer('parking_spots')->default(0)->change();
            $table->integer('elevators')->default(0)->change();
        });
    }
};
