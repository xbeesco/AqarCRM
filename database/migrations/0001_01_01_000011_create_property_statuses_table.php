<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 100)->index();
            $table->string('name_en', 100)->index();
            $table->string('slug', 120)->unique();
            $table->string('color', 20)->default('gray');
            $table->string('icon', 50)->nullable()->default('heroicon-o-home');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->integer('properties_count')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index(['name_ar', 'name_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_statuses');
    }
};
