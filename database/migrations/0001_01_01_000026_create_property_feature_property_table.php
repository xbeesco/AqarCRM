<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_feature_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('property_feature_id')->constrained('property_features')->cascadeOnDelete();
            $table->string('value')->nullable();
            $table->timestamps();
            $table->unique(['property_id', 'property_feature_id'], 'property_feature_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_feature_property');
    }
};
