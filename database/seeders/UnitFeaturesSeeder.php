<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UnitFeature;

class UnitFeaturesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // أساسيات
            ['name_ar' => 'تكييف مركزي', 'name_en' => 'Central AC', 'category' => 'basic', 'icon' => 'heroicon-o-bolt', 'sort_order' => 1],
            ['name_ar' => 'تكييف منفصل', 'name_en' => 'Split AC', 'category' => 'basic', 'icon' => 'heroicon-o-bolt', 'sort_order' => 2],
            ['name_ar' => 'سخان مياه', 'name_en' => 'Water Heater', 'category' => 'basic', 'icon' => 'heroicon-o-fire', 'sort_order' => 3],
            ['name_ar' => 'مطبخ مجهز', 'name_en' => 'Equipped Kitchen', 'category' => 'basic', 'icon' => 'heroicon-o-home', 'sort_order' => 4],
            ['name_ar' => 'خزائن مدمجة', 'name_en' => 'Built-in Closets', 'category' => 'basic', 'icon' => 'heroicon-o-archive-box', 'sort_order' => 5],
            
            // مرافق
            ['name_ar' => 'موقف خاص', 'name_en' => 'Private Parking', 'category' => 'amenities', 'icon' => 'heroicon-o-truck', 'sort_order' => 10],
            ['name_ar' => 'مصعد', 'name_en' => 'Elevator', 'category' => 'amenities', 'icon' => 'heroicon-o-arrow-up', 'sort_order' => 11],
            ['name_ar' => 'حديقة خاصة', 'name_en' => 'Private Garden', 'category' => 'amenities', 'icon' => 'heroicon-o-sparkles', 'sort_order' => 12],
            ['name_ar' => 'مسبح', 'name_en' => 'Swimming Pool', 'category' => 'amenities', 'icon' => 'heroicon-o-beaker', 'sort_order' => 13],
            ['name_ar' => 'صالة رياضية', 'name_en' => 'Gym', 'category' => 'amenities', 'icon' => 'heroicon-o-heart', 'sort_order' => 14],
            
            // أمان
            ['name_ar' => 'نظام إنذار', 'name_en' => 'Alarm System', 'category' => 'safety', 'icon' => 'heroicon-o-bell-alert', 'sort_order' => 20],
            ['name_ar' => 'كاميرات مراقبة', 'name_en' => 'CCTV', 'category' => 'safety', 'icon' => 'heroicon-o-video-camera', 'sort_order' => 21],
            ['name_ar' => 'أمن 24/7', 'name_en' => '24/7 Security', 'category' => 'safety', 'icon' => 'heroicon-o-shield-check', 'sort_order' => 22],
            ['name_ar' => 'بوابة إلكترونية', 'name_en' => 'Electronic Gate', 'category' => 'safety', 'icon' => 'heroicon-o-lock-closed', 'sort_order' => 23],
            
            // إضافات
            ['name_ar' => 'إنترنت', 'name_en' => 'Internet', 'category' => 'services', 'icon' => 'heroicon-o-wifi', 'sort_order' => 30],
            ['name_ar' => 'خدمة تنظيف', 'name_en' => 'Cleaning Service', 'category' => 'services', 'icon' => 'heroicon-o-sparkles', 'sort_order' => 31],
            ['name_ar' => 'صيانة دورية', 'name_en' => 'Regular Maintenance', 'category' => 'services', 'icon' => 'heroicon-o-wrench-screwdriver', 'sort_order' => 32],
            ['name_ar' => 'استقبال', 'name_en' => 'Reception', 'category' => 'services', 'icon' => 'heroicon-o-user-group', 'sort_order' => 33],
        ];

        foreach ($features as $feature) {
            UnitFeature::updateOrCreate(
                ['slug' => \Str::slug($feature['name_en'])],
                array_merge($feature, [
                    'is_active' => true,
                    'requires_value' => false,
                    'value_type' => null,
                ])
            );
        }
    }
}