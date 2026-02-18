<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('unit_type_id')->nullable()->constrained('unit_types')->nullOnDelete();
            $table->foreignId('unit_category_id')->nullable()->constrained('unit_categories')->nullOnDelete();
            $table->decimal('rent_price', 10, 2);
            $table->string('floor_plan_file')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('property_id');
            $table->index('unit_type_id');
            $table->index('unit_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
