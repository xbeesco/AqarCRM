<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractStatus;

class ContractStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'name_ar' => 'مسودة',
                'name_en' => 'Draft',
                'slug' => 'draft',
                'color' => 'gray',
                'icon' => 'heroicon-o-document',
                'description_ar' => 'عقد في مرحلة الإعداد',
                'description_en' => 'Contract in preparation phase',
                'is_active' => true,
                'allows_editing' => true,
                'allows_termination' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'نشط',
                'name_en' => 'Active',
                'slug' => 'active',
                'color' => 'green',
                'icon' => 'heroicon-o-check-circle',
                'description_ar' => 'عقد فعال ومفعل',
                'description_en' => 'Active and effective contract',
                'is_active' => true,
                'allows_editing' => true,
                'allows_termination' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'معلق',
                'name_en' => 'Suspended',
                'slug' => 'suspended',
                'color' => 'yellow',
                'icon' => 'heroicon-o-pause-circle',
                'description_ar' => 'عقد معلق مؤقتاً',
                'description_en' => 'Temporarily suspended contract',
                'is_active' => true,
                'allows_editing' => true,
                'allows_termination' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'منتهي',
                'name_en' => 'Expired',
                'slug' => 'expired',
                'color' => 'orange',
                'icon' => 'heroicon-o-clock',
                'description_ar' => 'عقد منتهي الصلاحية',
                'description_en' => 'Expired contract',
                'is_active' => true,
                'allows_editing' => false,
                'allows_termination' => false,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'مفسوخ',
                'name_en' => 'Terminated',
                'slug' => 'terminated',
                'color' => 'red',
                'icon' => 'heroicon-o-x-circle',
                'description_ar' => 'عقد مفسوخ',
                'description_en' => 'Terminated contract',
                'is_active' => true,
                'allows_editing' => false,
                'allows_termination' => false,
                'sort_order' => 5,
            ],
            [
                'name_ar' => 'مجدد',
                'name_en' => 'Renewed',
                'slug' => 'renewed',
                'color' => 'blue',
                'icon' => 'heroicon-o-arrow-path',
                'description_ar' => 'عقد تم تجديده',
                'description_en' => 'Renewed contract',
                'is_active' => true,
                'allows_editing' => false,
                'allows_termination' => false,
                'sort_order' => 6,
            ],
        ];

        foreach ($statuses as $status) {
            ContractStatus::firstOrCreate(['slug' => $status['slug']], $status);
        }
    }
}