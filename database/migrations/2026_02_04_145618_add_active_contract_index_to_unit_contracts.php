<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Composite index for efficiently querying active contracts:
     * WHERE unit_id = ? AND contract_status = 'active'
     *   AND start_date <= NOW() AND end_date >= NOW()
     */
    public function up(): void
    {
        Schema::table('unit_contracts', function (Blueprint $table) {
            // Composite index for active contract lookup by unit
            $table->index(
                ['unit_id', 'contract_status', 'start_date', 'end_date'],
                'idx_unit_active_contract'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_contracts', function (Blueprint $table) {
            $table->dropIndex('idx_unit_active_contract');
        });
    }
};
