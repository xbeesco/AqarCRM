<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_unit_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('unit_feature_id')->constrained('unit_features')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->timestamps();
            $table->unique(['unit_id', 'unit_feature_id'], 'unit_feature_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_unit_feature');
    }
};
