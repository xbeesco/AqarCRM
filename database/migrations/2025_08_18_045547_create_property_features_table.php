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
            $table->string('name_ar', 100)->index();
            $table->string('name_en', 100)->index();
            $table->string('slug', 120)->unique();
            $table->enum('category', ['basics', 'amenities', 'security', 'extras'])->default('basics')->index();
            $table->string('icon', 50)->nullable()->default('heroicon-o-star');
            $table->boolean('requires_value')->default(false)->index();
            $table->enum('value_type', ['boolean', 'number', 'text', 'select'])->default('boolean');
            $table->json('value_options')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->integer('properties_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['category', 'sort_order'], 'idx_property_features_category');
            $table->index(['is_active'], 'idx_property_features_active');
            $table->index(['value_type'], 'idx_property_features_value_type');
            $table->index(['name_ar', 'name_en'], 'idx_property_features_name_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_features');
    }
};