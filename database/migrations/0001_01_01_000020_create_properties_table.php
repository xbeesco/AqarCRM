<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('address');
            $table->string('postal_code')->nullable();
            $table->integer('parking_spots')->nullable();
            $table->integer('elevators')->nullable();
            $table->integer('build_year')->nullable();
            $table->integer('floors_count')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            // type_id and status_id will be added without FK constraints initially
            // to allow flexibility with the reference data
            $table->unsignedBigInteger('type_id')->nullable();
            $table->unsignedBigInteger('status_id')->nullable();

            // Indexes
            $table->index('owner_id');
            $table->index('location_id');
            $table->index('type_id');
            $table->index('status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
