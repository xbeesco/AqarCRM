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
        $propertyStatuses = [
            [
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'slug' => 'available',
                'color' => 'green',
                'icon' => 'heroicon-o-check-circle',
                'description_ar' => 'العقار متاح للإيجار',
                'description_en' => 'Property is available for rent',
                'is_available' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'مؤجر',
                'name_en' => 'Rented',
                'slug' => 'rented',
                'color' => 'blue',
                'icon' => 'heroicon-o-home',
                'description_ar' => 'العقار مؤجر حالياً',
                'description_en' => 'Property is currently rented',
                'is_available' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'قيد الصيانة',
                'name_en' => 'Under Maintenance',
                'slug' => 'under-maintenance',
                'color' => 'yellow',
                'icon' => 'heroicon-o-wrench-screwdriver',
                'description_ar' => 'العقار قيد الصيانة والإصلاح',
                'description_en' => 'Property is under maintenance and repair',
                'is_available' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'محجوز',
                'name_en' => 'Reserved',
                'slug' => 'reserved',
                'color' => 'orange',
                'icon' => 'heroicon-o-clock',
                'description_ar' => 'العقار محجوز للمستأجر',
                'description_en' => 'Property is reserved for tenant',
                'is_available' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'غير متاح',
                'name_en' => 'Unavailable',
                'slug' => 'unavailable',
                'color' => 'red',
                'icon' => 'heroicon-o-x-circle',
                'description_ar' => 'العقار غير متاح للإيجار',
                'description_en' => 'Property is unavailable for rent',
                'is_available' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($propertyStatuses as $propertyStatus) {
            PropertyStatus::updateOrCreate(
                ['slug' => $propertyStatus['slug']],
                $propertyStatus
            );
        }
    }
}