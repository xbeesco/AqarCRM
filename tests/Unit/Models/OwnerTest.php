<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\SupplyPayment;
use App\Models\Unit;
use App\Models\User;
use App\Services\OwnerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OwnerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required lookup tables data
        $this->seedLookupData();
    }

    protected function createOwner(array $attributes = []): Owner
    {
        return Owner::create(array_merge([
            'name' => 'Test Owner',
            'phone' => '0509876543',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ], $attributes));
    }

    protected function createPropertyWithUnits(Owner $owner, int $unitsCount = 2): Property
    {
        $property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Test Address',
        ]);

        for ($i = 1; $i <= $unitsCount; $i++) {
            Unit::create([
                'name' => "Unit {$i}",
                'property_id' => $property->id,
                'unit_type_id' => 1,
                'rent_price' => 2000 + ($i * 100),
                'floor_number' => $i,
            ]);
        }

        return $property;
    }

    #[Test]
    public function owner_global_scope_filters_by_type(): void
    {
        // Create users of different types
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        User::create([
            'name' => 'Tenant User',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::TENANT->value,
        ]);

        $owner = $this->createOwner([
            'email' => 'owner1@test.com',
        ]);

        // Query through Owner model should only return owners
        $owners = Owner::all();

        $this->assertCount(1, $owners);
        $this->assertEquals($owner->id, $owners->first()->id);
    }

    #[Test]
    public function only_owner_type_users_returned(): void
    {
        // Create multiple owners
        $owner1 = $this->createOwner(['email' => 'owner1@test.com', 'phone' => '0501111111']);
        $owner2 = $this->createOwner(['email' => 'owner2@test.com', 'phone' => '0502222222']);
        $owner3 = $this->createOwner(['email' => 'owner3@test.com', 'phone' => '0503333333']);

        // Create non-owner users
        User::create([
            'name' => 'Employee',
            'email' => 'employee@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::EMPLOYEE->value,
        ]);

        User::create([
            'name' => 'Tenant',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::TENANT->value,
        ]);

        $owners = Owner::all();

        $this->assertCount(3, $owners);
        foreach ($owners as $owner) {
            $this->assertEquals(UserType::OWNER->value, $owner->type);
        }
    }

    #[Test]
    public function global_scope_can_be_removed_with_without_global_scopes(): void
    {
        $owner = $this->createOwner();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        // With global scope - only owners
        $withScope = Owner::count();

        // Without global scope - all users in the table
        $withoutScope = Owner::withoutGlobalScopes()->count();

        $this->assertEquals(1, $withScope);
        $this->assertEquals(2, $withoutScope);
    }

    #[Test]
    public function properties_relationship(): void
    {
        $owner = $this->createOwner();

        // Create multiple properties
        $property1 = Property::create([
            'name' => 'Property 1',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Address 1',
        ]);

        $property2 = Property::create([
            'name' => 'Property 2',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Address 2',
        ]);

        $this->assertCount(2, $owner->properties);
        $this->assertInstanceOf(Property::class, $owner->properties->first());
        $this->assertTrue($owner->properties->contains($property1));
        $this->assertTrue($owner->properties->contains($property2));
    }

    #[Test]
    public function supply_payments_relationship(): void
    {
        $owner = $this->createOwner();
        $property = $this->createPropertyWithUnits($owner, 1);

        // Create a property contract
        $contract = PropertyContract::create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addYear()->subDay(),
            'contract_status' => 'active',
            'payment_frequency' => 'monthly',
        ]);

        // Get the fresh owner to ensure we have the latest data
        $owner = $owner->fresh();

        // First count the existing payments (from contract creation if any)
        $initialCount = $owner->supplyPayments()->count();

        // Create supply payments
        SupplyPayment::create([
            'payment_number' => 'SP-REL-001',
            'property_contract_id' => $contract->id,
            'owner_id' => $owner->id,
            'gross_amount' => 10000,
            'commission_amount' => 500,
            'commission_rate' => 5.00,
            'net_amount' => 9500,
            'due_date' => now()->addMonth(),
            'month_year' => now()->addMonth()->format('Y-m'),
        ]);

        SupplyPayment::create([
            'payment_number' => 'SP-REL-002',
            'property_contract_id' => $contract->id,
            'owner_id' => $owner->id,
            'gross_amount' => 10000,
            'commission_amount' => 500,
            'commission_rate' => 5.00,
            'net_amount' => 9500,
            'due_date' => now()->addMonths(2),
            'month_year' => now()->addMonths(2)->format('Y-m'),
        ]);

        // Refresh and verify the relationship works
        $owner = $owner->fresh();
        $this->assertGreaterThanOrEqual(2, $owner->supplyPayments->count());
        $this->assertInstanceOf(SupplyPayment::class, $owner->supplyPayments->first());
    }

    #[Test]
    public function units_through_properties_relationship(): void
    {
        $owner = $this->createOwner();

        // Create properties with units
        $property1 = $this->createPropertyWithUnits($owner, 3);
        $property2 = $this->createPropertyWithUnits($owner, 2);

        // Total units should be 5 (3 + 2)
        $this->assertCount(5, $owner->units);
        $this->assertInstanceOf(Unit::class, $owner->units->first());
    }

    #[Test]
    public function property_contracts_through_properties(): void
    {
        $owner = $this->createOwner();
        $property = $this->createPropertyWithUnits($owner, 1);

        // Create a property contract
        $contract = PropertyContract::create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addYear()->subDay(),
            'contract_status' => 'active',
            'payment_frequency' => 'monthly',
        ]);

        // Access contracts through property
        $propertyContracts = $owner->properties->flatMap->contracts;

        $this->assertCount(1, $propertyContracts);
        $this->assertEquals($contract->id, $propertyContracts->first()->id);
    }

    #[Test]
    public function payments_alias_returns_supply_payments(): void
    {
        $owner = $this->createOwner();
        $property = $this->createPropertyWithUnits($owner, 1);

        $contract = PropertyContract::create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addYear()->subDay(),
            'contract_status' => 'active',
            'payment_frequency' => 'monthly',
        ]);

        // Refresh owner
        $owner = $owner->fresh();

        // Count before adding
        $beforeCount = $owner->supplyPayments()->count();

        SupplyPayment::create([
            'payment_number' => 'SP-ALIAS-001',
            'property_contract_id' => $contract->id,
            'owner_id' => $owner->id,
            'gross_amount' => 5000,
            'commission_amount' => 250,
            'commission_rate' => 5.00,
            'net_amount' => 4750,
            'due_date' => now()->addMonth(),
            'month_year' => now()->addMonth()->format('Y-m'),
        ]);

        // Refresh owner
        $owner = $owner->fresh();

        // payments() is an alias for supplyPayments() - verify counts match
        $this->assertEquals($owner->supplyPayments()->count(), $owner->payments()->count());

        // Verify both return the same first item when sorted by id desc
        $supplyPayment = $owner->supplyPayments()->orderBy('id', 'desc')->first();
        $payment = $owner->payments()->orderBy('id', 'desc')->first();
        $this->assertEquals($supplyPayment->id, $payment->id);
    }

    #[Test]
    public function active_properties_count_accessor_delegates_to_service(): void
    {
        $owner = $this->createOwner();

        // Mock OwnerService
        $mockService = Mockery::mock(OwnerService::class);
        $mockService->shouldReceive('getActivePropertiesCount')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $owner->id))
            ->andReturn(5);

        $this->app->instance(OwnerService::class, $mockService);

        $result = $owner->active_properties_count;

        $this->assertEquals(5, $result);
    }

    #[Test]
    public function total_rental_income_accessor_delegates_to_service(): void
    {
        $owner = $this->createOwner();

        // Mock OwnerService
        $mockService = Mockery::mock(OwnerService::class);
        $mockService->shouldReceive('calculateTotalRentalIncome')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $owner->id))
            ->andReturn(50000.00);

        $this->app->instance(OwnerService::class, $mockService);

        $result = $owner->total_rental_income;

        $this->assertEquals(50000.00, $result);
    }

    #[Test]
    public function vacant_properties_accessor_delegates_to_service(): void
    {
        $owner = $this->createOwner();

        // Create some vacant properties for the mock to return
        // Use Eloquent Collection to match the expected return type
        $vacantProperties = new \Illuminate\Database\Eloquent\Collection([
            new Property(['id' => 1, 'name' => 'Vacant Property 1']),
            new Property(['id' => 2, 'name' => 'Vacant Property 2']),
        ]);

        // Mock OwnerService
        $mockService = Mockery::mock(OwnerService::class);
        $mockService->shouldReceive('getVacantProperties')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $owner->id))
            ->andReturn($vacantProperties);

        $this->app->instance(OwnerService::class, $mockService);

        $result = $owner->vacant_properties;

        $this->assertCount(2, $result);
    }

    #[Test]
    public function occupancy_rate_accessor_delegates_to_service(): void
    {
        $owner = $this->createOwner();

        // Mock OwnerService
        $mockService = Mockery::mock(OwnerService::class);
        $mockService->shouldReceive('calculateOccupancyRate')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $owner->id))
            ->andReturn(75.50);

        $this->app->instance(OwnerService::class, $mockService);

        $result = $owner->occupancy_rate;

        $this->assertEquals(75.50, $result);
    }

    #[Test]
    public function total_commissions_accessor_delegates_to_service(): void
    {
        $owner = $this->createOwner();

        // Mock OwnerService
        $mockService = Mockery::mock(OwnerService::class);
        $mockService->shouldReceive('calculateTotalCommissions')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $owner->id))
            ->andReturn(2500.00);

        $this->app->instance(OwnerService::class, $mockService);

        $result = $owner->total_commissions;

        $this->assertEquals(2500.00, $result);
    }

    #[Test]
    public function owner_type_is_auto_set_on_creation(): void
    {
        // Create owner without setting type explicitly
        $owner = Owner::create([
            'name' => 'Auto Type Owner',
            'phone' => '0505555555',
            'email' => 'autotype@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertEquals(UserType::OWNER->value, $owner->type);
    }

    #[Test]
    public function active_properties_count_returns_correct_count_with_real_service(): void
    {
        $owner = $this->createOwner();

        // Create active property status
        $activeStatus = PropertyStatus::firstOrCreate(
            ['slug' => 'available'],
            [
                'name' => 'Available',
                'slug' => 'available',
            ]
        );

        // Create inactive property status
        $inactiveStatus = PropertyStatus::firstOrCreate(
            ['slug' => 'inactive'],
            [
                'name' => 'Inactive',
                'slug' => 'inactive',
            ]
        );

        // Create properties with active status
        Property::create([
            'name' => 'Property 1',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => $activeStatus->id,
            'location_id' => 1,
            'address' => 'Address 1',
        ]);

        Property::create([
            'name' => 'Property 2',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => $activeStatus->id,
            'location_id' => 1,
            'address' => 'Address 2',
        ]);

        $owner = $owner->fresh();

        // Should return 2 for the two active properties
        $this->assertIsInt($owner->active_properties_count);
        $this->assertEquals(2, $owner->active_properties_count);
    }

    #[Test]
    public function occupancy_rate_returns_zero_when_no_units(): void
    {
        $owner = $this->createOwner();

        // Create property without units
        Property::create([
            'name' => 'Empty Property',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Address',
        ]);

        $owner = $owner->fresh();

        $this->assertEquals(0, $owner->occupancy_rate);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
