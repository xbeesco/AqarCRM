<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additional indexes for better query performance.
     */
    public function up(): void
    {
        // Index for collection_payments queries
        Schema::table('collection_payments', function (Blueprint $table) {
            // For dueForCollection scope and date queries
            $table->index(['collection_date'], 'idx_collection_date');
            $table->index(['property_id', 'due_date_start'], 'idx_property_due_date');
        });

        // Index for supply_payments queries
        Schema::table('supply_payments', function (Blueprint $table) {
            $table->index(['paid_date'], 'idx_paid_date');
        });

        // Index for unit_contracts expired queries
        Schema::table('unit_contracts', function (Blueprint $table) {
            // For expired contracts widget
            $table->index(['contract_status', 'end_date'], 'idx_status_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->dropIndex('idx_collection_date');
            $table->dropIndex('idx_property_due_date');
        });

        Schema::table('supply_payments', function (Blueprint $table) {
            $table->dropIndex('idx_paid_date');
        });

        Schema::table('unit_contracts', function (Blueprint $table) {
            $table->dropIndex('idx_status_end_date');
        });
    }
};
