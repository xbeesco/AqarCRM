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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('unit_number', 20)->index();
            $table->integer('floor_number')->index();
            $table->decimal('area_sqm', 8, 2);
            $table->integer('rooms_count')->index();
            $table->integer('bathrooms_count');
            $table->decimal('rent_price', 10, 2)->index();
            $table->enum('unit_type', ['studio', 'apartment', 'duplex', 'penthouse', 'office', 'shop', 'warehouse'])->default('apartment')->index();
            $table->enum('unit_ranking', ['economy', 'standard', 'premium', 'luxury'])->nullable()->index();
            $table->enum('direction', ['north', 'south', 'east', 'west', 'northeast', 'northwest', 'southeast', 'southwest'])->nullable();
            $table->enum('view_type', ['street', 'garden', 'sea', 'city', 'mountain', 'courtyard'])->nullable();
            // $table->foreignId('status_id')->constrained('unit_statuses')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedBigInteger('status_id')->nullable();
            $table->foreignId('current_tenant_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->boolean('furnished')->default(false)->index();
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_parking')->default(false);
            $table->boolean('has_storage')->default(false);
            $table->boolean('has_maid_room')->default(false);
            $table->text('notes')->nullable();
            $table->date('available_from')->nullable();
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_date')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            // Indexes
            $table->index(['property_id', 'unit_number'], 'idx_units_property_unit');
            // $table->index(['status_id', 'is_active'], 'idx_units_status_active');
            $table->index(['unit_type', 'unit_ranking'], 'idx_units_type_ranking');
            $table->index(['rent_price', 'rooms_count'], 'idx_units_rent_range');
            // $table->index(['status_id', 'available_from', 'is_active'], 'idx_units_availability');
            $table->index(['current_tenant_id', 'is_active'], 'idx_units_tenant_search');

            // Unique constraint
            $table->unique(['property_id', 'unit_number'], 'uk_units_property_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
