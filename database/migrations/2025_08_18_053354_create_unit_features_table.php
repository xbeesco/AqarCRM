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
        Schema::create('unit_features', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 100)->index();
            $table->string('name_en', 100)->index();
            $table->string('slug', 120)->unique();
            $table->enum('category', ['basic', 'amenities', 'safety', 'luxury', 'services'])->default('basic')->index();
            $table->string('icon', 50)->nullable()->default('heroicon-o-star');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('requires_value')->default(false);
            $table->enum('value_type', ['boolean', 'number', 'text', 'select'])->nullable();
            $table->json('value_options')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            // Indexes
            $table->index(['category', 'sort_order'], 'idx_unit_features_category_sort');
            $table->index(['is_active', 'sort_order'], 'idx_unit_features_active_sort');
            $table->index(['name_ar', 'name_en'], 'idx_unit_features_name_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_features');
    }
};
