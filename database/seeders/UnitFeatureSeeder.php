<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'name_ar' => 'بلكونة',
                'name_en' => 'Balcony',
                'slug' => 'balcony',
                'category' => 'basic',
                'icon' => 'heroicon-o-building-storefront',
                'description_ar' => 'بلكونة خارجية للوحدة',
                'description_en' => 'External balcony for the unit',
                'requires_value' => false,
                'value_type' => null,
                'value_options' => null,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'موقف سيارة',
                'name_en' => 'Parking Space',
                'slug' => 'parking-space',
                'category' => 'basic',
                'icon' => 'heroicon-o-truck',
                'description_ar' => 'موقف سيارة مخصص للوحدة',
                'description_en' => 'Dedicated parking space for the unit',
                'requires_value' => true,
                'value_type' => 'select',
                'value_options' => [
                    'covered' => 'مغطى / Covered',
                    'open' => 'مكشوف / Open',
                    'underground' => 'تحت الأرض / Underground'
                ],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'مخزن',
                'name_en' => 'Storage Room',
                'slug' => 'storage-room',
                'category' => 'basic',
                'icon' => 'heroicon-o-archive-box',
                'description_ar' => 'غرفة تخزين إضافية',
                'description_en' => 'Additional storage room',
                'requires_value' => true,
                'value_type' => 'number',
                'value_options' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'حديقة',
                'name_en' => 'Garden',
                'slug' => 'garden',
                'category' => 'amenities',
                'icon' => 'heroicon-o-sun',
                'description_ar' => 'حديقة خاصة أو مشتركة',
                'description_en' => 'Private or shared garden',
                'requires_value' => true,
                'value_type' => 'select',
                'value_options' => [
                    'private' => 'خاصة / Private',
                    'shared' => 'مشتركة / Shared'
                ],
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'غرفة خادمة',
                'name_en' => 'Maid Room',
                'slug' => 'maid-room',
                'category' => 'basic',
                'icon' => 'heroicon-o-home',
                'description_ar' => 'غرفة خادمة مع حمام',
                'description_en' => 'Maid room with bathroom',
                'requires_value' => false,
                'value_type' => null,
                'value_options' => null,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name_ar' => 'غرفة غسيل',
                'name_en' => 'Laundry Room',
                'slug' => 'laundry-room',
                'category' => 'amenities',
                'icon' => 'heroicon-o-square-3-stack-3d',
                'description_ar' => 'غرفة مخصصة للغسيل',
                'description_en' => 'Dedicated laundry room',
                'requires_value' => false,
                'value_type' => null,
                'value_options' => null,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name_ar' => 'مصعد',
                'name_en' => 'Elevator',
                'slug' => 'elevator',
                'category' => 'amenities',
                'icon' => 'heroicon-o-arrow-up-circle',
                'description_ar' => 'مصعد في المبنى',
                'description_en' => 'Elevator in the building',
                'requires_value' => false,
                'value_type' => null,
                'value_options' => null,
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name_ar' => 'نظام أمني',
                'name_en' => 'Security System',
                'slug' => 'security-system',
                'category' => 'safety',
                'icon' => 'heroicon-o-shield-check',
                'description_ar' => 'نظام أمني متطور',
                'description_en' => 'Advanced security system',
                'requires_value' => true,
                'value_type' => 'select',
                'value_options' => [
                    'cameras' => 'كاميرات / Cameras',
                    'alarm' => 'إنذار / Alarm',
                    'access_control' => 'التحكم في الدخول / Access Control',
                    'full_system' => 'نظام متكامل / Full System'
                ],
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name_ar' => 'مسبح',
                'name_en' => 'Swimming Pool',
                'slug' => 'swimming-pool',
                'category' => 'luxury',
                'icon' => 'heroicon-o-beaker',
                'description_ar' => 'مسبح خاص أو مشترك',
                'description_en' => 'Private or shared swimming pool',
                'requires_value' => true,
                'value_type' => 'select',
                'value_options' => [
                    'private' => 'خاص / Private',
                    'shared' => 'مشترك / Shared'
                ],
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name_ar' => 'خدمة تنظيف',
                'name_en' => 'Cleaning Service',
                'slug' => 'cleaning-service',
                'category' => 'services',
                'icon' => 'heroicon-o-sparkles',
                'description_ar' => 'خدمة تنظيف دورية',
                'description_en' => 'Regular cleaning service',
                'requires_value' => true,
                'value_type' => 'select',
                'value_options' => [
                    'weekly' => 'أسبوعي / Weekly',
                    'bi_weekly' => 'كل أسبوعين / Bi-weekly',
                    'monthly' => 'شهري / Monthly'
                ],
                'is_active' => true,
                'sort_order' => 10,
            ],
        ];

        foreach ($features as $feature) {
            \App\Models\UnitFeature::create($feature);
        }
    }
}
