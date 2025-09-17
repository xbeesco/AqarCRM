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
        Schema::table('property_contracts', function (Blueprint $table) {
            $table->string('file')->nullable()->after('notes');
        });

        Schema::table('unit_contracts', function (Blueprint $table) {
            $table->string('file')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_contracts', function (Blueprint $table) {
            $table->dropColumn('file');
        });

        Schema::table('unit_contracts', function (Blueprint $table) {
            $table->dropColumn('file');
        });
    }
};