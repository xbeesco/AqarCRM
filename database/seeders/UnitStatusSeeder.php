<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تعطيل فحص المفاتيح الأجنبية مؤقتاً
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // حذف البيانات القديمة
        \App\Models\UnitStatus::truncate();
        
        // إعادة تفعيل فحص المفاتيح الأجنبية
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $statuses = [
            [
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'slug' => 'available',
                'color' => 'green',
                'icon' => 'heroicon-o-check-circle',
                'description_ar' => 'الوحدة متاحة للإيجار',
                'description_en' => 'Unit is available for rent',
                'is_available' => true,
                'allows_tenant_assignment' => true,
                'requires_maintenance' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'مؤجر',
                'name_en' => 'Occupied',
                'slug' => 'occupied',
                'color' => 'blue',
                'icon' => 'heroicon-o-user',
                'description_ar' => 'الوحدة مؤجرة حالياً',
                'description_en' => 'Unit is currently occupied',
                'is_available' => false,
                'allows_tenant_assignment' => false,
                'requires_maintenance' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'قيد الصيانة',
                'name_en' => 'Under Maintenance',
                'slug' => 'maintenance',
                'color' => 'yellow',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'description_ar' => 'الوحدة تحت الصيانة',
                'description_en' => 'Unit is under maintenance',
                'is_available' => false,
                'allows_tenant_assignment' => false,
                'requires_maintenance' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'محجوز',
                'name_en' => 'Reserved',
                'slug' => 'reserved',
                'color' => 'purple',
                'icon' => 'heroicon-o-clock',
                'description_ar' => 'الوحدة محجوزة لمستأجر معين',
                'description_en' => 'Unit is reserved for specific tenant',
                'is_available' => false,
                'allows_tenant_assignment' => true,
                'requires_maintenance' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'غير متاح',
                'name_en' => 'Unavailable',
                'slug' => 'unavailable',
                'color' => 'red',
                'icon' => 'heroicon-o-x-circle',
                'description_ar' => 'الوحدة غير متاحة للإيجار',
                'description_en' => 'Unit is not available for rent',
                'is_available' => false,
                'allows_tenant_assignment' => false,
                'requires_maintenance' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($statuses as $status) {
            \App\Models\UnitStatus::create($status);
        }
    }
}
