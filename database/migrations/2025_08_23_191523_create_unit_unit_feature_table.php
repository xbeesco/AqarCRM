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
        Schema::create('unit_unit_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('unit_feature_id')->constrained('unit_features')->onDelete('cascade');
            $table->string('value')->nullable(); // قيمة المميزة (مثلاً: "2" للمصاعد)
            $table->timestamps();
            
            // منع التكرار - وحدة واحدة لا يمكن أن يكون لها نفس المميزة مرتين
            $table->unique(['unit_id', 'unit_feature_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_unit_feature');
    }
};
