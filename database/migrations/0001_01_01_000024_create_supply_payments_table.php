<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('property_contract_id')->constrained('property_contracts')->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('maintenance_deduction', 10, 2)->default(0.00);
            $table->decimal('other_deductions', 10, 2)->default(0.00);
            $table->decimal('net_amount', 10, 2);
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->integer('delay_duration')->default(0);
            $table->text('delay_reason')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('bank_transfer_reference', 100)->nullable();
            $table->json('invoice_details')->nullable();
            $table->json('deduction_details')->nullable();
            $table->string('month_year', 7);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('due_date');
            $table->index('paid_date');
            $table->index('approval_status');
            $table->index('month_year');
            $table->index(['property_contract_id', 'due_date']);
            $table->index(['owner_id', 'paid_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_payments');
    }
};
