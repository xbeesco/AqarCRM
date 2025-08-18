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
        Schema::table('users', function (Blueprint $table) {
            // Employee fields
            $table->string('employee_id')->nullable();
            $table->string('department')->nullable();
            $table->date('joining_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('position')->nullable();
            $table->foreignId('supervisor_id')->nullable()->constrained('users');
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();
            
            // Owner fields
            $table->string('commercial_register')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('secondary_phone')->nullable();
            $table->string('nationality')->nullable();
            $table->json('ownership_documents')->nullable();
            $table->string('legal_representative')->nullable();
            $table->string('company_name')->nullable();
            $table->string('business_type')->nullable();
            
            // Tenant fields
            $table->foreignId('current_property_id')->nullable()->constrained('properties');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->decimal('monthly_rent', 10, 2)->nullable();
            $table->decimal('security_deposit', 10, 2)->nullable();
            $table->string('guarantor_name')->nullable();
            $table->string('guarantor_phone')->nullable();
            $table->text('guarantor_address')->nullable();
            $table->string('guarantor_id_number')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer_name')->nullable();
            $table->string('employer_phone')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->text('previous_address')->nullable();
            $table->string('marital_status')->nullable();
            $table->integer('family_size')->nullable();
            $table->boolean('has_pets')->default(false);
            $table->text('pet_details')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id',
                'department',
                'joining_date',
                'salary',
                'position',
                'supervisor_id',
                'emergency_contact',
                'emergency_phone',
                'address',
                'birth_date',
                'commercial_register',
                'tax_number',
                'bank_name',
                'bank_account_number',
                'iban',
                'secondary_phone',
                'nationality',
                'ownership_documents',
                'legal_representative',
                'company_name',
                'business_type',
                'current_property_id',
                'contract_start_date',
                'contract_end_date',
                'monthly_rent',
                'security_deposit',
                'guarantor_name',
                'guarantor_phone',
                'guarantor_address',
                'guarantor_id_number',
                'occupation',
                'employer_name',
                'employer_phone',
                'monthly_income',
                'previous_address',
                'marital_status',
                'family_size',
                'has_pets',
                'pet_details',
            ]);
        });
    }
};
