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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->enum('type', ['residential', 'commercial', 'mixed'])->default('residential');
            $table->foreignId('location_id')->nullable()->constrained('locations');
            $table->string('address');
            $table->string('postal_code')->nullable();
            $table->integer('parking_spots')->default(0);
            $table->integer('elevators')->default(0);
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->integer('build_year')->nullable();
            $table->integer('floors_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
