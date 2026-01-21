<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->text('desc')->comment('توصيف النفقة');
            $table->string('type', 50)->comment('نوع النفقة');
            $table->decimal('cost', 10, 2)->comment('إجمالي المبلغ');
            $table->date('date')->comment('تاريخ النفقة');
            $table->json('docs')->nullable()->comment('الإثباتات والوثائق');
            $table->string('subject_type')->nullable()->comment('نوع الكيان: App\\Models\\Property أو App\\Models\\Unit');
            $table->unsignedBigInteger('subject_id')->nullable()->comment('معرف الكيان');
            $table->timestamps();

            $table->index('type');
            $table->index('date');
            $table->index(['date', 'type']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
