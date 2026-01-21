<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('type', 20)->nullable(); // super_admin, admin, employee, owner, tenant
            $table->string('phone')->nullable();
            $table->string('identity_file')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            // Employee-specific fields
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();

            // Contact information
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->date('birth_date')->nullable();

            // Owner-specific fields
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

            // Indexes
            $table->index('type');
            $table->index('phone');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
