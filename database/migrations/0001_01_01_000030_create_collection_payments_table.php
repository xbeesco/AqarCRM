<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('unit_contract_id')->constrained('unit_contracts')->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('users')->cascadeOnDelete();
            // $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('late_fee', 8, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            $table->date('due_date_start');
            $table->date('due_date_end');
            $table->date('paid_date')->nullable();
            $table->date('collection_date')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('delay_duration')->nullable();
            $table->text('delay_reason')->nullable();
            $table->text('late_payment_notes')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('receipt_number', 50)->nullable()->unique();
            $table->string('month_year', 7);
            $table->timestamps();

            // Indexes matching SQL
            $table->index(['property_id', 'month_year']);
            $table->index('month_year');
            $table->index('due_date_start');
            $table->index('collected_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_payments');
    }
};
