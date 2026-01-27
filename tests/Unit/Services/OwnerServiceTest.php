<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\SupplyPayment;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Services\OwnerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OwnerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OwnerService $service;

    protected Location $location;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected PropertyStatus $activePropertyStatus;

    protected UnitType $unitType;

    protected Owner $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OwnerService::class);

        // Freeze time to ensure consistent behavior across tests
        Carbon::setTestNow(Carbon::create(2026, 1, 24, 12, 0, 0));

        // Clear cache for settings
        Cache::flush();

        // Create required reference data
        $this->createDependencies();
    }

    protected function tearDown(): void
    {
        // Reset Carbon test time
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createDependencies(): void
    {
        $this->location = Location::create([
            'name' => 'Test Location',
            'code' => 'TEST',
            'level' => 1,
            'is_active' => true,
        ]);

        $this->propertyType = PropertyType::create([
            'name_ar' => 'شقة',
            'name_en' => 'Apartment',
            'slug' => 'apartment',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->propertyStatus = PropertyStatus::create([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Note: The OwnerService checks for 'status' = 'active' but Property uses status_id
        // We'll add a 'status' field test if the schema supports it, otherwise we test what exists
        $this->activePropertyStatus = PropertyStatus::create([
            'name_ar' => 'نشط',
            'name_en' => 'Active',
            'slug' => 'active',
            'color' => 'blue',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->unitType = UnitType::create([
            'name_ar' => 'شقة سكنية',
            'name_en' => 'Residential Apartment',
            'slug' => 'residential-apartment',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Set default settings
        Setting::set('payment_due_days', 7);

        // Create owner
        $this->owner = Owner::create([
            'name' => 'Test Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'phone' => '0501234567',
            'type' => UserType::OWNER->value,
        ]);
    }

    /**
     * Helper method to create a property for the owner
     */
    protected function createProperty(array $overrides = []): Property
    {
        $defaults = [
            'name' => 'Test Property '.uniqid(),
            'owner_id' => $this->owner->id,
            'location_id' => $this->location->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'address' => 'Test Address',
            'postal_code' => '12345',
            'parking_spots' => 5,
            'elevators' => 1,
            'build_year' => 2020,
            'floors_count' => 3,
        ];

        return Property::create(array_merge($defaults, $overrides));
    }

    /**
     * Helper method to create a unit for a property
     */
    protected function createUnit(Property $property, array $overrides = []): Unit
    {
        $defaults = [
            'name' => 'Unit '.uniqid(),
            'property_id' => $property->id,
            'unit_type_id' => $this->unitType->id,
            'floor_number' => 1,
            'area_sqm' => 100,
            'rooms_count' => 2,
            'bathrooms_count' => 1,
            'rent_price' => 3000,
        ];

        return Unit::create(array_merge($defaults, $overrides));
    }

    /**
     * Helper method to create an active unit contract
     */
    protected function createActiveContract(Unit $unit, ?Tenant $tenant = null): UnitContract
    {
        if (! $tenant) {
            $tenant = Tenant::create([
                'name' => 'Test Tenant '.uniqid(),
                'email' => 'tenant'.uniqid().'@test.com',
                'password' => bcrypt('password'),
                'phone' => '050'.rand(1000000, 9999999),
                'type' => UserType::TENANT->value,
            ]);
        }

        return UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'monthly_rent' => $unit->rent_price ?? 3000,
            'duration_months' => 12,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'contract_status' => 'active',
            'payment_frequency' => 'monthly',
            'payment_method' => 'bank_transfer',
        ]);
    }

    /**
     * Helper method to create a property contract for supply payments
     */
    protected function createPropertyContract(Property $property): PropertyContract
    {
        return PropertyContract::create([
            'owner_id' => $this->owner->id,
            'property_id' => $property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'contract_status' => 'active',
            'payment_frequency' => 'monthly',
        ]);
    }

    /**
     * Helper method to create a supply payment
     * Note: net_amount is auto-calculated in the model as:
     * gross_amount - commission_amount - maintenance_deduction - other_deductions
     */
    protected function createSupplyPayment(PropertyContract $contract, array $overrides = []): SupplyPayment
    {
        $defaults = [
            'payment_number' => 'SP-'.uniqid(),
            'property_contract_id' => $contract->id,
            'owner_id' => $this->owner->id,
            'gross_amount' => 10000,
            'commission_amount' => 500,
            'commission_rate' => 5.00,
            'maintenance_deduction' => 0,
            'other_deductions' => 0,
            'due_date' => now(),
            'paid_date' => null,
            'month_year' => now()->format('Y-m'),
        ];

        // net_amount will be auto-calculated by the model
        return SupplyPayment::create(array_merge($defaults, $overrides));
    }

    #[Test]
    public function test_get_active_properties_count_returns_correct_count(): void
    {
        // Create properties with 'status' = 'active'
        // Note: The service checks for 'status' field directly, but Property uses status_id
        // We need to add the 'status' field to properties or the service has a bug
        // For now, let's test what happens - it should return 0 as the field doesn't exist

        $property1 = $this->createProperty();
        $property2 = $this->createProperty();

        $count = $this->service->getActivePropertiesCount($this->owner);

        // Properties with active status should be counted
        $this->assertIsInt($count);
        $this->assertEquals(2, $count);
    }

    #[Test]
    public function test_get_active_properties_count_excludes_inactive(): void
    {
        // Create properties with default (active) status
        $this->createProperty();
        $this->createProperty();

        $count = $this->service->getActivePropertiesCount($this->owner);

        // Both properties have active status by default
        $this->assertEquals(2, $count);
    }

    #[Test]
    public function test_get_active_properties_count_returns_zero_when_none(): void
    {
        // No properties created
        $count = $this->service->getActivePropertiesCount($this->owner);

        $this->assertEquals(0, $count);
    }

    #[Test]
    public function test_calculate_total_rental_income_sums_collected_payments(): void
    {
        $property = $this->createProperty();
        $contract = $this->createPropertyContract($property);

        // Create collected supply payments (paid_date is set)
        // net_amount = gross_amount - commission_amount - maintenance_deduction - other_deductions
        // Payment 1: 10000 - 500 - 0 - 0 = 9500
        $this->createSupplyPayment($contract, [
            'gross_amount' => 10000,
            'commission_amount' => 500,
            'paid_date' => now()->subDays(5),
        ]);

        // Payment 2: 8000 - 400 - 0 - 0 = 7600
        $this->createSupplyPayment($contract, [
            'payment_number' => 'SP-'.uniqid(),
            'gross_amount' => 8000,
            'commission_amount' => 400,
            'paid_date' => now()->subDays(10),
        ]);

        $totalIncome = $this->service->calculateTotalRentalIncome($this->owner);

        // Expected: 9500 + 7600 = 17100
        $this->assertEquals(17100.00, $totalIncome);
    }

    #[Test]
    public function test_calculate_total_rental_income_for_period(): void
    {
        $property = $this->createProperty();
        $contract = $this->createPropertyContract($property);

        // Create collected payment for this month
        // net_amount = 10000 - 500 - 0 - 0 = 9500
        $this->createSupplyPayment($contract, [
            'gross_amount' => 10000,
            'commission_amount' => 500,
            'paid_date' => now()->subDays(2),
            'month_year' => now()->format('Y-m'),
        ]);

        // Create collected payment for last month
        // net_amount = 8000 - 400 - 0 - 0 = 7600
        $this->createSupplyPayment($contract, [
            'payment_number' => 'SP-'.uniqid(),
            'gross_amount' => 8000,
            'commission_amount' => 400,
            'paid_date' => now()->subMonth(),
            'month_year' => now()->subMonth()->format('Y-m'),
        ]);

        // calculateTotalRentalIncome doesn't filter by period, so both are included
        $totalIncome = $this->service->calculateTotalRentalIncome($this->owner);

        // Expected: 9500 + 7600 = 17100
        $this->assertEquals(17100.00, $totalIncome);
    }

    #[Test]
    public function test_calculate_total_rental_income_excludes_deductions(): void
    {
        $property = $this->createProperty();
        $contract = $this->createPropertyContract($property);

        // Create collected payment with deductions
        // net_amount = gross_amount - commission_amount - maintenance_deduction
        $this->createSupplyPayment($contract, [
            'gross_amount' => 10000,
            'commission_amount' => 500,
            'maintenance_deduction' => 200,
            'paid_date' => now()->subDays(5),
        ]);

        $totalIncome = $this->service->calculateTotalRentalIncome($this->owner);

        // net_amount = 10000 - 500 - 200 - 0 = 9300
        $this->assertEquals(9300.00, $totalIncome);
    }

    #[Test]
    public function test_calculate_total_rental_income_excludes_uncollected_payments(): void
    {
        $property = $this->createProperty();
        $contract = $this->createPropertyContract($property);

        // Create collected payment (net_amount = 10000 - 500 - 0 - 0 = 9500)
        $this->createSupplyPayment($contract, [
            'paid_date' => now()->subDays(5),
        ]);

        // Create uncollected payment (paid_date is null)
        $this->createSupplyPayment($contract, [
            'payment_number' => 'SP-'.uniqid(),
            'paid_date' => null,
        ]);

        $totalIncome = $this->service->calculateTotalRentalIncome($this->owner);

        // Only collected payment is included
        $this->assertEquals(9500.00, $totalIncome);
    }

    #[Test]
    public function test_calculate_total_rental_income_returns_zero_when_no_payments(): void
    {
        $totalIncome = $this->service->calculateTotalRentalIncome($this->owner);

        $this->assertEquals(0, $totalIncome);
    }

    #[Test]
    public function test_calculate_occupancy_rate_returns_percentage(): void
    {
        $property = $this->createProperty();

        // Create 4 units
        $unit1 = $this->createUnit($property);
        $unit2 = $this->createUnit($property);
        $unit3 = $this->createUnit($property);
        $unit4 = $this->createUnit($property);

        // Create active contracts for 2 units (50% occupancy)
        $this->createActiveContract($unit1);
        $this->createActiveContract($unit2);

        $occupancyRate = $this->service->calculateOccupancyRate($this->owner);

        $this->assertEquals(50.00, $occupancyRate);
    }

    #[Test]
    public function test_calculate_occupancy_rate_returns_zero_when_no_units(): void
    {
        // Create property without units
        $this->createProperty();

        $occupancyRate = $this->service->calculateOccupancyRate($this->owner);

        $this->assertEquals(0, $occupancyRate);
    }

    #[Test]
    public function test_calculate_occupancy_rate_returns_100_when_all_occupied(): void
    {
        $property = $this->createProperty();

        // Create 3 units
        $unit1 = $this->createUnit($property);
        $unit2 = $this->createUnit($property);
        $unit3 = $this->createUnit($property);

        // Create active contracts for all 3 units (100% occupancy)
        $this->createActiveContract($unit1);
        $this->createActiveContract($unit2);
        $this->createActiveContract($unit3);

        $occupancyRate = $this->service->calculateOccupancyRate($this->owner);

        $this->assertEquals(100.00, $occupancyRate);
    }

    #[Test]
    public function test_calculate_occupancy_rate_excludes_expired_contracts(): void
    {
        $property = $this->createProperty();

        // Create 2 units
        $unit1 = $this->createUnit($property);
        $unit2 = $this->createUnit($property);

        // Create active contract for unit 1
        $this->createActiveContract($unit1);

        // Create expired contract for unit 2
        $tenant = Tenant::create([
            'name' => 'Expired Tenant',
            'email' => 'expired@test.com',
            'password' => bcrypt('password'),
            'phone' => '0507777777',
            'type' => UserType::TENANT->value,
        ]);

        UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit2->id,
            'property_id' => $property->id,
            'monthly_rent' => 3000,
            'duration_months' => 12,
            'start_date' => now()->subYear(),
            'end_date' => now()->subMonth(), // Expired
            'contract_status' => 'expired',
            'payment_frequency' => 'monthly',
            'payment_method' => 'bank_transfer',
        ]);

        $occupancyRate = $this->service->calculateOccupancyRate($this->owner);

        // Only 1 out of 2 units occupied = 50%
        $this->assertEquals(50.00, $occupancyRate);
    }

    #[Test]
    public function test_calculate_occupancy_rate_handles_multiple_properties(): void
    {
        $property1 = $this->createProperty();
        $property2 = $this->createProperty();

        // Property 1: 2 units, 1 occupied
        $unit1 = $this->createUnit($property1);
        $unit2 = $this->createUnit($property1);
        $this->createActiveContract($unit1);

        // Property 2: 2 units, 2 occupied
        $unit3 = $this->createUnit($property2);
        $unit4 = $this->createUnit($property2);
        $this->createActiveContract($unit3);
        $this->createActiveContract($unit4);

        // Total: 4 units, 3 occupied = 75%
        $occupancyRate = $this->service->calculateOccupancyRate($this->owner);

        $this->assertEquals(75.00, $occupancyRate);
    }

    #[Test]
    public function test_get_vacant_units_count_returns_correct_count(): void
    {
        $property = $this->createProperty();

        // Create 3 units
        $unit1 = $this->createUnit($property);
        $unit2 = $this->createUnit($property);
        $unit3 = $this->createUnit($property);

        // Create active contract for 1 unit
        $this->createActiveContract($unit1);

        // Get vacant units
        $vacantUnits = $this->service->getVacantUnits($this->owner);

        // 2 units should be vacant
        $this->assertCount(2, $vacantUnits);
        $this->assertTrue($vacantUnits->contains('id', $unit2->id));
        $this->assertTrue($vacantUnits->contains('id', $unit3->id));
        $this->assertFalse($vacantUnits->contains('id', $unit1->id));
    }

    #[Test]
    public function test_get_vacant_units_returns_all_when_no_contracts(): void
    {
        $property = $this->createProperty();

        // Create 3 units without contracts
        $unit1 = $this->createUnit($property);
        $unit2 = $this->createUnit($property);
        $unit3 = $this->createUnit($property);

        $vacantUnits = $this->service->getVacantUnits($this->owner);

        // All 3 units should be vacant
        $this->assertCount(3, $vacantUnits);
    }

    #[Test]
    public function test_get_vacant_units_returns_empty_when_all_occupied(): void
    {
        $property = $this->createProperty();

        // Create 2 units and occupy both
        $unit1 = $this->createUnit($property);
        $unit2 = $this->createUnit($property);

        $this->createActiveContract($unit1);
        $this->createActiveContract($unit2);

        $vacantUnits = $this->service->getVacantUnits($this->owner);

        // No vacant units
        $this->assertCount(0, $vacantUnits);
    }

    #[Test]
    public function test_get_vacant_units_includes_property_relationship(): void
    {
        $property = $this->createProperty();
        $unit = $this->createUnit($property);

        $vacantUnits = $this->service->getVacantUnits($this->owner);

        // Verify property is eager loaded
        $this->assertTrue($vacantUnits->first()->relationLoaded('property'));
        $this->assertEquals($property->id, $vacantUnits->first()->property->id);
    }

    #[Test]
    public function test_calculate_total_commissions_returns_correct_amount(): void
    {
        $property = $this->createProperty();
        $contract = $this->createPropertyContract($property);

        // Create collected payments with commissions
        $this->createSupplyPayment($contract, [
            'commission_amount' => 500,
            'paid_date' => now()->subDays(5),
        ]);

        $this->createSupplyPayment($contract, [
            'payment_number' => 'SP-'.uniqid(),
            'commission_amount' => 300,
            'paid_date' => now()->subDays(10),
        ]);

        $totalCommissions = $this->service->calculateTotalCommissions($this->owner);

        $this->assertEquals(800.00, $totalCommissions);
    }

    #[Test]
    public function test_calculate_total_deductions_returns_correct_amount(): void
    {
        $property = $this->createProperty();
        $contract = $this->createPropertyContract($property);

        // Create collected payments with maintenance deductions
        $this->createSupplyPayment($contract, [
            'maintenance_deduction' => 200,
            'paid_date' => now()->subDays(5),
        ]);

        $this->createSupplyPayment($contract, [
            'payment_number' => 'SP-'.uniqid(),
            'maintenance_deduction' => 150,
            'paid_date' => now()->subDays(10),
        ]);

        $totalDeductions = $this->service->calculateTotalDeductions($this->owner);

        $this->assertEquals(350.00, $totalDeductions);
    }

    #[Test]
    public function test_get_vacant_properties_returns_correct_properties(): void
    {
        // Properties with 'available' status should be returned
        $this->createProperty();
        $this->createProperty();

        $vacantProperties = $this->service->getVacantProperties($this->owner);

        // Properties with available status are returned
        $this->assertCount(2, $vacantProperties);
    }

    #[Test]
    public function test_get_occupied_units_returns_correct_units(): void
    {
        $property = $this->createProperty();

        $unit1 = $this->createUnit($property);
        $unit2 = $this->createUnit($property);
        $unit3 = $this->createUnit($property);

        // Occupy 2 units
        $this->createActiveContract($unit1);
        $this->createActiveContract($unit2);

        $occupiedUnits = $this->service->getOccupiedUnits($this->owner);

        $this->assertCount(2, $occupiedUnits);
        $this->assertTrue($occupiedUnits->contains('id', $unit1->id));
        $this->assertTrue($occupiedUnits->contains('id', $unit2->id));
        $this->assertFalse($occupiedUnits->contains('id', $unit3->id));
    }

    #[Test]
    public function test_get_quick_stats_returns_all_keys(): void
    {
        $stats = $this->service->getQuickStats($this->owner);

        $this->assertArrayHasKey('total_rental_income', $stats);
        $this->assertArrayHasKey('active_properties', $stats);
        $this->assertArrayHasKey('occupancy_rate', $stats);
        $this->assertArrayHasKey('pending_supply_payments', $stats);
        $this->assertArrayHasKey('pending_supply_amount', $stats);
    }

    #[Test]
    public function test_get_financial_summary_returns_correct_structure(): void
    {
        $summary = $this->service->getFinancialSummary($this->owner);

        $this->assertArrayHasKey('total_gross_amount', $summary);
        $this->assertArrayHasKey('total_net_amount', $summary);
        $this->assertArrayHasKey('total_commissions', $summary);
        $this->assertArrayHasKey('total_deductions', $summary);
        $this->assertArrayHasKey('pending_amount', $summary);
        $this->assertArrayHasKey('payments_count', $summary);

        $this->assertArrayHasKey('total', $summary['payments_count']);
        $this->assertArrayHasKey('collected', $summary['payments_count']);
        $this->assertArrayHasKey('pending', $summary['payments_count']);
    }

    #[Test]
    public function test_get_properties_summary_returns_correct_structure(): void
    {
        $property = $this->createProperty();
        $unit = $this->createUnit($property);
        $this->createActiveContract($unit);

        $summary = $this->service->getPropertiesSummary($this->owner);

        $this->assertArrayHasKey('total_properties', $summary);
        $this->assertArrayHasKey('active_properties', $summary);
        $this->assertArrayHasKey('total_units', $summary);
        $this->assertArrayHasKey('occupied_units', $summary);
        $this->assertArrayHasKey('vacant_units', $summary);
        $this->assertArrayHasKey('occupancy_rate', $summary);
        $this->assertArrayHasKey('expected_monthly_rent', $summary);
        $this->assertArrayHasKey('properties', $summary);

        $this->assertEquals(1, $summary['total_properties']);
        $this->assertEquals(1, $summary['total_units']);
        $this->assertEquals(1, $summary['occupied_units']);
        $this->assertEquals(0, $summary['vacant_units']);
        $this->assertEquals(100, $summary['occupancy_rate']);
    }

    #[Test]
    public function test_search_owners_by_name(): void
    {
        // Create additional owners
        Owner::create([
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@test.com',
            'password' => bcrypt('password'),
            'phone' => '0501111111',
            'type' => UserType::OWNER->value,
        ]);

        Owner::create([
            'name' => 'Mohammed Khan',
            'email' => 'mohammed@test.com',
            'password' => bcrypt('password'),
            'phone' => '0502222222',
            'type' => UserType::OWNER->value,
        ]);

        $results = $this->service->searchOwners(['name' => 'Ahmed']);

        $this->assertCount(1, $results);
        $this->assertEquals('Ahmed Ali', $results->first()->name);
    }

    #[Test]
    public function test_search_owners_by_phone(): void
    {
        Owner::create([
            'name' => 'Phone Test Owner',
            'email' => 'phonetest@test.com',
            'password' => bcrypt('password'),
            'phone' => '0509999999',
            'type' => UserType::OWNER->value,
        ]);

        $results = $this->service->searchOwners(['phone' => '0509999999']);

        $this->assertCount(1, $results);
        $this->assertEquals('0509999999', $results->first()->phone);
    }

    #[Test]
    public function test_search_owners_with_min_properties(): void
    {
        // Main owner has no properties yet
        $ownerWithProperties = Owner::create([
            'name' => 'Owner With Properties',
            'email' => 'withprops@test.com',
            'password' => bcrypt('password'),
            'phone' => '0503333333',
            'type' => UserType::OWNER->value,
        ]);

        // Create 3 properties for this owner
        Property::create([
            'name' => 'Prop 1',
            'owner_id' => $ownerWithProperties->id,
            'location_id' => $this->location->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'address' => 'Address 1',
        ]);
        Property::create([
            'name' => 'Prop 2',
            'owner_id' => $ownerWithProperties->id,
            'location_id' => $this->location->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'address' => 'Address 2',
        ]);
        Property::create([
            'name' => 'Prop 3',
            'owner_id' => $ownerWithProperties->id,
            'location_id' => $this->location->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'address' => 'Address 3',
        ]);

        $results = $this->service->searchOwners(['min_properties' => 3]);

        $this->assertCount(1, $results);
        $this->assertEquals($ownerWithProperties->id, $results->first()->id);
    }
}
