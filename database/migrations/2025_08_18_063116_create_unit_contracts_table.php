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
        Schema::create('unit_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('security_deposit', 10, 2)->default(0.00);
            $table->integer('duration_months')->default(12);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('contract_status', ['draft', 'active', 'expired', 'terminated', 'renewed'])->default('draft');
            $table->enum('payment_frequency', ['monthly', 'quarterly', 'semi_annually', 'annually'])->default('monthly');
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check', 'online'])->default('bank_transfer');
            $table->integer('grace_period_days')->default(5);
            $table->decimal('late_fee_rate', 5, 2)->default(0.00);
            $table->boolean('utilities_included')->default(false);
            $table->boolean('furnished')->default(false);
            $table->integer('evacuation_notice_days')->default(30);
            $table->text('terms_and_conditions')->nullable();
            $table->text('special_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('terminated_reason')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id']);
            $table->index(['unit_id']);
            $table->index(['property_id']);
            $table->index(['contract_status']);
            $table->index(['start_date', 'end_date']);
            $table->index(['contract_status', 'start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_contracts');
    }
};