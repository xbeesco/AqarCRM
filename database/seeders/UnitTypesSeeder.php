<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UnitType;

class UnitTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'icon' => 'heroicon-o-home', 'sort_order' => 1],
            ['name_ar' => 'ستوديو', 'name_en' => 'Studio', 'icon' => 'heroicon-o-home-modern', 'sort_order' => 2],
            ['name_ar' => 'دوبلكس', 'name_en' => 'Duplex', 'icon' => 'heroicon-o-building-office-2', 'sort_order' => 3],
            ['name_ar' => 'بنت هاوس', 'name_en' => 'Penthouse', 'icon' => 'heroicon-o-building-office', 'sort_order' => 4],
            ['name_ar' => 'مكتب', 'name_en' => 'Office', 'icon' => 'heroicon-o-briefcase', 'sort_order' => 5],
            ['name_ar' => 'محل تجاري', 'name_en' => 'Shop', 'icon' => 'heroicon-o-shopping-bag', 'sort_order' => 6],
            ['name_ar' => 'مستودع', 'name_en' => 'Warehouse', 'icon' => 'heroicon-o-cube', 'sort_order' => 7],
        ];

        foreach ($types as $type) {
            UnitType::updateOrCreate(
                ['slug' => \Str::slug($type['name_en'])],
                array_merge($type, [
                    'is_active' => true,
                    'description' => null,
                ])
            );
        }
    }
}