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
        $propertyTypes = [
            [
                'name_ar' => 'فيلا',
                'name_en' => 'Villa',
                'slug' => 'villa',
                'icon' => 'heroicon-o-home',
                'description_ar' => 'فيلا سكنية منفصلة',
                'description_en' => 'Detached residential villa',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'slug' => 'apartment',
                'icon' => 'heroicon-o-building-office-2',
                'description_ar' => 'شقة سكنية في مبنى',
                'description_en' => 'Residential apartment in building',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'محل تجاري',
                'name_en' => 'Commercial Store',
                'slug' => 'commercial-store',
                'icon' => 'heroicon-o-building-storefront',
                'description_ar' => 'محل تجاري للأنشطة التجارية',
                'description_en' => 'Commercial store for business activities',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'مكتب',
                'name_en' => 'Office',
                'slug' => 'office',
                'icon' => 'heroicon-o-building-office',
                'description_ar' => 'مكتب إداري أو تجاري',
                'description_en' => 'Administrative or commercial office',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'مستودع',
                'name_en' => 'Warehouse',
                'slug' => 'warehouse',
                'icon' => 'heroicon-o-cube',
                'description_ar' => 'مستودع للتخزين والإمداد',
                'description_en' => 'Warehouse for storage and supply',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name_ar' => 'أرض',
                'name_en' => 'Land',
                'slug' => 'land',
                'icon' => 'heroicon-o-map',
                'description_ar' => 'قطعة أرض للبناء أو الاستثمار',
                'description_en' => 'Land plot for construction or investment',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name_ar' => 'عمارة',
                'name_en' => 'Building',
                'slug' => 'building',
                'icon' => 'heroicon-o-building-office-2',
                'description_ar' => 'عمارة سكنية أو تجارية',
                'description_en' => 'Residential or commercial building',
                'is_active' => true,
                'sort_order' => 7,
            ],
        ];

        foreach ($propertyTypes as $propertyType) {
            PropertyType::updateOrCreate(
                ['slug' => $propertyType['slug']],
                $propertyType
            );
        }
    }
}