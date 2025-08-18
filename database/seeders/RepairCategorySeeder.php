<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RepairCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $repairCategories = [
            [
                'name_ar' => 'صيانة عامة',
                'name_en' => 'General Maintenance',
                'slug' => 'general_maintenance',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'description' => 'الصيانة العامة للعقار',
                'affects_property' => true,
                'affects_unit' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'صيانة خاصة',
                'name_en' => 'Special Maintenance',
                'slug' => 'special_maintenance',
                'icon' => 'heroicon-o-cog-6-tooth',
                'description' => 'الصيانة الخاصة بالوحدة',
                'affects_property' => false,
                'affects_unit' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'دفعة حكومية للوحدة',
                'name_en' => 'Government Payment Unit',
                'slug' => 'government_payment_unit',
                'icon' => 'heroicon-o-building-office',
                'description' => 'دفعة حكومية خاصة بالوحدة',
                'affects_property' => false,
                'affects_unit' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'دفعة حكومية للعقار',
                'name_en' => 'Government Payment Property',
                'slug' => 'government_payment_property',
                'icon' => 'heroicon-o-building-office-2',
                'description' => 'دفعة حكومية خاصة بالعقار',
                'affects_property' => true,
                'affects_unit' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'صيانة كهرباء',
                'name_en' => 'Electrical Maintenance',
                'slug' => 'electrical_maintenance',
                'icon' => 'heroicon-o-bolt',
                'description' => 'صيانة الأعمال الكهربائية',
                'affects_property' => true,
                'affects_unit' => true,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name_ar' => 'صيانة سباكة',
                'name_en' => 'Plumbing Maintenance',
                'slug' => 'plumbing_maintenance',
                'icon' => 'heroicon-o-wrench',
                'description' => 'صيانة أعمال السباكة',
                'affects_property' => true,
                'affects_unit' => true,
                'is_active' => true,
                'sort_order' => 6,
            ],
        ];

        foreach ($repairCategories as $category) {
            \App\Models\RepairCategory::create($category);
        }
    }
}
