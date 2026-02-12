<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyFeature;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertyTest extends TestCase
{
    use RefreshDatabase;

    protected Owner $owner;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRequiredLookupData();
    }

    protected function createRequiredLookupData(): void
    {
        // Use updateOrInsert to force specific IDs (works with MySQL)
        PropertyType::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Residential Building', 'slug' => 'residential-building', 'created_at' => now(), 'updated_at' => now()]
        );
        $this->propertyType = PropertyType::find(1);

        PropertyStatus::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Available', 'slug' => 'available', 'created_at' => now(), 'updated_at' => now()]
        );
        $this->propertyStatus = PropertyStatus::find(1);

        Location::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Test Location', 'level' => 1, 'created_at' => now(), 'updated_at' => now()]
        );
        $this->location = Location::find(1);

        UnitType::query()->updateOrInsert(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment', 'created_at' => now(), 'updated_at' => now()]
        );

        // Create owner
        $this->owner = Owner::create([
            'name' => 'Test Owner',
            'phone' => '0509876543',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);
    }

    protected function createProperty(array $attributes = []): Property
    {
        return Property::create(array_merge([
            'name' => 'Test Property',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Test Address 123',
            'postal_code' => '12345',
            'parking_spots' => 10,
            'elevators' => 2,
            'floors_count' => 5,
            'build_year' => 2020,
        ], $attributes));
    }

    // ==========================================
    // Basic Model Tests
    // ==========================================

    #[Test]
    public function property_can_be_created(): void
    {
        $property = $this->createProperty();

        $this->assertDatabaseHas('properties', [
            'name' => 'Test Property',
            'owner_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(Property::class, $property);
    }

    #[Test]
    public function property_has_fillable_attributes(): void
    {
        $property = $this->createProperty([
            'name' => 'My Building',
            'address' => 'Main Street 456',
            'postal_code' => '54321',
            'parking_spots' => 15,
            'elevators' => 3,
            'floors_count' => 8,
            'build_year' => 2015,
            'notes' => 'Test notes',
        ]);

        $this->assertEquals('My Building', $property->name);
        $this->assertEquals('Main Street 456', $property->address);
        $this->assertEquals('54321', $property->postal_code);
        $this->assertEquals(15, $property->parking_spots);
        $this->assertEquals(3, $property->elevators);
        $this->assertEquals(8, $property->floors_count);
        $this->assertEquals(2015, $property->build_year);
        $this->assertEquals('Test notes', $property->notes);
    }

    #[Test]
    public function property_casts_attributes_correctly(): void
    {
        $property = $this->createProperty([
            'build_year' => '2018',
            'parking_spots' => '20',
            'elevators' => '4',
            'floors_count' => '10',
        ]);

        $this->assertIsInt($property->build_year);
        $this->assertIsInt($property->parking_spots);
        $this->assertIsInt($property->elevators);
        $this->assertIsInt($property->floors_count);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    #[Test]
    public function property_belongs_to_owner(): void
    {
        $property = $this->createProperty();

        $this->assertInstanceOf(Owner::class, $property->owner);
        $this->assertEquals($this->owner->id, $property->owner->id);
    }

    #[Test]
    public function property_belongs_to_location(): void
    {
        $property = $this->createProperty();

        $this->assertInstanceOf(Location::class, $property->location);
        $this->assertEquals($this->location->id, $property->location->id);
    }

    #[Test]
    public function property_belongs_to_property_type(): void
    {
        $property = $this->createProperty();

        $this->assertInstanceOf(PropertyType::class, $property->propertyType);
        $this->assertEquals($this->propertyType->id, $property->propertyType->id);
    }

    #[Test]
    public function property_belongs_to_property_status(): void
    {
        $property = $this->createProperty();

        $this->assertInstanceOf(PropertyStatus::class, $property->propertyStatus);
        $this->assertEquals($this->propertyStatus->id, $property->propertyStatus->id);
    }

    #[Test]
    public function property_has_many_units(): void
    {
        $property = $this->createProperty();

        // Create units
        Unit::create([
            'name' => 'Unit 101',
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'rent_price' => 3000,
            'floor_number' => 1,
        ]);

        Unit::create([
            'name' => 'Unit 102',
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'rent_price' => 3500,
            'floor_number' => 1,
        ]);

        $property->refresh();

        $this->assertCount(2, $property->units);
        $this->assertInstanceOf(Unit::class, $property->units->first());
    }

    #[Test]
    public function property_has_many_contracts(): void
    {
        $property = $this->createProperty();

        // Create property contract
        PropertyContract::create([
            'owner_id' => $this->owner->id,
            'property_id' => $property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addYear()->subDay(),
            'contract_status' => 'active',
            'payment_frequency' => 'monthly',
        ]);

        $property->refresh();

        $this->assertCount(1, $property->contracts);
        $this->assertInstanceOf(PropertyContract::class, $property->contracts->first());
    }

    #[Test]
    public function property_belongs_to_many_features(): void
    {
        $property = $this->createProperty();

        // Create features
        $feature1 = PropertyFeature::firstOrCreate(
            ['slug' => 'swimming-pool'],
            [
                'name' => 'Swimming Pool',
                'slug' => 'swimming-pool',
            ]
        );

        $feature2 = PropertyFeature::firstOrCreate(
            ['slug' => 'gym'],
            [
                'name' => 'Gym',
                'slug' => 'gym',
            ]
        );

        // Attach features to property
        $property->features()->attach([$feature1->id, $feature2->id]);

        $property->refresh();

        $this->assertCount(2, $property->features);
        $this->assertInstanceOf(PropertyFeature::class, $property->features->first());
        $this->assertContains($feature1->id, $property->features->pluck('id')->toArray());
    }

    #[Test]
    public function property_has_many_expenses(): void
    {
        $property = $this->createProperty();

        // Create expenses
        Expense::create([
            'subject_type' => Property::class,
            'subject_id' => $property->id,
            'desc' => 'Maintenance',
            'type' => 'maintenance',
            'cost' => 500,
            'date' => now(),
        ]);

        Expense::create([
            'subject_type' => Property::class,
            'subject_id' => $property->id,
            'desc' => 'Cleaning',
            'type' => 'other',
            'cost' => 200,
            'date' => now(),
        ]);

        $property->refresh();

        $this->assertCount(2, $property->expenses);
        $this->assertInstanceOf(Expense::class, $property->expenses->first());
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    #[Test]
    public function property_calculates_total_expenses(): void
    {
        $property = $this->createProperty();

        Expense::create([
            'subject_type' => Property::class,
            'subject_id' => $property->id,
            'desc' => 'Expense 1',
            'type' => 'maintenance',
            'cost' => 1000,
            'date' => now(),
        ]);

        Expense::create([
            'subject_type' => Property::class,
            'subject_id' => $property->id,
            'desc' => 'Expense 2',
            'type' => 'maintenance',
            'cost' => 500,
            'date' => now(),
        ]);

        $this->assertEquals(1500, $property->total_expenses);
    }

    #[Test]
    public function property_calculates_current_month_expenses(): void
    {
        $property = $this->createProperty();

        // Current month expense
        Expense::create([
            'subject_type' => Property::class,
            'subject_id' => $property->id,
            'desc' => 'Current Month Expense',
            'type' => 'maintenance',
            'cost' => 1000,
            'date' => now(),
        ]);

        // Last month expense
        Expense::create([
            'subject_type' => Property::class,
            'subject_id' => $property->id,
            'desc' => 'Last Month Expense',
            'type' => 'maintenance',
            'cost' => 500,
            'date' => now()->subMonth(),
        ]);

        $this->assertEquals(1000, $property->current_month_expenses);
    }

    #[Test]
    public function property_calculates_total_units(): void
    {
        $property = $this->createProperty();

        // Create units
        for ($i = 1; $i <= 5; $i++) {
            Unit::create([
                'name' => "Unit {$i}",
                'property_id' => $property->id,
                'unit_type_id' => 1,
                'rent_price' => 3000,
                'floor_number' => $i,
            ]);
        }

        $this->assertEquals(5, $property->total_units);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function property_handles_null_location(): void
    {
        $property = $this->createProperty(['location_id' => null]);

        $this->assertNull($property->location);
        $this->assertNull($property->location_id);
    }

    #[Test]
    public function property_handles_zero_units(): void
    {
        $property = $this->createProperty();

        $this->assertEquals(0, $property->total_units);
        $this->assertCount(0, $property->units);
    }

    #[Test]
    public function property_handles_zero_expenses(): void
    {
        $property = $this->createProperty();

        $this->assertEquals(0, $property->total_expenses);
        $this->assertEquals(0, $property->current_month_expenses);
    }

    #[Test]
    public function property_can_be_updated(): void
    {
        $property = $this->createProperty();

        $property->update([
            'name' => 'Updated Property Name',
            'floors_count' => 10,
        ]);

        $this->assertDatabaseHas('properties', [
            'id' => $property->id,
            'name' => 'Updated Property Name',
            'floors_count' => 10,
        ]);
    }

    #[Test]
    public function property_can_be_deleted(): void
    {
        $property = $this->createProperty();
        $propertyId = $property->id;

        $property->delete();

        $this->assertDatabaseMissing('properties', ['id' => $propertyId]);
    }

    #[Test]
    public function property_uses_factory(): void
    {
        $property = Property::factory()->create([
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
        ]);

        $this->assertDatabaseHas('properties', ['id' => $property->id]);
        $this->assertInstanceOf(Property::class, $property);
    }
}
