<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // تعطيل فحص المفاتيح الأجنبية مؤقتاً
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // حذف البيانات القديمة
        Location::truncate();
        
        // إعادة تفعيل فحص المفاتيح الأجنبية
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // إضافة المناطق (المستوى 1)
        $riyadhRegion = Location::create([
            'name_ar' => 'منطقة الرياض',
            'name_en' => 'Riyadh Region',
            'level' => 1,
            'parent_id' => null,
        ]);
        
        $makkahRegion = Location::create([
            'name_ar' => 'منطقة مكة المكرمة',
            'name_en' => 'Makkah Region',
            'level' => 1,
            'parent_id' => null,
        ]);
        
        $easternRegion = Location::create([
            'name_ar' => 'المنطقة الشرقية',
            'name_en' => 'Eastern Region',
            'level' => 1,
            'parent_id' => null,
        ]);
        
        // إضافة المدن (المستوى 2)
        $riyadhCity = Location::create([
            'name_ar' => 'مدينة الرياض',
            'name_en' => 'Riyadh City',
            'level' => 2,
            'parent_id' => $riyadhRegion->id,
        ]);
        
        $jeddahCity = Location::create([
            'name_ar' => 'مدينة جدة',
            'name_en' => 'Jeddah City',
            'level' => 2,
            'parent_id' => $makkahRegion->id,
        ]);
        
        $dammamCity = Location::create([
            'name_ar' => 'مدينة الدمام',
            'name_en' => 'Dammam City',
            'level' => 2,
            'parent_id' => $easternRegion->id,
        ]);
        
        // إضافة المراكز (المستوى 3)
        $northRiyadh = Location::create([
            'name_ar' => 'شمال الرياض',
            'name_en' => 'North Riyadh',
            'level' => 3,
            'parent_id' => $riyadhCity->id,
        ]);
        
        $eastRiyadh = Location::create([
            'name_ar' => 'شرق الرياض',
            'name_en' => 'East Riyadh',
            'level' => 3,
            'parent_id' => $riyadhCity->id,
        ]);
        
        $northJeddah = Location::create([
            'name_ar' => 'شمال جدة',
            'name_en' => 'North Jeddah',
            'level' => 3,
            'parent_id' => $jeddahCity->id,
        ]);
        
        // إضافة الأحياء (المستوى 4)
        Location::create([
            'name_ar' => 'حي النرجس',
            'name_en' => 'Al Narjis District',
            'level' => 4,
            'parent_id' => $northRiyadh->id,
        ]);
        
        Location::create([
            'name_ar' => 'حي الياسمين',
            'name_en' => 'Al Yasmin District',
            'level' => 4,
            'parent_id' => $northRiyadh->id,
        ]);
        
        Location::create([
            'name_ar' => 'حي القدس',
            'name_en' => 'Al Quds District',
            'level' => 4,
            'parent_id' => $eastRiyadh->id,
        ]);
        
        Location::create([
            'name_ar' => 'حي الروضة',
            'name_en' => 'Al Rawdah District',
            'level' => 4,
            'parent_id' => $northJeddah->id,
        ]);
        
        echo "Locations seeded successfully!\n";
    }
}