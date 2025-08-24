<?php

namespace Database\Seeders;

use App\Models\Expense;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إنشاء نفقات تجريبية متنوعة
        
        // نفقات الصيانة (الأكثر شيوعاً)
        Expense::factory()
            ->count(15)
            ->ofType('maintenance')
            ->create();

        // نفقات حكومية
        Expense::factory()
            ->count(5)
            ->ofType('government')
            ->expensive()
            ->create();

        // فواتير الخدمات
        Expense::factory()
            ->count(10)
            ->ofType('utilities')
            ->thisMonth()
            ->create();

        // مشتريات
        Expense::factory()
            ->count(8)
            ->ofType('purchases')
            ->withDocuments()
            ->create();

        // رواتب
        Expense::factory()
            ->count(6)
            ->ofType('salaries')
            ->expensive()
            ->thisMonth()
            ->create();

        // عمولات
        Expense::factory()
            ->count(3)
            ->ofType('commissions')
            ->create();

        // أخرى
        Expense::factory()
            ->count(7)
            ->ofType('other')
            ->create();

        // إنشاء بعض النفقات بدون إثباتات
        Expense::factory()
            ->count(10)
            ->withoutDocuments()
            ->create();

        // نفقات الشهر الماضي
        Expense::factory()
            ->count(12)
            ->lastMonth()
            ->create();
    }
}