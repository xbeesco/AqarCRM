<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyType;

class PropertyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تعطيل فحص المفاتيح الأجنبية مؤقتاً
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // حذف البيانات القديمة
        PropertyType::truncate();
        
        // إعادة تفعيل فحص المفاتيح الأجنبية
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $propertyTypes = [
            [
                'name_ar' => 'فيلا',
                'name_en' => 'Villa',
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'دور',
                'name_en' => 'Floor',
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'محل تجاري',
                'name_en' => 'Shop',
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'مكتب',
                'name_en' => 'Office',
                'sort_order' => 5,
            ],
            [
                'name_ar' => 'مستودع',
                'name_en' => 'Warehouse',
                'sort_order' => 6,
            ],
            [
                'name_ar' => 'أرض',
                'name_en' => 'Land',
                'sort_order' => 7,
            ],
            [
                'name_ar' => 'عمارة',
                'name_en' => 'Building',
                'sort_order' => 8,
            ],
            [
                'name_ar' => 'مجمع سكني',
                'name_en' => 'Residential Complex',
                'sort_order' => 9,
            ],
            [
                'name_ar' => 'مزرعة',
                'name_en' => 'Farm',
                'sort_order' => 10,
            ],
        ];

        foreach ($propertyTypes as $propertyType) {
            PropertyType::create($propertyType);
        }
        
        echo "Property types seeded successfully!\n";
    }
}