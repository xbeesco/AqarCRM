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
        Schema::create('unit_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 100)->index();
            $table->string('name_en', 100)->index();
            $table->string('slug', 120)->unique();
            $table->string('color', 20)->default('gray');
            $table->string('icon', 50)->nullable()->default('heroicon-o-home');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->boolean('allows_tenant_assignment')->default(true);
            $table->boolean('requires_maintenance')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->integer('units_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'sort_order'], 'idx_unit_statuses_active_sort');
            $table->index(['is_available', 'allows_tenant_assignment'], 'idx_unit_statuses_availability');
            $table->index(['name_ar', 'name_en'], 'idx_unit_statuses_name_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_statuses');
    }
};
