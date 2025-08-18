<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Property;
use App\Models\PropertyType;
use App\Models\PropertyStatus;
use App\Models\User;
use App\Models\Location;

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

        // Get property types and statuses
        $types = PropertyType::all();
        $statuses = PropertyStatus::all();
        
        if ($types->isEmpty() || $statuses->isEmpty()) {
            $this->command->warn('Property types or statuses not found. Please run seeders first.');
            return;
        }

        // Create sample location if not exists
        $location = Location::firstOrCreate(
            ['name' => 'الرياض'],
            [
                'parent_id' => null,
                'level' => 1,
                'path' => 'الرياض'
            ]
        );

        $properties = [
            [
                'name' => 'برج الفيصلية السكني',
                'code' => 'PROP-001',
                'address' => 'شارع الملك فهد، حي العليا',
                'total_units' => 50,
                'description' => 'برج سكني فاخر في قلب الرياض',
            ],
            [
                'name' => 'مجمع النخيل السكني',
                'code' => 'PROP-002',
                'address' => 'شارع الأمير محمد بن سلمان، حي الياسمين',
                'total_units' => 30,
                'description' => 'مجمع سكني حديث مع جميع الخدمات',
            ],
            [
                'name' => 'فيلا الروضة',
                'code' => 'PROP-003',
                'address' => 'حي الروضة، شارع 15',
                'total_units' => 1,
                'description' => 'فيلا فاخرة مع حديقة ومسبح',
            ],
            [
                'name' => 'مركز الأعمال التجاري',
                'code' => 'PROP-004',
                'address' => 'طريق الملك عبدالله، حي الواحة',
                'total_units' => 20,
                'description' => 'مركز تجاري يحتوي على محلات ومكاتب',
            ],
            [
                'name' => 'عمارة السلام',
                'code' => 'PROP-005',
                'address' => 'حي السلام، شارع الإمام سعود',
                'total_units' => 12,
                'description' => 'عمارة سكنية متوسطة',
            ],
        ];

        foreach ($properties as $index => $propertyData) {
            Property::create([
                'name' => $propertyData['name'],
                'code' => $propertyData['code'],
                'address' => $propertyData['address'],
                'total_units' => $propertyData['total_units'],
                'description' => $propertyData['description'],
                'owner_id' => $owners->random()->id,
                'location_id' => $location->id,
                'property_type_id' => $types->random()->id,
                'property_status_id' => $statuses->where('slug', 'available')->first()->id ?? $statuses->first()->id,
                'built_year' => rand(2010, 2023),
                'total_area' => rand(500, 5000),
                'building_area' => rand(400, 4000),
                'floors_count' => rand(1, 20),
                'parking_spots' => rand(10, 100),
            ]);
        }

        $this->command->info('Properties seeded successfully!');
    }
}