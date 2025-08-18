<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyFeature;

class PropertyFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $propertyFeatures = [
            // Basics Category
            [
                'name_ar' => 'مصعد',
                'name_en' => 'Elevator',
                'slug' => 'elevator',
                'category' => 'basics',
                'icon' => 'heroicon-o-arrow-up-tray',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'يحتوي على مصعد',
                'description_en' => 'Has elevator',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'موقف سيارات',
                'name_en' => 'Parking',
                'slug' => 'parking',
                'category' => 'basics',
                'icon' => 'heroicon-o-truck',
                'requires_value' => true,
                'value_type' => 'number',
                'description_ar' => 'عدد مواقف السيارات المتاحة',
                'description_en' => 'Number of available parking spaces',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'مطبخ مجهز',
                'name_en' => 'Equipped Kitchen',
                'slug' => 'equipped-kitchen',
                'category' => 'basics',
                'icon' => 'heroicon-o-home',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'مطبخ مجهز بالأجهزة',
                'description_en' => 'Kitchen equipped with appliances',
                'is_active' => true,
                'sort_order' => 3,
            ],

            // Amenities Category
            [
                'name_ar' => 'حديقة',
                'name_en' => 'Garden',
                'slug' => 'garden',
                'category' => 'amenities',
                'icon' => 'heroicon-o-leaf',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'يحتوي على حديقة',
                'description_en' => 'Has garden',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'مسبح',
                'name_en' => 'Swimming Pool',
                'slug' => 'swimming-pool',
                'category' => 'amenities',
                'icon' => 'heroicon-o-building-office',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'يحتوي على مسبح',
                'description_en' => 'Has swimming pool',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'صالة رياضية',
                'name_en' => 'Gym',
                'slug' => 'gym',
                'category' => 'amenities',
                'icon' => 'heroicon-o-heart',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'يحتوي على صالة رياضية',
                'description_en' => 'Has gym facility',
                'is_active' => true,
                'sort_order' => 3,
            ],

            // Security Category
            [
                'name_ar' => 'نظام أمني',
                'name_en' => 'Security System',
                'slug' => 'security-system',
                'category' => 'security',
                'icon' => 'heroicon-o-shield-check',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'نظام أمني متكامل',
                'description_en' => 'Integrated security system',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'حارس أمن',
                'name_en' => 'Security Guard',
                'slug' => 'security-guard',
                'category' => 'security',
                'icon' => 'heroicon-o-user-circle',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'يوجد حارس أمن',
                'description_en' => 'Has security guard',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'كاميرات مراقبة',
                'name_en' => 'CCTV Cameras',
                'slug' => 'cctv-cameras',
                'category' => 'security',
                'icon' => 'heroicon-o-video-camera',
                'requires_value' => true,
                'value_type' => 'number',
                'description_ar' => 'عدد كاميرات المراقبة',
                'description_en' => 'Number of CCTV cameras',
                'is_active' => true,
                'sort_order' => 3,
            ],

            // Extras Category
            [
                'name_ar' => 'تكييف مركزي',
                'name_en' => 'Central AC',
                'slug' => 'central-ac',
                'category' => 'extras',
                'icon' => 'heroicon-o-sun',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'تكييف مركزي',
                'description_en' => 'Central air conditioning',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'إنترنت عالي السرعة',
                'name_en' => 'High-Speed Internet',
                'slug' => 'high-speed-internet',
                'category' => 'extras',
                'icon' => 'heroicon-o-wifi',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'إنترنت عالي السرعة',
                'description_en' => 'High-speed internet connection',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'تدفئة مركزية',
                'name_en' => 'Central Heating',
                'slug' => 'central-heating',
                'category' => 'extras',
                'icon' => 'heroicon-o-fire',
                'requires_value' => false,
                'value_type' => 'boolean',
                'description_ar' => 'نظام تدفئة مركزي',
                'description_en' => 'Central heating system',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($propertyFeatures as $propertyFeature) {
            PropertyFeature::updateOrCreate(
                ['slug' => $propertyFeature['slug']],
                $propertyFeature
            );
        }
    }
}