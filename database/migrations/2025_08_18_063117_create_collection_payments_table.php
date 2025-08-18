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
        Schema::create('collection_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('unit_contract_id')->constrained('unit_contracts')->onDelete('cascade');
            $table->foreignId('unit_id')->constrained('units')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_status_id')->constrained('payment_statuses');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods');
            $table->decimal('amount', 10, 2);
            $table->decimal('late_fee', 8, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);
            $table->date('due_date_start');
            $table->date('due_date_end');
            $table->date('paid_date')->nullable();
            $table->integer('delay_duration')->nullable();
            $table->text('delay_reason')->nullable();
            $table->text('late_payment_notes')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->string('receipt_number', 50)->nullable()->unique();
            $table->string('month_year', 7);
            $table->timestamps();

            $table->index(['payment_status_id', 'due_date_end']);
            $table->index(['property_id', 'month_year']);
            $table->index('month_year');
            $table->index('due_date_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_payments');
    }
};
