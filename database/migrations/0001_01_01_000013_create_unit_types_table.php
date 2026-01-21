<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('slug', 120)->unique();
            $table->string('icon', 50)->nullable()->default('heroicon-o-home');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['name_ar', 'name_en']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_types');
    }
};
