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
        Schema::create('property_features', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('category')->nullable(); // أساسيات، مرافق، أمان، إضافات
            $table->string('icon')->nullable();
            $table->boolean('requires_value')->default(false);
            $table->string('value_type')->nullable(); // number, text, boolean
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // جدول الربط بين العقارات والمميزات
        Schema::create('property_property_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_feature_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable(); // قيمة الميزة إذا كانت تتطلب قيمة
            $table->timestamps();

            $table->unique(['property_id', 'property_feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_property_feature');
        Schema::dropIfExists('property_features');
    }
};