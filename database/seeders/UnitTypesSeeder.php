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
            ['name_ar' => 'فيلا', 'name_en' => 'Villa', 'icon' => 'heroicon-o-home-modern', 'sort_order' => 2],
            ['name_ar' => 'مكتب', 'name_en' => 'Office', 'icon' => 'heroicon-o-briefcase', 'sort_order' => 3],
            ['name_ar' => 'محل تجاري', 'name_en' => 'Shop', 'icon' => 'heroicon-o-shopping-bag', 'sort_order' => 4],
            ['name_ar' => 'مستودع', 'name_en' => 'Warehouse', 'icon' => 'heroicon-o-cube', 'sort_order' => 5],
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