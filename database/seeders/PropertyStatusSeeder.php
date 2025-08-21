<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PropertyStatus;

class PropertyStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تعطيل فحص المفاتيح الأجنبية مؤقتاً
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // حذف البيانات القديمة
        PropertyStatus::truncate();
        
        // إعادة تفعيل فحص المفاتيح الأجنبية
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $propertyStatuses = [
            [
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'color' => '#10b981', // green
            ],
            [
                'name_ar' => 'مؤجر',
                'name_en' => 'Rented',
                'color' => '#3b82f6', // blue
            ],
            [
                'name_ar' => 'قيد الصيانة',
                'name_en' => 'Under Maintenance',
                'color' => '#eab308', // yellow
            ],
            [
                'name_ar' => 'محجوز',
                'name_en' => 'Reserved',
                'color' => '#f97316', // orange
            ],
            [
                'name_ar' => 'غير متاح',
                'name_en' => 'Unavailable',
                'color' => '#ef4444', // red
            ],
        ];

        foreach ($propertyStatuses as $propertyStatus) {
            PropertyStatus::create($propertyStatus);
        }
        
        echo "Property statuses seeded successfully!\n";
    }
}