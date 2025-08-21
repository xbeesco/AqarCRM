<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\User;
use App\Models\Location;
use Illuminate\Support\Facades\DB;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get owners
        $owners = User::role('owner')->get();
        
        if ($owners->isEmpty()) {
            $this->command->warn('No owners found. Please run UserTypesSeeder first.');
            return;
        }

        // Get locations - use existing locations from seeder
        $location = Location::where('name_ar', 'الرياض')->first();
        
        if (!$location) {
            $this->command->warn('No location found. Please run LocationSeeder first.');
            return;
        }

        // Insert properties directly using DB to avoid enum issues
        $properties = [
            [
                'name' => 'برج الفيصلية السكني',
                'owner_id' => $owners->random()->id,
                'status' => '1',
                'type' => '1', 
                'location_id' => $location->id,
                'address' => 'شارع الملك فهد، حي العليا',
                'postal_code' => '11564',
                'parking_spots' => 20,
                'elevators' => 2,
                'area_sqm' => 5000,
                'build_year' => 2020,
                'floors_count' => 15,
                'notes' => 'برج سكني فاخر في قلب الرياض',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مجمع النخيل السكني',
                'owner_id' => $owners->random()->id,
                'status' => '1',
                'type' => '2',
                'location_id' => $location->id,
                'address' => 'حي الياسمين، شارع الأمير محمد',
                'postal_code' => '11565',
                'parking_spots' => 30,
                'elevators' => 1,
                'area_sqm' => 3000,
                'build_year' => 2018,
                'floors_count' => 8,
                'notes' => 'مجمع سكني حديث مع جميع الخدمات',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'فيلا الروضة',
                'owner_id' => $owners->random()->id,
                'status' => '1',
                'type' => '1',
                'location_id' => $location->id,
                'address' => 'حي الروضة، شارع 15',
                'postal_code' => '11566',
                'parking_spots' => 4,
                'elevators' => 0,
                'area_sqm' => 800,
                'build_year' => 2015,
                'floors_count' => 2,
                'notes' => 'فيلا فاخرة مع حديقة ومسبح',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مركز الأعمال التجاري',
                'owner_id' => $owners->random()->id,
                'status' => '1',
                'type' => '3',
                'location_id' => $location->id,
                'address' => 'طريق الملك عبدالله، حي الواحة',
                'postal_code' => '11567',
                'parking_spots' => 50,
                'elevators' => 3,
                'area_sqm' => 2000,
                'build_year' => 2019,
                'floors_count' => 6,
                'notes' => 'مركز تجاري يحتوي على محلات ومكاتب',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'عمارة السلام',
                'owner_id' => $owners->random()->id,
                'status' => '1',
                'type' => '2',
                'location_id' => $location->id,
                'address' => 'حي السلام، شارع الإمام سعود',
                'postal_code' => '11568',
                'parking_spots' => 15,
                'elevators' => 1,
                'area_sqm' => 1200,
                'build_year' => 2017,
                'floors_count' => 4,
                'notes' => 'عمارة سكنية متوسطة',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($properties as $property) {
            DB::table('properties')->insert($property);
        }

        $this->command->info('Properties seeded successfully!');
    }
}