<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use App\Models\City;
use App\Models\District;
use App\Models\Neighborhood;

class LocationsSeeder extends Seeder
{
    public function run(): void
    {
        // Delete any existing test data first
        Country::truncate();
        
        // Create countries
        $egypt = Country::create([
            'name' => 'مصر',
            'name_ar' => 'مصر',
            'name_en' => 'Egypt',
            'code' => 'EG',
            'is_active' => true
        ]);

        $saudi = Country::create([
            'name' => 'السعودية',
            'name_ar' => 'السعودية',
            'name_en' => 'Saudi Arabia',
            'code' => 'SA',
            'is_active' => true
        ]);

        $uae = Country::create([
            'name' => 'الإمارات',
            'name_ar' => 'الإمارات العربية المتحدة',
            'name_en' => 'United Arab Emirates',
            'code' => 'UAE',
            'is_active' => true
        ]);

        // Create cities in Egypt
        $cairo = City::create([
            'name' => 'القاهرة',
            'name_ar' => 'القاهرة',
            'name_en' => 'Cairo',
            'parent_id' => $egypt->id,
            'is_active' => true
        ]);

        $alexandria = City::create([
            'name' => 'الإسكندرية',
            'name_ar' => 'الإسكندرية',
            'name_en' => 'Alexandria',
            'parent_id' => $egypt->id,
            'is_active' => true
        ]);

        // Create cities in Saudi Arabia
        $riyadh = City::create([
            'name' => 'الرياض',
            'name_ar' => 'الرياض',
            'name_en' => 'Riyadh',
            'parent_id' => $saudi->id,
            'is_active' => true
        ]);

        $jeddah = City::create([
            'name' => 'جدة',
            'name_ar' => 'جدة',
            'name_en' => 'Jeddah',
            'parent_id' => $saudi->id,
            'is_active' => true
        ]);

        // Create cities in UAE
        $dubai = City::create([
            'name' => 'دبي',
            'name_ar' => 'دبي',
            'name_en' => 'Dubai',
            'parent_id' => $uae->id,
            'is_active' => true
        ]);

        $abudhabi = City::create([
            'name' => 'أبوظبي',
            'name_ar' => 'أبوظبي',
            'name_en' => 'Abu Dhabi',
            'parent_id' => $uae->id,
            'is_active' => true
        ]);

        // Create districts in Cairo
        $nasr_city = District::create([
            'name' => 'مدينة نصر',
            'name_ar' => 'مدينة نصر',
            'name_en' => 'Nasr City',
            'parent_id' => $cairo->id,
            'is_active' => true
        ]);

        $maadi = District::create([
            'name' => 'المعادي',
            'name_ar' => 'المعادي',
            'name_en' => 'Maadi',
            'parent_id' => $cairo->id,
            'is_active' => true
        ]);

        // Create districts in Riyadh
        $olaya = District::create([
            'name' => 'العليا',
            'name_ar' => 'العليا',
            'name_en' => 'Olaya',
            'parent_id' => $riyadh->id,
            'is_active' => true
        ]);

        $malaz = District::create([
            'name' => 'الملز',
            'name_ar' => 'الملز',
            'name_en' => 'Malaz',
            'parent_id' => $riyadh->id,
            'is_active' => true
        ]);

        // Create neighborhoods in Nasr City
        Neighborhood::create([
            'name' => 'الحي الأول',
            'name_ar' => 'الحي الأول',
            'name_en' => 'First District',
            'parent_id' => $nasr_city->id,
            'is_active' => true
        ]);

        Neighborhood::create([
            'name' => 'الحي السابع',
            'name_ar' => 'الحي السابع',
            'name_en' => 'Seventh District',
            'parent_id' => $nasr_city->id,
            'is_active' => true
        ]);

        // Create neighborhoods in Olaya
        Neighborhood::create([
            'name' => 'برج العليا',
            'name_ar' => 'برج العليا',
            'name_en' => 'Olaya Tower',
            'parent_id' => $olaya->id,
            'is_active' => true
        ]);

        Neighborhood::create([
            'name' => 'مركز الملك فهد',
            'name_ar' => 'مركز الملك فهد',
            'name_en' => 'King Fahd Center',
            'parent_id' => $olaya->id,
            'is_active' => true
        ]);
    }
}