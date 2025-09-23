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
        // إنشاء الجدول فقط إذا لم يكن موجوداً
        if (!Schema::hasTable('properties')) {
            Schema::create('properties', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('owner_id')->constrained('users');
                $table->unsignedBigInteger('type_id')->nullable(); // تم إزالة foreign key مؤقتاً
                $table->unsignedBigInteger('status_id')->nullable(); // تم إزالة foreign key مؤقتاً
                $table->foreignId('location_id')->nullable()->constrained('locations');
                $table->string('address');
                $table->string('postal_code')->nullable();
                $table->integer('parking_spots')->nullable();
                $table->integer('elevators')->nullable();
                $table->integer('build_year')->nullable();
                $table->integer('floors_count')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
