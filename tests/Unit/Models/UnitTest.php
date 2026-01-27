<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\UnitContract;
use App\Models\UnitFeature;
use App\Models\UnitType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

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

        $this->createRequiredLookupData();
    }

    protected function createRequiredLookupData(): void
    {
        // Create property type
        $this->propertyType = PropertyType::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'عمارة سكنية',
                'name_en' => 'Residential Building',
                'slug' => 'residential-building',
                'is_active' => true,
            ]
        );

        // Create property status
        $this->propertyStatus = PropertyStatus::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'slug' => 'available',
                'is_active' => true,
            ]
        );

        // Create location
        $this->location = Location::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Location',
                'name_ar' => 'موقع اختبار',
                'name_en' => 'Test Location',
                'level' => 1,
                'is_active' => true,
            ]
        );

        // Create unit type
        $this->unitType = UnitType::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'slug' => 'apartment',
                'is_active' => true,
            ]
        );

        // Create unit category
        $this->unitCategory = UnitCategory::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'سكني',
                'name_en' => 'Residential',
                'slug' => 'residential',
                'is_active' => true,
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

    // ==========================================
    // Basic Model Tests
    // ==========================================

    #[Test]
    public function unit_can_be_created(): void
    {
        $unit = $this->createUnit();

        $this->assertDatabaseHas('units', [
            'name' => 'Unit 101',
            'property_id' => $this->property->id,
        ]);

        $this->assertInstanceOf(Unit::class, $unit);
    }

    #[Test]
    public function unit_has_fillable_attributes(): void
    {
        $unit = $this->createUnit([
            'name' => 'My Unit',
            'floor_number' => 5,
            'area_sqm' => 150.50,
            'rooms_count' => 4,
            'bathrooms_count' => 3,
            'balconies_count' => 2,
            'has_laundry_room' => false,
            'electricity_account_number' => '1234567890',
            'water_expenses' => 100.50,
            'rent_price' => 5000,
            'notes' => 'Test notes',
        ]);

        $this->assertEquals('My Unit', $unit->name);
        $this->assertEquals(5, $unit->floor_number);
        $this->assertEquals(150.50, $unit->area_sqm);
        $this->assertEquals(4, $unit->rooms_count);
        $this->assertEquals(3, $unit->bathrooms_count);
        $this->assertEquals(2, $unit->balconies_count);
        $this->assertFalse($unit->has_laundry_room);
        $this->assertEquals('1234567890', $unit->electricity_account_number);
        $this->assertEquals(100.50, $unit->water_expenses);
        $this->assertEquals(5000, $unit->rent_price);
        $this->assertEquals('Test notes', $unit->notes);
    }

    #[Test]
    public function unit_casts_attributes_correctly(): void
    {
        $unit = $this->createUnit([
            'area_sqm' => '150.50',
            'rent_price' => '3500.00',
            'water_expenses' => '50.75',
            'floor_number' => '3',
            'rooms_count' => '4',
            'bathrooms_count' => '2',
            'balconies_count' => '1',
            'has_laundry_room' => 1,
        ]);

        $this->assertIsFloat(floatval($unit->area_sqm));
        $this->assertIsFloat(floatval($unit->rent_price));
        $this->assertIsFloat(floatval($unit->water_expenses));
        $this->assertIsInt($unit->floor_number);
        $this->assertIsInt($unit->rooms_count);
        $this->assertIsInt($unit->bathrooms_count);
        $this->assertIsInt($unit->balconies_count);
        $this->assertIsBool($unit->has_laundry_room);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    #[Test]
    public function unit_belongs_to_property(): void
    {
        $unit = $this->createUnit();

        $this->assertInstanceOf(Property::class, $unit->property);
        $this->assertEquals($this->property->id, $unit->property->id);
    }

    #[Test]
    public function unit_belongs_to_unit_type(): void
    {
        $unit = $this->createUnit();

        $this->assertInstanceOf(UnitType::class, $unit->unitType);
        $this->assertEquals($this->unitType->id, $unit->unitType->id);
    }

    #[Test]
    public function unit_belongs_to_unit_category(): void
    {
        $unit = $this->createUnit();

        $this->assertInstanceOf(UnitCategory::class, $unit->unitCategory);
        $this->assertEquals($this->unitCategory->id, $unit->unitCategory->id);
    }

    #[Test]
    public function unit_has_many_contracts(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant();

        // Create contracts
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        $unit->refresh();

        $this->assertCount(1, $unit->contracts);
        $this->assertInstanceOf(UnitContract::class, $unit->contracts->first());
    }

    #[Test]
    public function unit_has_one_active_contract(): void
    {
        $unit = $this->createUnit();
        $tenant1 = $this->createTenant(['email' => 'tenant1@test.com']);
        $tenant2 = $this->createTenant(['email' => 'tenant2@test.com']);

        // Create expired contract
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant1->id,
            'contract_status' => 'expired',
            'start_date' => Carbon::now()->subYears(2),
            'end_date' => Carbon::now()->subYear(),
            'duration_months' => 12,
        ]);

        // Create active contract
        $activeContract = UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant2->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        $unit->refresh();

        $this->assertNotNull($unit->activeContract);
        $this->assertEquals('active', $unit->activeContract->contract_status);
        $this->assertEquals($activeContract->id, $unit->activeContract->id);
    }

    #[Test]
    public function unit_belongs_to_many_features(): void
    {
        $unit = $this->createUnit();

        // Create features
        $feature1 = UnitFeature::firstOrCreate(
            ['slug' => 'air-conditioning'],
            [
                'name_ar' => 'تكييف',
                'name_en' => 'Air Conditioning',
                'slug' => 'air-conditioning',
            ]
        );

        $feature2 = UnitFeature::firstOrCreate(
            ['slug' => 'furnished'],
            [
                'name_ar' => 'مفروشة',
                'name_en' => 'Furnished',
                'slug' => 'furnished',
            ]
        );

        // Attach features to unit
        $unit->features()->attach([
            $feature1->id => ['value' => 'available'],
            $feature2->id => ['value' => 'fully'],
        ]);

        $unit->refresh();

        $this->assertCount(2, $unit->features);
        $this->assertInstanceOf(UnitFeature::class, $unit->features->first());
        $this->assertNotNull($unit->features->first()->pivot->value);
    }

    #[Test]
    public function unit_has_many_expenses(): void
    {
        $unit = $this->createUnit();

        // Create expenses
        Expense::create([
            'subject_type' => Unit::class,
            'subject_id' => $unit->id,
            'desc' => 'Unit Maintenance',
            'type' => 'maintenance',
            'cost' => 500,
            'date' => now(),
        ]);

        Expense::create([
            'subject_type' => Unit::class,
            'subject_id' => $unit->id,
            'desc' => 'Cleaning',
            'type' => 'other',
            'cost' => 200,
            'date' => now(),
        ]);

        $unit->refresh();

        $this->assertCount(2, $unit->expenses);
        $this->assertInstanceOf(Expense::class, $unit->expenses->first());
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    #[Test]
    public function unit_calculates_total_expenses(): void
    {
        $unit = $this->createUnit();

        Expense::create([
            'subject_type' => Unit::class,
            'subject_id' => $unit->id,
            'desc' => 'Expense 1',
            'type' => 'maintenance',
            'cost' => 1000,
            'date' => now(),
        ]);

        Expense::create([
            'subject_type' => Unit::class,
            'subject_id' => $unit->id,
            'desc' => 'Expense 2',
            'type' => 'maintenance',
            'cost' => 500,
            'date' => now(),
        ]);

        $this->assertEquals(1500, $unit->total_expenses);
    }

    #[Test]
    public function unit_calculates_current_month_expenses(): void
    {
        $unit = $this->createUnit();

        // Current month expense
        Expense::create([
            'subject_type' => Unit::class,
            'subject_id' => $unit->id,
            'desc' => 'Current Month Expense',
            'type' => 'maintenance',
            'cost' => 1000,
            'date' => now(),
        ]);

        // Last month expense
        Expense::create([
            'subject_type' => Unit::class,
            'subject_id' => $unit->id,
            'desc' => 'Last Month Expense',
            'type' => 'maintenance',
            'cost' => 500,
            'date' => now()->subMonth(),
        ]);

        $this->assertEquals(1000, $unit->current_month_expenses);
    }

    #[Test]
    public function unit_gets_current_tenant_from_active_contract(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant(['name' => 'Ahmed Tenant']);

        // Create active contract with tenant
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        $unit->refresh();

        $this->assertNotNull($unit->current_tenant);
        $this->assertEquals('Ahmed Tenant', $unit->current_tenant->name);
    }

    #[Test]
    public function unit_returns_null_current_tenant_when_no_active_contract(): void
    {
        $unit = $this->createUnit();

        $this->assertNull($unit->current_tenant);
    }

    // ==========================================
    // Business Logic Tests
    // ==========================================

    #[Test]
    public function unit_is_available_when_no_active_contract(): void
    {
        $unit = $this->createUnit();

        $this->assertTrue($unit->isAvailable());
        $this->assertFalse($unit->isOccupied());
    }

    #[Test]
    public function unit_is_occupied_when_has_active_contract(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant();

        // Create active contract
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        $unit->refresh();

        $this->assertFalse($unit->isAvailable());
        $this->assertTrue($unit->isOccupied());
    }

    #[Test]
    public function unit_is_available_when_contract_is_expired(): void
    {
        $unit = $this->createUnit();
        $tenant = $this->createTenant();

        // Create expired contract
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant->id,
            'contract_status' => 'expired',
            'start_date' => Carbon::now()->subYears(2),
            'end_date' => Carbon::now()->subYear(),
            'duration_months' => 12,
        ]);

        $unit->refresh();

        $this->assertTrue($unit->isAvailable());
        $this->assertFalse($unit->isOccupied());
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function unit_handles_null_category(): void
    {
        $unit = $this->createUnit(['unit_category_id' => null]);

        $this->assertNull($unit->unitCategory);
        $this->assertNull($unit->unit_category_id);
    }

    #[Test]
    public function unit_handles_zero_expenses(): void
    {
        $unit = $this->createUnit();

        $this->assertEquals(0, $unit->total_expenses);
        $this->assertEquals(0, $unit->current_month_expenses);
    }

    #[Test]
    public function unit_handles_zero_rooms(): void
    {
        $unit = $this->createUnit([
            'rooms_count' => 0,
            'bathrooms_count' => 0,
            'balconies_count' => 0,
        ]);

        $this->assertEquals(0, $unit->rooms_count);
        $this->assertEquals(0, $unit->bathrooms_count);
        $this->assertEquals(0, $unit->balconies_count);
    }

    #[Test]
    public function unit_can_be_updated(): void
    {
        $unit = $this->createUnit();

        $unit->update([
            'name' => 'Updated Unit Name',
            'rent_price' => 4500,
            'rooms_count' => 5,
        ]);

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Updated Unit Name',
            'rent_price' => 4500,
            'rooms_count' => 5,
        ]);
    }

    #[Test]
    public function unit_can_be_deleted(): void
    {
        $unit = $this->createUnit();
        $unitId = $unit->id;

        $unit->delete();

        $this->assertDatabaseMissing('units', ['id' => $unitId]);
    }

    #[Test]
    public function unit_uses_factory(): void
    {
        $unit = Unit::factory()->create([
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
        ]);

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
        $this->assertInstanceOf(Unit::class, $unit);
    }

    #[Test]
    public function unit_handles_nullable_fields(): void
    {
        $unit = Unit::create([
            'name' => 'Minimal Unit',
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
            'unit_category_id' => null,
            'rent_price' => 2000,
            'floor_number' => null,
            'area_sqm' => null,
            'rooms_count' => null,
            'bathrooms_count' => null,
            'balconies_count' => null,
            'has_laundry_room' => false,
            'electricity_account_number' => null,
            'water_expenses' => null,
            'floor_plan_file' => null,
            'notes' => null,
        ]);

        $this->assertDatabaseHas('units', ['id' => $unit->id]);
        $this->assertNull($unit->floor_number);
        $this->assertNull($unit->area_sqm);
        $this->assertNull($unit->rooms_count);
    }

    #[Test]
    public function unit_multiple_contracts_returns_only_active(): void
    {
        $unit = $this->createUnit();
        $tenant1 = $this->createTenant(['email' => 'tenant1@test.com']);
        $tenant2 = $this->createTenant(['email' => 'tenant2@test.com']);
        $tenant3 = $this->createTenant(['email' => 'tenant3@test.com']);

        // Create expired contract
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant1->id,
            'contract_status' => 'expired',
            'start_date' => Carbon::now()->subYears(3),
            'end_date' => Carbon::now()->subYears(2),
            'duration_months' => 12,
        ]);

        // Create terminated contract
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant2->id,
            'contract_status' => 'terminated',
            'start_date' => Carbon::now()->subYears(2),
            'end_date' => Carbon::now()->subYear(),
            'duration_months' => 12,
        ]);

        // Create active contract
        $activeContract = UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $tenant3->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        $unit->refresh();

        // Total contracts count
        $this->assertCount(3, $unit->contracts);

        // activeContract returns only the active one
        $this->assertNotNull($unit->activeContract);
        $this->assertEquals($activeContract->id, $unit->activeContract->id);
        $this->assertEquals('active', $unit->activeContract->contract_status);
    }
}
