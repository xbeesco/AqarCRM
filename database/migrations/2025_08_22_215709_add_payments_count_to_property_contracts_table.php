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
        Schema::table('property_contracts', function (Blueprint $table) {
            $table->integer('payments_count')->nullable()->after('payment_frequency')->comment('عدد الدفعات المحسوب بناءً على مدة العقد وتكرار التوريد');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_contracts', function (Blueprint $table) {
            $table->dropColumn('payments_count');
        });
    }
};
