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
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('locations');
            $table->integer('level')->default(1);
            $table->string('path')->nullable();
            $table->string('coordinates')->nullable();
            $table->string('postal_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('parent_id');
            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
