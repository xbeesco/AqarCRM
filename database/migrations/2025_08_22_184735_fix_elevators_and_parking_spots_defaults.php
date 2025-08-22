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
            // تعديل حقل elevators ليقبل NULL
            $table->integer('elevators')->nullable()->default(null)->change();
            // تعديل حقل parking_spots ليقبل NULL أيضاً
            $table->integer('parking_spots')->nullable()->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('elevators')->default(0)->change();
            $table->integer('parking_spots')->default(0)->change();
        });
    }
};
