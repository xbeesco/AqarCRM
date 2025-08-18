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
        Schema::create('property_contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number', 50)->unique();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->decimal('commission_rate', 5, 2)->default(5.00);
            $table->integer('duration_months')->default(12);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('contract_status', ['draft', 'active', 'suspended', 'expired', 'terminated'])->default('draft');
            $table->string('notary_number', 100)->nullable();
            $table->integer('payment_day')->default(1);
            $table->boolean('auto_renew')->default(false);
            $table->integer('notice_period_days')->default(30);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('terminated_reason')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['owner_id']);
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
        Schema::dropIfExists('property_contracts');
    }
};