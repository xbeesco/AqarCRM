<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = [
            [
                'name_ar' => 'نقداً',
                'name_en' => 'Cash',
                'slug' => 'cash',
                'icon' => 'heroicon-o-banknotes',
                'description' => 'الدفع نقداً',
                'is_active' => true,
                'requires_reference' => false,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'تحويل بنكي',
                'name_en' => 'Bank Transfer',
                'slug' => 'bank_transfer',
                'icon' => 'heroicon-o-building-library',
                'description' => 'التحويل البنكي المباشر',
                'is_active' => true,
                'requires_reference' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'شيك',
                'name_en' => 'Check',
                'slug' => 'check',
                'icon' => 'heroicon-o-document-text',
                'description' => 'الدفع بالشيك',
                'is_active' => true,
                'requires_reference' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'بطاقة ائتمان',
                'name_en' => 'Credit Card',
                'slug' => 'credit_card',
                'icon' => 'heroicon-o-credit-card',
                'description' => 'الدفع بالبطاقة الائتمانية',
                'is_active' => true,
                'requires_reference' => true,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'محفظة إلكترونية',
                'name_en' => 'Digital Wallet',
                'slug' => 'digital_wallet',
                'icon' => 'heroicon-o-device-phone-mobile',
                'description' => 'الدفع عبر المحفظة الإلكترونية',
                'is_active' => true,
                'requires_reference' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($paymentMethods as $method) {
            \App\Models\PaymentMethod::create($method);
        }
    }
}
