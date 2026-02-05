<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerformanceTestSeeder extends Seeder
{
    /**
     * Seed 1 million unit contracts for performance testing.
     *
     * Structure:
     * - 200 properties
     * - 50 units per property = 10,000 units
     * - 100 contracts per unit = 1,000,000 contracts
     */
    public function run(): void
    {
        $this->command->info('Starting Performance Test Seeder...');

        // Disable foreign key checks for faster inserts
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Get or create test owner
        $owner = User::firstOrCreate(
            ['email' => 'test-owner@test.com'],
            [
                'name' => 'Test Owner',
                'password' => bcrypt('password'),
                'type' => 'owner',
            ]
        );

        // Get or create test tenant
        $tenant = User::firstOrCreate(
            ['email' => 'test-tenant@test.com'],
            [
                'name' => 'Test Tenant',
                'password' => bcrypt('password'),
                'type' => 'tenant',
            ]
        );

        $propertiesCount = 200;
        $unitsPerProperty = 50;
        $contractsPerUnit = 100;

        $this->command->info("Creating {$propertiesCount} properties...");

        // Batch insert properties
        $properties = [];
        $now = now();

        for ($i = 1; $i <= $propertiesCount; $i++) {
            $properties[] = [
                'name' => "عقار اختبار {$i}",
                'owner_id' => $owner->id,
                'status_id' => rand(1, 3),
                'type_id' => rand(1, 4),
                'address' => "شارع الاختبار {$i}",
                'postal_code' => rand(10000, 99999),
                'parking_spots' => rand(5, 20),
                'elevators' => rand(1, 3),
                'build_year' => rand(2000, 2024),
                'floors_count' => rand(3, 15),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('properties')->insert($properties);
        $this->command->info("Created {$propertiesCount} properties.");

        // Get property IDs
        $propertyIds = Property::latest()->take($propertiesCount)->pluck('id')->toArray();

        $this->command->info('Creating units...');

        // Batch insert units
        $units = [];
        $unitId = DB::table('units')->max('id') ?? 0;

        foreach ($propertyIds as $propertyId) {
            for ($j = 1; $j <= $unitsPerProperty; $j++) {
                $unitId++;
                $units[] = [
                    'name' => "وحدة {$j}",
                    'property_id' => $propertyId,
                    'floor_number' => ceil($j / 5),
                    'area_sqm' => rand(80, 250),
                    'rooms_count' => rand(1, 5),
                    'bathrooms_count' => rand(1, 3),
                    'rent_price' => rand(2000, 8000),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Insert in chunks of 1000
                if (count($units) >= 1000) {
                    DB::table('units')->insert($units);
                    $units = [];
                }
            }
        }

        // Insert remaining units
        if (! empty($units)) {
            DB::table('units')->insert($units);
        }

        $totalUnits = $propertiesCount * $unitsPerProperty;
        $this->command->info("Created {$totalUnits} units.");

        // Get all unit IDs with their property IDs
        $unitData = Unit::select('id', 'property_id')->get();

        $this->command->info('Creating contracts (this will take a while)...');

        // Batch insert contracts
        $contracts = [];
        $contractNumber = DB::table('unit_contracts')->max('id') ?? 0;
        $totalContracts = 0;
        $chunkSize = 2000; // MySQL limit: 65535 placeholders / 20 columns ≈ 3276 max

        $statuses = ['draft', 'active', 'expired', 'terminated', 'renewed'];
        $frequencies = ['monthly', 'quarterly', 'semi_annually', 'annually'];
        $methods = ['bank_transfer', 'cash', 'check', 'online'];

        foreach ($unitData as $unit) {
            for ($k = 1; $k <= $contractsPerUnit; $k++) {
                $contractNumber++;

                // Create varied date ranges
                $yearsBack = ($k - 1) * 0.12; // Spread contracts over 12 years
                $startDate = Carbon::now()->subYears($yearsBack)->subMonths(rand(0, 11));
                $durationMonths = [6, 12, 18, 24][array_rand([6, 12, 18, 24])];
                $endDate = (clone $startDate)->addMonths($durationMonths)->subDay();

                // Determine status based on dates
                $status = 'expired';
                if ($endDate->isFuture() && $startDate->isPast()) {
                    $status = 'active';
                } elseif ($startDate->isFuture()) {
                    $status = 'draft';
                }

                // Make some contracts specifically active for testing
                if ($k <= 2) {
                    $startDate = Carbon::now()->subMonths(rand(1, 6));
                    $endDate = Carbon::now()->addMonths(rand(3, 12));
                    $status = 'active';
                }

                $monthlyRent = rand(2000, 8000);

                $contracts[] = [
                    'contract_number' => 'UC-'.$contractNumber,
                    'tenant_id' => $tenant->id,
                    'unit_id' => $unit->id,
                    'property_id' => $unit->property_id,
                    'monthly_rent' => $monthlyRent,
                    'security_deposit' => $monthlyRent,
                    'duration_months' => $durationMonths,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'contract_status' => $status,
                    'payment_frequency' => $frequencies[array_rand($frequencies)],
                    'payment_method' => $methods[array_rand($methods)],
                    'grace_period_days' => [3, 5, 7, 10][array_rand([3, 5, 7, 10])],
                    'late_fee_rate' => rand(0, 5),
                    'utilities_included' => rand(0, 1),
                    'furnished' => rand(0, 1),
                    'evacuation_notice_days' => [30, 60, 90][array_rand([30, 60, 90])],
                    'created_by' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // Insert in chunks
                if (count($contracts) >= $chunkSize) {
                    DB::table('unit_contracts')->insert($contracts);
                    $totalContracts += count($contracts);
                    $this->command->info("Inserted {$totalContracts} contracts...");
                    $contracts = [];
                }
            }
        }

        // Insert remaining contracts
        if (! empty($contracts)) {
            DB::table('unit_contracts')->insert($contracts);
            $totalContracts += count($contracts);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Performance Test Seeder completed!');
        $this->command->info("Created: {$propertiesCount} properties, {$totalUnits} units, {$totalContracts} contracts");
    }
}
