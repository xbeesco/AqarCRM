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
        Schema::create('supply_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('property_contract_id')->constrained('property_contracts')->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('commission_amount', 10, 2)->default(0.00);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('maintenance_deduction', 10, 2)->default(0.00);
            $table->decimal('other_deductions', 10, 2)->default(0.00);
            $table->decimal('net_amount', 10, 2);
            $table->enum('supply_status', ['pending', 'worth_collecting', 'collected', 'on_hold'])->default('pending');
            $table->date('due_date');
            $table->date('paid_date')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('bank_transfer_reference', 100)->nullable();
            $table->json('invoice_details')->nullable();
            $table->json('deduction_details')->nullable();
            $table->string('month_year', 7);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['supply_status', 'due_date']);
            $table->index(['owner_id', 'month_year']);
            $table->index('approval_status');
            $table->index('month_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supply_payments');
    }
};
