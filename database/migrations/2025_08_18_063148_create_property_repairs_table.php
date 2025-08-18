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
        Schema::create('property_repairs', function (Blueprint $table) {
            $table->id();
            $table->string('repair_number', 50)->unique();
            $table->foreignId('repair_category_id')->constrained('repair_categories');
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('cascade');
            $table->foreignId('unit_id')->nullable()->constrained('units')->onDelete('cascade');
            $table->string('title', 200);
            $table->text('description');
            $table->decimal('total_cost', 10, 2);
            $table->date('maintenance_date');
            $table->date('scheduled_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->enum('status', ['reported', 'scheduled', 'in_progress', 'completed', 'cancelled'])->default('reported');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->string('vendor_name', 200)->nullable();
            $table->string('vendor_phone', 20)->nullable();
            $table->boolean('is_under_warranty')->default(false);
            $table->date('warranty_expires_at')->nullable();
            $table->text('work_notes')->nullable();
            $table->json('cost_breakdown')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'maintenance_date']);
            $table->index(['unit_id', 'maintenance_date']);
            $table->index(['status', 'priority']);
            $table->index('maintenance_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_repairs');
    }
};
