<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // المناطق (Level 1)
        $riyadh = Location::create([
            'name_ar' => 'منطقة الرياض',
            'name_en' => 'Riyadh Region',
            'level' => 1,
            'is_active' => true,
        ]);

        $makkah = Location::create([
            'name_ar' => 'منطقة مكة المكرمة',
            'name_en' => 'Makkah Region',
            'level' => 1,
            'is_active' => true,
        ]);

        $eastern = Location::create([
            'name_ar' => 'المنطقة الشرقية',
            'name_en' => 'Eastern Province',
            'level' => 1,
            'is_active' => true,
        ]);

        // المدن (Level 2)
        $riyadhCity = Location::create([
            'name_ar' => 'الرياض',
            'name_en' => 'Riyadh',
            'level' => 2,
            'parent_id' => $riyadh->id,
            'path' => (string)$riyadh->id,
            'is_active' => true,
        ]);

        $jeddah = Location::create([
            'name_ar' => 'جدة',
            'name_en' => 'Jeddah',
            'level' => 2,
            'parent_id' => $makkah->id,
            'path' => (string)$makkah->id,
            'is_active' => true,
        ]);

        $dammam = Location::create([
            'name_ar' => 'الدمام',
            'name_en' => 'Dammam',
            'level' => 2,
            'parent_id' => $eastern->id,
            'path' => (string)$eastern->id,
            'is_active' => true,
        ]);

        // المراكز (Level 3)
        $olaya = Location::create([
            'name_ar' => 'العليا',
            'name_en' => 'Olaya',
            'level' => 3,
            'parent_id' => $riyadhCity->id,
            'path' => $riyadh->id . '.' . $riyadhCity->id,
            'is_active' => true,
        ]);

        $malaz = Location::create([
            'name_ar' => 'الملز',
            'name_en' => 'Malaz',
            'level' => 3,
            'parent_id' => $riyadhCity->id,
            'path' => $riyadh->id . '.' . $riyadhCity->id,
            'is_active' => true,
        ]);

        $salamah = Location::create([
            'name_ar' => 'السلامة',
            'name_en' => 'Salamah',
            'level' => 3,
            'parent_id' => $jeddah->id,
            'path' => $makkah->id . '.' . $jeddah->id,
            'is_active' => true,
        ]);

        // الأحياء (Level 4)
        Location::create([
            'name_ar' => 'حي البرج',
            'name_en' => 'Al Burj',
            'level' => 4,
            'parent_id' => $olaya->id,
            'path' => $riyadh->id . '.' . $riyadhCity->id . '.' . $olaya->id,
            'is_active' => true,
        ]);

        Location::create([
            'name_ar' => 'حي الصحافة',
            'name_en' => 'As Sahafah',
            'level' => 4,
            'parent_id' => $olaya->id,
            'path' => $riyadh->id . '.' . $riyadhCity->id . '.' . $olaya->id,
            'is_active' => true,
        ]);

        Location::create([
            'name_ar' => 'حي الروضة',
            'name_en' => 'Ar Rawdah',
            'level' => 4,
            'parent_id' => $malaz->id,
            'path' => $riyadh->id . '.' . $riyadhCity->id . '.' . $malaz->id,
            'is_active' => true,
        ]);

        Location::create([
            'name_ar' => 'حي الفيصلية',
            'name_en' => 'Al Faisaliyah',
            'level' => 4,
            'parent_id' => $salamah->id,
            'path' => $makkah->id . '.' . $jeddah->id . '.' . $salamah->id,
            'is_active' => true,
        ]);
    }
}