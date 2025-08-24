<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\UnitCategory;

class UnitCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name_ar' => 'اقتصادي', 'name_en' => 'Economy', 'icon' => 'heroicon-o-home', 'sort_order' => 1],
            ['name_ar' => 'عادي', 'name_en' => 'Standard', 'icon' => 'heroicon-o-home-modern', 'sort_order' => 2],
            ['name_ar' => 'مميز', 'name_en' => 'Premium', 'icon' => 'heroicon-o-sparkles', 'sort_order' => 3],
            ['name_ar' => 'فاخر', 'name_en' => 'Luxury', 'icon' => 'heroicon-o-star', 'sort_order' => 4],
            ['name_ar' => 'VIP', 'name_en' => 'VIP', 'icon' => 'heroicon-o-trophy', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            UnitCategory::updateOrCreate(
                ['slug' => \Str::slug($category['name_en'])],
                array_merge($category, [
                    'is_active' => true,
                    'description' => null,
                ])
            );
        }
    }
}