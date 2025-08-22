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
            ['name_ar' => 'اقتصادي', 'name_en' => 'Economy', 'color' => '#9CA3AF', 'icon' => 'heroicon-o-home', 'sort_order' => 1],
            ['name_ar' => 'عادي', 'name_en' => 'Standard', 'color' => '#60A5FA', 'icon' => 'heroicon-o-home-modern', 'sort_order' => 2],
            ['name_ar' => 'مميز', 'name_en' => 'Premium', 'color' => '#F59E0B', 'icon' => 'heroicon-o-sparkles', 'sort_order' => 3],
            ['name_ar' => 'فاخر', 'name_en' => 'Luxury', 'color' => '#EF4444', 'icon' => 'heroicon-o-star', 'sort_order' => 4],
            ['name_ar' => 'VIP', 'name_en' => 'VIP', 'color' => '#8B5CF6', 'icon' => 'heroicon-o-trophy', 'sort_order' => 5],
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