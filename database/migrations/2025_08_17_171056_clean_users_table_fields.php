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
        // Remove unwanted columns one by one to avoid errors
        Schema::table('users', function (Blueprint $table) {
            $columnsToRemove = [
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
                'pet_details'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible
    }
};