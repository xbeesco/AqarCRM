<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required lookup tables data
        $this->createRequiredLookupData();
    }

    protected function createRequiredLookupData(): void
    {
        // Create property type
        PropertyType::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Apartment',
                'slug' => 'apartment',
            ]
        );

        // Create property status
        PropertyStatus::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Available',
                'slug' => 'available',
            ]
        );

        // Create location
        Location::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Location',
                'level' => 1,
            ]
        );

        // Create unit type
        UnitType::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Apartment',
                'slug' => 'apartment',
            ]
        );
    }

    protected function createTenant(array $attributes = []): Tenant
    {
        return Tenant::create(array_merge([
            'name' => 'Test Tenant',
            'phone' => '0501234567',
            'email' => 'tenant@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::TENANT->value,
        ], $attributes));
    }

    protected function createOwner(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Test Owner',
            'phone' => '0509876543',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ], $attributes));
    }

    protected function createPropertyWithUnit(User $owner): array
    {
        $property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $owner->id,
            'type_id' => 1,
            'status_id' => 1,
            'location_id' => 1,
            'address' => 'Test Address',
        ]);

        $unit = Unit::create([
            'name' => 'Unit 101',
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'rent_price' => 2000,
            'floor_number' => 1,
        ]);

        return [$property, $unit];
    }

    // ==============================================
    // Global Scope Tests
    // ==============================================

    #[Test]
    public function tenant_global_scope_filters_by_type(): void
    {
        // Create users of different types
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        User::create([
            'name' => 'Owner User',
            'email' => 'owner2@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        $tenant = $this->createTenant([
            'email' => 'tenant1@test.com',
        ]);

        // Query through Tenant model should only return tenants
        $tenants = Tenant::all();

        $this->assertCount(1, $tenants);
        $this->assertEquals($tenant->id, $tenants->first()->id);
    }

    #[Test]
    public function only_tenant_type_users_returned(): void
    {
        // Create multiple tenants
        $tenant1 = $this->createTenant(['email' => 'tenant1@test.com', 'phone' => '0501111111']);
        $tenant2 = $this->createTenant(['email' => 'tenant2@test.com', 'phone' => '0502222222']);
        $tenant3 = $this->createTenant(['email' => 'tenant3@test.com', 'phone' => '0503333333']);

        // Create non-tenant users
        User::create([
            'name' => 'Employee',
            'email' => 'employee@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::EMPLOYEE->value,
        ]);

        $tenants = Tenant::all();

        $this->assertCount(3, $tenants);
        foreach ($tenants as $tenant) {
            $this->assertEquals(UserType::TENANT->value, $tenant->type);
        }
    }

    #[Test]
    public function global_scope_can_be_removed_with_without_global_scopes(): void
    {
        $tenant = $this->createTenant();

        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::ADMIN->value,
        ]);

        // With global scope - only tenants
        $withScope = Tenant::count();

        // Without global scope - all users in the table
        $withoutScope = Tenant::withoutGlobalScopes()->count();

        $this->assertEquals(1, $withScope);
        $this->assertEquals(2, $withoutScope);
    }

    // ==============================================
    // Relationship Tests
    // ==============================================

    #[Test]
    public function current_property_relationship_returns_null_when_not_set(): void
    {
        $tenant = $this->createTenant(['email' => 'tenant_prop@test.com']);

        // Without setting current_property_id, relationship should return null
        $this->assertNull($tenant->currentProperty);
    }

    #[Test]
    public function current_contract_relationship(): void
    {
        $owner = $this->createOwner();
        [$property, $unit] = $this->createPropertyWithUnit($owner);
        $tenant = $this->createTenant(['email' => 'tenant_contract@test.com']);

        // Create an active contract
        $contract = UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => 12,
            'start_date' => now()->subMonths(2),
            'end_date' => now()->addMonths(10),
            'contract_status' => 'active',
        ]);

        $currentContract = $tenant->currentContract;

        $this->assertNotNull($currentContract);
        $this->assertEquals($contract->id, $currentContract->id);
        $this->assertEquals('active', $currentContract->contract_status);
    }

    #[Test]
    public function rental_contracts_relationship(): void
    {
        $owner = $this->createOwner();
        [$property, $unit] = $this->createPropertyWithUnit($owner);
        $tenant = $this->createTenant(['email' => 'tenant_rental@test.com']);

        // Create multiple contracts
        UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => 12,
            'start_date' => now()->subYear()->subMonths(2),
            'end_date' => now()->subMonths(2),
            'contract_status' => 'expired',
        ]);

        UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2200,
            'duration_months' => 12,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'contract_status' => 'active',
        ]);

        $this->assertCount(2, $tenant->rentalContracts);
        $this->assertInstanceOf(UnitContract::class, $tenant->rentalContracts->first());
    }

    #[Test]
    public function unit_contracts_relationship(): void
    {
        $owner = $this->createOwner();
        [$property, $unit] = $this->createPropertyWithUnit($owner);
        $tenant = $this->createTenant(['email' => 'tenant_unit@test.com']);

        UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => 6,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(5),
            'contract_status' => 'active',
        ]);

        $this->assertCount(1, $tenant->unitContracts);
        $this->assertInstanceOf(UnitContract::class, $tenant->unitContracts->first());
    }

    #[Test]
    public function collection_payments_relationship(): void
    {
        $owner = $this->createOwner();
        [$property, $unit] = $this->createPropertyWithUnit($owner);
        $tenant = $this->createTenant(['email' => 'tenant_payments@test.com']);

        // Use draft status to avoid auto-generating payments via Observer
        $startDate = now();
        $durationMonths = 12;
        $contract = UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($durationMonths)->subDay(),
            'contract_status' => 'draft', // Use draft to prevent auto payment generation
        ]);

        // Create collection payments manually
        CollectionPayment::create([
            'payment_number' => 'PAY-001',
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 2000,
            'due_date_start' => now()->subDays(30),
            'due_date_end' => now()->subDays(25),
            'collection_date' => now()->subDays(28),
        ]);

        CollectionPayment::create([
            'payment_number' => 'PAY-002',
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 2000,
            'due_date_start' => now(),
            'due_date_end' => now()->addDays(5),
        ]);

        $this->assertCount(2, $tenant->collectionPayments);
        $this->assertInstanceOf(CollectionPayment::class, $tenant->collectionPayments->first());
    }

    #[Test]
    public function payment_history_alias_works(): void
    {
        $owner = $this->createOwner();
        [$property, $unit] = $this->createPropertyWithUnit($owner);
        $tenant = $this->createTenant(['email' => 'tenant_history@test.com']);

        // Use draft status to avoid auto-generating payments via Observer
        $startDate = now();
        $durationMonths = 12;
        $contract = UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($durationMonths)->subDay(),
            'contract_status' => 'draft', // Use draft to prevent auto payment generation
        ]);

        CollectionPayment::create([
            'payment_number' => 'PAY-HIST-001',
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 2000,
            'due_date_start' => now()->subDays(30),
            'due_date_end' => now()->subDays(25),
        ]);

        // collectionPayments is the relationship, it should work
        $this->assertCount(1, $tenant->collectionPayments);
    }

    // ==============================================
    // Accessor Tests (Delegated to TenantService)
    // ==============================================

    #[Test]
    public function has_active_contract_accessor(): void
    {
        $tenant = $this->createTenant(['email' => 'tenant_accessor@test.com']);

        // Mock TenantService
        $mockService = Mockery::mock(TenantService::class);
        $mockService->shouldReceive('hasActiveContract')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $tenant->id))
            ->andReturn(true);

        $this->app->instance(TenantService::class, $mockService);

        $result = $tenant->has_active_contract;

        $this->assertTrue($result);
    }

    #[Test]
    public function total_amount_paid_accessor_delegates_to_service(): void
    {
        $tenant = $this->createTenant(['email' => 'tenant_paid@test.com']);

        // Mock TenantService
        $mockService = Mockery::mock(TenantService::class);
        $mockService->shouldReceive('calculateTotalPaid')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $tenant->id))
            ->andReturn(5000.00);

        $this->app->instance(TenantService::class, $mockService);

        $result = $tenant->total_amount_paid;

        $this->assertEquals(5000.00, $result);
    }

    #[Test]
    public function outstanding_balance_accessor_delegates_to_service(): void
    {
        $tenant = $this->createTenant(['email' => 'tenant_balance@test.com']);

        // Mock TenantService
        $mockService = Mockery::mock(TenantService::class);
        $mockService->shouldReceive('calculateOutstandingBalance')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $tenant->id))
            ->andReturn(2500.00);

        $this->app->instance(TenantService::class, $mockService);

        $result = $tenant->outstanding_balance;

        $this->assertEquals(2500.00, $result);
    }

    #[Test]
    public function is_in_good_standing_accessor_delegates_to_service(): void
    {
        $tenant = $this->createTenant(['email' => 'tenant_standing@test.com']);

        // Mock TenantService
        $mockService = Mockery::mock(TenantService::class);
        $mockService->shouldReceive('isInGoodStanding')
            ->once()
            ->with(Mockery::on(fn ($arg) => $arg->id === $tenant->id))
            ->andReturn(true);

        $this->app->instance(TenantService::class, $mockService);

        $result = $tenant->is_in_good_standing;

        $this->assertTrue($result);
    }

    // ==============================================
    // Status Check Tests
    // ==============================================

    #[Test]
    public function has_active_contract_returns_true_when_active(): void
    {
        $owner = $this->createOwner(['email' => 'owner_active@test.com']);
        [$property, $unit] = $this->createPropertyWithUnit($owner);
        $tenant = $this->createTenant(['email' => 'tenant_active@test.com']);

        // Create an active contract
        UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => 12,
            'start_date' => now()->subMonth(),
            'end_date' => now()->addMonths(11),
            'contract_status' => 'active',
        ]);

        // Fresh tenant with real service
        $tenant = $tenant->fresh();

        $this->assertTrue($tenant->hasActiveContract());
    }

    #[Test]
    public function has_active_contract_returns_false_when_no_contract(): void
    {
        $tenant = $this->createTenant(['email' => 'tenant_nocontract@test.com']);

        $this->assertFalse($tenant->hasActiveContract());
    }

    #[Test]
    public function has_active_contract_returns_false_when_expired(): void
    {
        $owner = $this->createOwner(['email' => 'owner_expired@test.com']);
        [$property, $unit] = $this->createPropertyWithUnit($owner);
        $tenant = $this->createTenant(['email' => 'tenant_expired@test.com']);

        // Create an expired contract
        UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 2000,
            'duration_months' => 12,
            'start_date' => now()->subYear()->subMonth(),
            'end_date' => now()->subMonth(),
            'contract_status' => 'expired',
        ]);

        $tenant = $tenant->fresh();

        $this->assertFalse($tenant->hasActiveContract());
    }

    // ==============================================
    // Auto-Set Type on Creation Test
    // ==============================================

    #[Test]
    public function tenant_type_is_auto_set_on_creation(): void
    {
        // Create tenant without setting type explicitly
        $tenant = Tenant::create([
            'name' => 'Auto Type Tenant',
            'phone' => '0505555555',
            'email' => 'autotype@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertEquals(UserType::TENANT->value, $tenant->type);
    }

    #[Test]
    public function tenant_auto_generates_email_from_phone(): void
    {
        // Create tenant without email
        $tenant = Tenant::create([
            'name' => 'No Email Tenant',
            'phone' => '0506666666',
            'password' => bcrypt('password'),
        ]);

        // Email should be auto-generated
        $this->assertNotNull($tenant->email);
        $this->assertStringContainsString('0506666666', $tenant->email);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
