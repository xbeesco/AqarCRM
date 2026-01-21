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
            $table->string('notary_number', 100)->nullable();
            $table->integer('payment_day')->default(1);
            $table->boolean('auto_renew')->default(false);
            $table->integer('notice_period_days')->default(30);
            $table->text('terms_and_conditions')->nullable();
            $table->text('notes')->nullable();
            $table->string('file')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('terminated_reason')->nullable();
            $table->timestamp('terminated_at')->nullable();
            $table->timestamps();

            $table->index('contract_status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['property_id', 'contract_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_contracts');
    }
};
