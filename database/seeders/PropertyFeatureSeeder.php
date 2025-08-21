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
        // تعطيل فحص المفاتيح الأجنبية مؤقتاً
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // حذف البيانات القديمة
        PropertyFeature::truncate();
        
        // إعادة تفعيل فحص المفاتيح الأجنبية
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $propertyFeatures = [
            // basics category
            [
                'name_ar' => 'مصعد',
                'name_en' => 'Elevator',
                'category' => 'basics',
            ],
            [
                'name_ar' => 'موقف سيارات',
                'name_en' => 'Parking',
                'category' => 'basics',
            ],
            [
                'name_ar' => 'مطبخ مجهز',
                'name_en' => 'Equipped Kitchen',
                'category' => 'basics',
            ],
            
            // amenities category
            [
                'name_ar' => 'حديقة',
                'name_en' => 'Garden',
                'category' => 'amenities',
            ],
            [
                'name_ar' => 'مسبح',
                'name_en' => 'Swimming Pool',
                'category' => 'amenities',
            ],
            [
                'name_ar' => 'صالة رياضية',
                'name_en' => 'Gym',
                'category' => 'amenities',
            ],
            
            // security category
            [
                'name_ar' => 'نظام أمني',
                'name_en' => 'Security System',
                'category' => 'security',
            ],
            [
                'name_ar' => 'حارس أمن',
                'name_en' => 'Security Guard',
                'category' => 'security',
            ],
            [
                'name_ar' => 'كاميرات مراقبة',
                'name_en' => 'CCTV Cameras',
                'category' => 'security',
            ],
            
            // extras category
            [
                'name_ar' => 'تكييف مركزي',
                'name_en' => 'Central AC',
                'category' => 'extras',
            ],
            [
                'name_ar' => 'إنترنت عالي السرعة',
                'name_en' => 'High-Speed Internet',
                'category' => 'extras',
            ],
            [
                'name_ar' => 'تدفئة مركزية',
                'name_en' => 'Central Heating',
                'category' => 'extras',
            ],
        ];

        foreach ($propertyFeatures as $propertyFeature) {
            PropertyFeature::create($propertyFeature);
        }
        
        echo "Property features seeded successfully!\n";
    }
}