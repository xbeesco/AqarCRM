<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use App\Services\UnitService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnitServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UnitService $service;

    protected Owner $owner;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected Location $location;

    protected UnitType $unitType;

    protected UnitCategory $unitCategory;

    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UnitService::class);
        $this->createRequiredLookupData();
    }

    protected function createRequiredLookupData(): void
    {
        // Create property type
        $this->propertyType = PropertyType::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Residential Building',
                'slug' => 'residential-building',
            ]
        );

        // Create property status
        $this->propertyStatus = PropertyStatus::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Available',
                'slug' => 'available',
            ]
        );

        // Create location
        $this->location = Location::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Location',
                'level' => 1,
            ]
        );

        // Create unit type
        $this->unitType = UnitType::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Apartment',
                'slug' => 'apartment',
            ]
        );

        // Create unit category
        $this->unitCategory = UnitCategory::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Residential',
                'slug' => 'residential',
            ]
        );

        // Create owner
        $this->owner = Owner::create([
            'name' => 'Test Owner',
            'phone' => '0509876543',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        // Create property
        $this->property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Test Address 123',
        ]);
    }

    protected function createUnit(array $attributes = []): Unit
    {
        return Unit::create(array_merge([
            'name' => 'Unit 101',
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
            'unit_category_id' => $this->unitCategory->id,
            'rent_price' => 3000,
            'floor_number' => 1,
            'area_sqm' => 100,
            'rooms_count' => 3,
            'bathrooms_count' => 2,
            'balconies_count' => 1,
            'has_laundry_room' => true,
        ], $attributes));
    }

    protected function createTenant(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'type' => UserType::TENANT->value,
        ], $attributes));
    }

    protected function createActiveContract(Unit $unit, User $tenant): UnitContract
    {
        return UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);
    }

    // ==========================================
    // checkUnitAvailability Tests
    // ==========================================

    #[Test]
    public function check_availability_returns_true_for_available_unit(): void
    {
        $unit = $this->createUnit();

        $isAvailable = $this->service->checkUnitAvailability($unit->id);

        $this->assertTrue($isAvailable);
    }

    #[Test]
    public function check_availability_returns_false_for_occupied_unit(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant();
        $this->createActiveContract($unit, $tenant);

        $isAvailable = $this->service->checkUnitAvailability($unit->id);

        $this->assertFalse($isAvailable);
    }

    #[Test]
    public function check_availability_with_date_range_returns_true_when_no_overlap(): void
    {
        $unit = $this->createUnit();

        $dateRange = [
            'start' => Carbon::now()->addMonths(2)->toDateString(),
        ];

        $isAvailable = $this->service->checkUnitAvailability($unit->id, $dateRange);

        $this->assertTrue($isAvailable);
    }

    // ==========================================
    // assignTenant Tests
    // ==========================================

    #[Test]
    public function assign_tenant_creates_contract_for_available_unit(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant();

        $result = $this->service->assignTenant($unit->id, $tenant->id, [
            'start_date' => Carbon::now(),
            'duration_months' => 12,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('Tenant assigned successfully', $result['message']);
        $this->assertNotNull($result['contract']);
        $this->assertEquals($tenant->id, $result['contract']->tenant_id);
        $this->assertEquals($unit->id, $result['contract']->unit_id);
    }

    #[Test]
    public function assign_tenant_throws_exception_for_occupied_unit(): void
    {
        $unit = $this->createUnit();
        $existingTenant = $this->createTenant(['email' => 'existing@test.com']);
        $this->createActiveContract($unit, $existingTenant);

        $newTenant = $this->createTenant(['email' => 'new@test.com']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unit is not available for assignment');

        $this->service->assignTenant($unit->id, $newTenant->id);
    }

    #[Test]
    public function assign_tenant_uses_unit_rent_price_as_default(): void
    {
        $unit = $this->createUnit(['rent_price' => 5000]);
        $tenant = $this->createTenant();

        $result = $this->service->assignTenant($unit->id, $tenant->id);

        $this->assertEquals(5000, $result['contract']->monthly_rent);
    }

    // ==========================================
    // releaseUnit Tests
    // ==========================================

    #[Test]
    public function release_unit_terminates_active_contract(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant();
        $contract = $this->createActiveContract($unit, $tenant);

        $result = $this->service->releaseUnit($unit->id, 'Tenant requested early termination');

        $this->assertTrue($result['success']);
        $this->assertEquals('Unit released successfully', $result['message']);

        $contract->refresh();
        $this->assertEquals('terminated', $contract->contract_status);
        $this->assertNotNull($contract->updated_at);
    }

    #[Test]
    public function release_unit_throws_exception_when_not_occupied(): void
    {
        $unit = $this->createUnit();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unit is not currently occupied');

        $this->service->releaseUnit($unit->id);
    }

    #[Test]
    public function release_unit_returns_previous_tenant(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant(['name' => 'Previous Tenant']);
        $this->createActiveContract($unit, $tenant);

        $result = $this->service->releaseUnit($unit->id);

        $this->assertEquals('Previous Tenant', $result['previous_tenant']->name);
    }

    // ==========================================
    // calculateUnitPricing Tests
    // ==========================================

    #[Test]
    public function calculate_pricing_returns_all_period_prices(): void
    {
        $unit = $this->createUnit(['rent_price' => 3000]);

        $pricing = $this->service->calculateUnitPricing($unit->id);

        $this->assertArrayHasKey('monthly', $pricing['pricing']);
        $this->assertArrayHasKey('quarterly', $pricing['pricing']);
        $this->assertArrayHasKey('semi_annual', $pricing['pricing']);
        $this->assertArrayHasKey('annual', $pricing['pricing']);
    }

    #[Test]
    public function calculate_pricing_applies_correct_discounts(): void
    {
        $unit = $this->createUnit(['rent_price' => 1000]);

        $pricing = $this->service->calculateUnitPricing($unit->id);

        // Monthly has 0% discount
        $this->assertEquals(0, $pricing['pricing']['monthly']['discount_percentage']);

        // Quarterly has 5% discount
        $this->assertEquals(5, $pricing['pricing']['quarterly']['discount_percentage']);

        // Semi-annual has 8% discount
        $this->assertEquals(8, $pricing['pricing']['semi_annual']['discount_percentage']);

        // Annual has 12% discount
        $this->assertEquals(12, $pricing['pricing']['annual']['discount_percentage']);
    }

    #[Test]
    public function calculate_pricing_returns_correct_base_prices(): void
    {
        $unit = $this->createUnit(['rent_price' => 1000]);

        $pricing = $this->service->calculateUnitPricing($unit->id);

        // Monthly base = 1000
        $this->assertEquals(1000, $pricing['pricing']['monthly']['base_price']);

        // Quarterly base = 3000
        $this->assertEquals(3000, $pricing['pricing']['quarterly']['base_price']);

        // Semi-annual base = 6000
        $this->assertEquals(6000, $pricing['pricing']['semi_annual']['base_price']);

        // Annual base = 12000
        $this->assertEquals(12000, $pricing['pricing']['annual']['base_price']);
    }

    #[Test]
    public function calculate_pricing_includes_recommended_period(): void
    {
        $unit = $this->createUnit(['rent_price' => 3000]);

        $pricing = $this->service->calculateUnitPricing($unit->id);

        $this->assertArrayHasKey('recommended_period', $pricing);
        // Annual should be recommended due to highest discount
        $this->assertEquals('annual', $pricing['recommended_period']);
    }

    // ==========================================
    // searchUnits Tests
    // ==========================================

    #[Test]
    public function search_units_by_property_id(): void
    {
        $unit1 = $this->createUnit(['name' => 'Unit 1']);

        // Create another property and unit
        $property2 = Property::create([
            'name' => 'Property 2',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Address 2',
        ]);

        $unit2 = Unit::create([
            'name' => 'Unit 2',
            'property_id' => $property2->id,
            'unit_type_id' => $this->unitType->id,
            'rent_price' => 2500,
        ]);

        $results = $this->service->searchUnits(['property_id' => $this->property->id]);

        $this->assertCount(1, $results);
        $this->assertEquals($unit1->id, $results->first()->id);
    }

    #[Test]
    public function search_units_by_availability(): void
    {
        $availableUnit = $this->createUnit(['name' => 'Available Unit']);
        $occupiedUnit = $this->createUnit(['name' => 'Occupied Unit']);

        $tenant = $this->createTenant();
        $this->createActiveContract($occupiedUnit, $tenant);

        // Search available units
        $availableResults = $this->service->searchUnits(['availability' => 'available']);
        $this->assertCount(1, $availableResults);
        $this->assertEquals($availableUnit->id, $availableResults->first()->id);

        // Search occupied units
        $occupiedResults = $this->service->searchUnits(['availability' => 'occupied']);
        $this->assertCount(1, $occupiedResults);
        $this->assertEquals($occupiedUnit->id, $occupiedResults->first()->id);
    }

    #[Test]
    public function search_units_by_price_range(): void
    {
        $cheapUnit = $this->createUnit(['name' => 'Cheap', 'rent_price' => 1500]);
        $midUnit = $this->createUnit(['name' => 'Mid', 'rent_price' => 3000]);
        $expensiveUnit = $this->createUnit(['name' => 'Expensive', 'rent_price' => 5000]);

        $results = $this->service->searchUnits([
            'min_price' => 2000,
            'max_price' => 4000,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($midUnit->id, $results->first()->id);
    }

    #[Test]
    public function search_units_by_area_range(): void
    {
        $smallUnit = $this->createUnit(['name' => 'Small', 'area_sqm' => 50]);
        $mediumUnit = $this->createUnit(['name' => 'Medium', 'area_sqm' => 100]);
        $largeUnit = $this->createUnit(['name' => 'Large', 'area_sqm' => 200]);

        $results = $this->service->searchUnits([
            'min_area' => 80,
            'max_area' => 150,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($mediumUnit->id, $results->first()->id);
    }

    #[Test]
    public function search_units_by_rooms_count(): void
    {
        $this->createUnit(['name' => 'Studio', 'rooms_count' => 1]);
        $twoRooms = $this->createUnit(['name' => 'Two Rooms', 'rooms_count' => 2]);
        $this->createUnit(['name' => 'Three Rooms', 'rooms_count' => 3]);

        $results = $this->service->searchUnits(['rooms_count' => 2]);

        $this->assertCount(1, $results);
        $this->assertEquals($twoRooms->id, $results->first()->id);
    }

    #[Test]
    public function search_units_by_floor_range(): void
    {
        $groundFloor = $this->createUnit(['name' => 'Ground', 'floor_number' => 0]);
        $thirdFloor = $this->createUnit(['name' => 'Third', 'floor_number' => 3]);
        $fifthFloor = $this->createUnit(['name' => 'Fifth', 'floor_number' => 5]);

        $results = $this->service->searchUnits([
            'min_floor' => 2,
            'max_floor' => 4,
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals($thirdFloor->id, $results->first()->id);
    }

    #[Test]
    public function search_units_with_no_filters_returns_all(): void
    {
        $this->createUnit(['name' => 'Unit 1']);
        $this->createUnit(['name' => 'Unit 2']);
        $this->createUnit(['name' => 'Unit 3']);

        $results = $this->service->searchUnits([]);

        $this->assertCount(3, $results);
    }

    // ==========================================
    // getUnitRecommendations Tests
    // ==========================================

    #[Test]
    public function get_recommendations_returns_only_available_units(): void
    {
        $availableUnit = $this->createUnit(['name' => 'Available']);
        $occupiedUnit = $this->createUnit(['name' => 'Occupied']);

        $tenant = $this->createTenant();
        $this->createActiveContract($occupiedUnit, $tenant);

        $results = $this->service->getUnitRecommendations([]);

        $this->assertCount(1, $results);
        $this->assertEquals($availableUnit->id, $results->first()->id);
    }

    #[Test]
    public function get_recommendations_filters_by_budget(): void
    {
        $affordableUnit = $this->createUnit(['name' => 'Affordable', 'rent_price' => 2000]);
        $expensiveUnit = $this->createUnit(['name' => 'Expensive', 'rent_price' => 5000]);

        $results = $this->service->getUnitRecommendations(['max_budget' => 3000]);

        $this->assertCount(1, $results);
        $this->assertEquals($affordableUnit->id, $results->first()->id);
    }

    #[Test]
    public function get_recommendations_filters_by_min_rooms(): void
    {
        $studioUnit = $this->createUnit(['name' => 'Studio', 'rooms_count' => 1]);
        $familyUnit = $this->createUnit(['name' => 'Family', 'rooms_count' => 3]);

        $results = $this->service->getUnitRecommendations(['min_rooms' => 2]);

        $this->assertCount(1, $results);
        $this->assertEquals($familyUnit->id, $results->first()->id);
    }

    #[Test]
    public function get_recommendations_calculates_relevance_score(): void
    {
        $unit = $this->createUnit([
            'rent_price' => 2000,
            'rooms_count' => 3,
            'balconies_count' => 2,
        ]);

        $results = $this->service->getUnitRecommendations([
            'max_budget' => 3000,
            'min_rooms' => 2,
        ]);

        $this->assertNotNull($results->first()->relevance_score);
        $this->assertGreaterThan(0, $results->first()->relevance_score);
    }

    // ==========================================
    // getUnitsPerformanceMetrics Tests
    // ==========================================

    #[Test]
    public function performance_metrics_calculates_total_units(): void
    {
        $this->createUnit(['name' => 'Unit 1']);
        $this->createUnit(['name' => 'Unit 2']);
        $this->createUnit(['name' => 'Unit 3']);

        $metrics = $this->service->getUnitsPerformanceMetrics();

        $this->assertEquals(3, $metrics['total_units']);
    }

    #[Test]
    public function performance_metrics_calculates_occupancy(): void
    {
        $unit1 = $this->createUnit(['name' => 'Occupied']);
        $this->createUnit(['name' => 'Available']);

        $tenant = $this->createTenant();
        $this->createActiveContract($unit1, $tenant);

        $metrics = $this->service->getUnitsPerformanceMetrics();

        $this->assertEquals(2, $metrics['total_units']);
        $this->assertEquals(1, $metrics['occupied_units']);
        $this->assertEquals(1, $metrics['available_units']);
        $this->assertEquals(50, $metrics['occupancy_rate']);
    }

    #[Test]
    public function performance_metrics_calculates_average_rent(): void
    {
        $this->createUnit(['rent_price' => 2000]);
        $this->createUnit(['rent_price' => 3000]);
        $this->createUnit(['rent_price' => 4000]);

        $metrics = $this->service->getUnitsPerformanceMetrics();

        $this->assertEquals(3000, $metrics['average_rent']);
    }

    #[Test]
    public function performance_metrics_filters_by_unit_ids(): void
    {
        $unit1 = $this->createUnit(['name' => 'Unit 1', 'rent_price' => 2000]);
        $unit2 = $this->createUnit(['name' => 'Unit 2', 'rent_price' => 3000]);
        $unit3 = $this->createUnit(['name' => 'Unit 3', 'rent_price' => 4000]);

        $metrics = $this->service->getUnitsPerformanceMetrics([$unit1->id, $unit2->id]);

        $this->assertEquals(2, $metrics['total_units']);
        $this->assertEquals(2500, $metrics['average_rent']);
    }

    #[Test]
    public function performance_metrics_calculates_revenue_from_occupied_units(): void
    {
        $occupiedUnit = $this->createUnit(['name' => 'Occupied', 'rent_price' => 3000]);
        $this->createUnit(['name' => 'Available', 'rent_price' => 4000]);

        $tenant = $this->createTenant();
        $this->createActiveContract($occupiedUnit, $tenant);

        $metrics = $this->service->getUnitsPerformanceMetrics();

        // Only occupied unit's rent counts toward revenue
        $this->assertEquals(3000, $metrics['total_monthly_revenue']);
    }

    #[Test]
    public function performance_metrics_includes_unit_type_distribution(): void
    {
        $this->createUnit(['name' => 'Unit 1']);
        $this->createUnit(['name' => 'Unit 2']);

        $metrics = $this->service->getUnitsPerformanceMetrics();

        $this->assertArrayHasKey('unit_type_distribution', $metrics);
        $this->assertIsArray($metrics['unit_type_distribution']);
    }

    #[Test]
    public function performance_metrics_includes_floor_distribution(): void
    {
        $this->createUnit(['name' => 'Ground', 'floor_number' => 0]);
        $this->createUnit(['name' => 'First', 'floor_number' => 1]);
        $this->createUnit(['name' => 'Second', 'floor_number' => 2]);

        $metrics = $this->service->getUnitsPerformanceMetrics();

        $this->assertArrayHasKey('floor_distribution', $metrics);
        $this->assertIsArray($metrics['floor_distribution']);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function throws_exception_for_non_existent_unit(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->checkUnitAvailability(99999);
    }

    #[Test]
    public function handles_empty_unit_list_for_metrics(): void
    {
        $metrics = $this->service->getUnitsPerformanceMetrics();

        $this->assertEquals(0, $metrics['total_units']);
        $this->assertEquals(0, $metrics['occupancy_rate']);
        $this->assertEquals(0, $metrics['total_monthly_revenue']);
    }

    #[Test]
    public function search_returns_empty_collection_when_no_match(): void
    {
        $this->createUnit(['rent_price' => 5000]);

        $results = $this->service->searchUnits(['max_price' => 1000]);

        $this->assertCount(0, $results);
    }

    #[Test]
    public function recommendations_returns_empty_when_all_occupied(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant();
        $this->createActiveContract($unit, $tenant);

        $results = $this->service->getUnitRecommendations([]);

        $this->assertCount(0, $results);
    }
}
