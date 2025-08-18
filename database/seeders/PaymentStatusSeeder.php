<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentStatuses = [
            [
                'name_ar' => 'مستحقة التحصيل',
                'name_en' => 'Worth Collecting',
                'slug' => 'worth_collecting',
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
                'description' => 'الدفعة مستحقة ولم يتم تحصيلها بعد',
                'is_active' => true,
                'is_paid_status' => false,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'محصلة',
                'name_en' => 'Collected',
                'slug' => 'collected',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'description' => 'تم تحصيل الدفعة بنجاح',
                'is_active' => true,
                'is_paid_status' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'مؤجلة',
                'name_en' => 'Delayed',
                'slug' => 'delayed',
                'color' => 'info',
                'icon' => 'heroicon-o-pause-circle',
                'description' => 'تم تأجيل الدفعة لفترة محددة',
                'is_active' => true,
                'is_paid_status' => false,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'متأخرة',
                'name_en' => 'Overdue',
                'slug' => 'overdue',
                'color' => 'danger',
                'icon' => 'heroicon-o-exclamation-circle',
                'description' => 'الدفعة متأخرة عن موعد الاستحقاق',
                'is_active' => true,
                'is_paid_status' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($paymentStatuses as $status) {
            \App\Models\PaymentStatus::create($status);
        }
    }
}
