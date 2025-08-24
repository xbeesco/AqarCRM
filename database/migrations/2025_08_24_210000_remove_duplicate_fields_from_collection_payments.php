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
        Schema::table('collection_payments', function (Blueprint $table) {
            // حذف الحقول المكررة
            $table->dropColumn([
                'amount_simple',    // نستخدم amount بدلاً منه
                'date_from',        // نستخدم due_date_start بدلاً منه
                'date_to',          // نستخدم due_date_end بدلاً منه
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_payments', function (Blueprint $table) {
            $table->decimal('amount_simple', 10, 2)->nullable()->after('collection_status');
            $table->date('date_from')->nullable()->after('amount_simple');
            $table->date('date_to')->nullable()->after('date_from');
        });
    }
};