<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->integer('duration_months')->default(12);
            $table->string('payment_frequency', 20)->default('monthly'); // monthly, quarterly, semi_annually, annually
            $table->integer('payments_count')->nullable()->comment('عدد الدفعات المحسوب بناءً على مدة العقد وتكرار التوريد');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('contract_status', ['draft', 'active', 'suspended', 'expired', 'terminated'])->default('draft');
            $table->text('notes')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();

            $table->index('start_date');
            $table->index('end_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_contracts');
    }
};
