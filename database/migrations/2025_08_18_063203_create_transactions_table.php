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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number', 50)->unique();
            $table->enum('type', ['collection_payment', 'supply_payment', 'repair_expense', 'other']);
            $table->morphs('transactionable'); // For polymorphic relation
            $table->foreignId('property_id')->nullable()->constrained('properties')->onDelete('cascade');
            $table->decimal('debit_amount', 10, 2)->default(0.00);
            $table->decimal('credit_amount', 10, 2)->default(0.00);
            $table->decimal('balance', 12, 2)->default(0.00);
            $table->text('description');
            $table->date('transaction_date');
            $table->string('reference_number', 100)->nullable();
            $table->json('meta_data')->nullable();
            $table->timestamps();

            $table->index(['type', 'transaction_date']);
            $table->index(['property_id', 'transaction_date']);
            $table->index('transaction_date');
            $table->index(['transactionable_type', 'transactionable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
