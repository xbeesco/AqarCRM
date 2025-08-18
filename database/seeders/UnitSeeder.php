<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Unit;
use App\Models\Property;
use App\Models\UnitStatus;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first property and available status
        $property = Property::first();
        $availableStatus = UnitStatus::where('slug', 'available')->first();
        $occupiedStatus = UnitStatus::where('slug', 'occupied')->first();
        
        if (!$property || !$availableStatus) {
            $this->command->warn('Properties or UnitStatuses not found. Please run PropertySeeder and UnitStatusSeeder first.');
            return;
        }

        $units = [
            [
                'property_id' => $property->id,
                'unit_number' => '101',
                'floor_number' => 1,
                'area_sqm' => 85.50,
                'rooms_count' => 2,
                'bathrooms_count' => 1,
                'rent_price' => 2500.00,
                'unit_type' => 'apartment',
                'unit_ranking' => 'standard',
                'direction' => 'north',
                'view_type' => 'street',
                'status_id' => $availableStatus->id,
                'current_tenant_id' => null,
                'furnished' => false,
                'has_balcony' => true,
                'has_parking' => true,
                'has_storage' => false,
                'has_maid_room' => false,
                'notes' => 'شقة بحالة ممتازة مع إطلالة جميلة',
                'available_from' => now()->toDateString(),
                'is_active' => true,
            ],
            [
                'property_id' => $property->id,
                'unit_number' => '102',
                'floor_number' => 1,
                'area_sqm' => 95.75,
                'rooms_count' => 3,
                'bathrooms_count' => 2,
                'rent_price' => 3200.00,
                'unit_type' => 'apartment',
                'unit_ranking' => 'premium',
                'direction' => 'south',
                'view_type' => 'garden',
                'status_id' => $occupiedStatus?->id ?: $availableStatus->id,
                'current_tenant_id' => null,
                'furnished' => true,
                'has_balcony' => true,
                'has_parking' => true,
                'has_storage' => true,
                'has_maid_room' => false,
                'notes' => 'شقة مفروشة بالكامل مع إطلالة على الحديقة',
                'available_from' => null,
                'is_active' => true,
            ],
            [
                'property_id' => $property->id,
                'unit_number' => '201',
                'floor_number' => 2,
                'area_sqm' => 120.00,
                'rooms_count' => 4,
                'bathrooms_count' => 3,
                'rent_price' => 4500.00,
                'unit_type' => 'duplex',
                'unit_ranking' => 'luxury',
                'direction' => 'northeast',
                'view_type' => 'city',
                'status_id' => $availableStatus->id,
                'current_tenant_id' => null,
                'furnished' => false,
                'has_balcony' => true,
                'has_parking' => true,
                'has_storage' => true,
                'has_maid_room' => true,
                'notes' => 'دوبلكس فاخر مع غرفة خادمة وإطلالة على المدينة',
                'available_from' => now()->addDays(30)->toDateString(),
                'is_active' => true,
            ],
            [
                'property_id' => $property->id,
                'unit_number' => 'S01',
                'floor_number' => 0,
                'area_sqm' => 45.00,
                'rooms_count' => 0,
                'bathrooms_count' => 1,
                'rent_price' => 1800.00,
                'unit_type' => 'studio',
                'unit_ranking' => 'economy',
                'direction' => 'west',
                'view_type' => 'courtyard',
                'status_id' => $availableStatus->id,
                'current_tenant_id' => null,
                'furnished' => true,
                'has_balcony' => false,
                'has_parking' => false,
                'has_storage' => false,
                'has_maid_room' => false,
                'notes' => 'ستوديو صغير ومفروش مناسب للأفراد',
                'available_from' => now()->toDateString(),
                'is_active' => true,
            ],
            [
                'property_id' => $property->id,
                'unit_number' => 'P01',
                'floor_number' => 3,
                'area_sqm' => 200.00,
                'rooms_count' => 5,
                'bathrooms_count' => 4,
                'rent_price' => 8000.00,
                'unit_type' => 'penthouse',
                'unit_ranking' => 'luxury',
                'direction' => 'south',
                'view_type' => 'sea',
                'status_id' => $availableStatus->id,
                'current_tenant_id' => null,
                'furnished' => false,
                'has_balcony' => true,
                'has_parking' => true,
                'has_storage' => true,
                'has_maid_room' => true,
                'notes' => 'بنت هاوس فاخر مع إطلالة بحرية رائعة',
                'available_from' => now()->addDays(60)->toDateString(),
                'is_active' => true,
            ],
        ];

        foreach ($units as $unitData) {
            Unit::create($unitData);
        }

        $this->command->info('Sample units created successfully.');
    }
}