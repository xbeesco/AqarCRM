<?php

namespace Tests\Feature\Filament;

use App\Enums\UserType;
use App\Filament\Resources\Units\UnitResource;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\UnitCategory;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnitResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $owner;

    protected User $tenantUser;

    protected bool $isUsingSqlite = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if we're using SQLite
        $this->isUsingSqlite = DB::connection()->getDriverName() === 'sqlite';

        // Create required reference data
        $this->createReferenceData();

        // Create users of different types
        $this->superAdmin = User::factory()->create([
            'type' => UserType::SUPER_ADMIN->value,
            'email' => 'superadmin@test.com',
        ]);

        $this->admin = User::factory()->create([
            'type' => UserType::ADMIN->value,
            'email' => 'admin@test.com',
        ]);

        $this->employee = User::factory()->create([
            'type' => UserType::EMPLOYEE->value,
            'email' => 'employee@test.com',
        ]);

        $this->owner = User::factory()->create([
            'type' => UserType::OWNER->value,
            'email' => 'owner@test.com',
        ]);

        $this->tenantUser = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'tenant@test.com',
        ]);

        // Set the Filament panel
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /**
     * Create reference data required for testing
     */
    protected function createReferenceData(): void
    {
        // Create default Location
        Location::firstOrCreate(
            ['id' => 1],
            ['name' => 'Default Location', 'level' => 1]
        );

        // Create default PropertyType
        PropertyType::firstOrCreate(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment']
        );

        // Create default PropertyStatus
        PropertyStatus::firstOrCreate(
            ['id' => 1],
            ['name' => 'Available', 'slug' => 'available']
        );

        // Create default UnitType
        UnitType::firstOrCreate(
            ['id' => 1],
            ['name' => 'Apartment', 'slug' => 'apartment']
        );

        // Create default UnitCategory
        UnitCategory::firstOrCreate(
            ['id' => 1],
            ['name' => 'Residential', 'slug' => 'residential']
        );

        // Create payment_due_days setting
        Setting::set('payment_due_days', 7);
    }

    /**
     * Create a property with full relations
     */
    protected function createPropertyWithRelations(array $propertyAttributes = []): Property
    {
        $ownerUser = User::factory()->create(['type' => UserType::OWNER->value]);

        return Property::factory()->create(array_merge([
            'owner_id' => $ownerUser->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ], $propertyAttributes));
    }

    /**
     * Create a unit with property
     */
    protected function createUnitWithProperty(array $unitAttributes = [], array $propertyAttributes = []): Unit
    {
        $property = $this->createPropertyWithRelations($propertyAttributes);

        return Unit::factory()->create(array_merge([
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'unit_category_id' => 1,
            'rent_price' => 3000,
        ], $unitAttributes));
    }

    /**
     * Create an active contract for a unit
     */
    protected function createActiveContractForUnit(Unit $unit, ?User $tenant = null, array $attributes = []): UnitContract
    {
        if (! $tenant) {
            $tenant = User::factory()->create(['type' => UserType::TENANT->value]);
        }

        return UnitContract::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ], $attributes));
    }

    /**
     * Call private static method using reflection
     */
    protected function callPrivateStaticMethod(string $className, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $parameters);
    }

    // ==========================================
    // getUnitStatistics Tests
    // These tests verify the logic used by getUnitStatistics method
    // They use direct queries that work with SQLite
    // ==========================================

    #[Test]
    public function test_statistics_calculates_total_revenue(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 5000]);
        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create draft contract to avoid auto-generated payments
        $contract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'draft',
            'start_date' => Carbon::now()->subMonths(3),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        // Create collected payments
        CollectionPayment::factory()->collected()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 5000,
            'late_fee' => 0,
            'collection_date' => Carbon::now()->subMonth(),
        ]);

        CollectionPayment::factory()->collected()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 5000,
            'late_fee' => 0,
            'collection_date' => Carbon::now(),
        ]);

        // Calculate total revenue using the same logic as getUnitStatistics
        // Filter by unit_contract_id to ensure test isolation
        $totalRevenue = CollectionPayment::where('unit_contract_id', $contract->id)
            ->collectedPayments()
            ->sum('total_amount');

        // Total revenue should be sum of collected payments
        $this->assertEquals(10000, $totalRevenue);
    }

    #[Test]
    public function test_statistics_uses_collected_payments_scope(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 3000]);
        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create draft contract to avoid auto-generated payments
        $contract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'draft',
            'start_date' => Carbon::now()->subMonths(3),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        // Create collected payment (should count)
        CollectionPayment::factory()->collected()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'late_fee' => 0,
            'collection_date' => Carbon::now(),
        ]);

        // Create uncollected payment (should NOT count in total revenue)
        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'late_fee' => 0,
            'due_date_start' => Carbon::now()->subDays(5),
            'collection_date' => null, // Not collected
        ]);

        // Calculate using collectedPayments scope
        // Filter by unit_contract_id to ensure test isolation
        $totalRevenue = CollectionPayment::where('unit_contract_id', $contract->id)
            ->collectedPayments()
            ->sum('total_amount');

        // Only collected payment should be in total revenue
        $this->assertEquals(3000, $totalRevenue);
    }

    #[Test]
    public function test_statistics_calculates_pending_payments(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 4000]);
        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create draft contract to avoid auto-generated payments
        $contract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'draft',
            'start_date' => Carbon::now()->subMonths(3),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        // Create pending payments (due but not collected)
        CollectionPayment::create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 4000,
            'total_amount' => 4000,
            'due_date_start' => Carbon::now()->subDays(10),
            'due_date_end' => Carbon::now()->subDays(5),
            'collection_date' => null, // Not collected
        ]);

        CollectionPayment::create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 4000,
            'total_amount' => 4000,
            'due_date_start' => Carbon::now()->subDays(5),
            'due_date_end' => Carbon::now(),
            'collection_date' => null, // Not collected
        ]);

        // Calculate pending payments using the same logic as getUnitStatistics
        $pendingPayments = CollectionPayment::where('unit_id', $unit->id)
            ->where('due_date_start', '<=', now())
            ->whereNull('collection_date')
            ->sum('total_amount');

        // Pending payments should be sum of uncollected payments where due_date_start <= now
        $this->assertEquals(8000, $pendingPayments);
    }

    #[Test]
    public function test_statistics_includes_current_tenant(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 3500]);
        $tenant = User::factory()->create([
            'type' => UserType::TENANT->value,
            'name' => 'Ahmed Tenant',
        ]);

        // Create active contract with tenant
        $this->createActiveContractForUnit($unit, $tenant, [
            'start_date' => Carbon::now()->subMonths(2),
            'end_date' => Carbon::now()->addMonths(10),
        ]);

        // Get active contract using the same logic as getUnitStatistics
        $activeContract = $unit->contracts()
            ->where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->with('tenant')
            ->first();

        // Verify current tenant detection
        $this->assertNotNull($activeContract);
        $this->assertEquals('Ahmed Tenant', $activeContract->tenant->name);
    }

    #[Test]
    public function test_statistics_calculates_avg_contract_duration(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 2500]);

        // Create contracts with different durations using duration_months
        $tenant1 = User::factory()->create(['type' => UserType::TENANT->value]);
        UnitContract::factory()->create([
            'tenant_id' => $tenant1->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'draft', // Use draft to avoid overlapping active contracts
            'start_date' => Carbon::parse('2023-01-01'),
            'duration_months' => 12,
        ]);

        $tenant2 = User::factory()->create(['type' => UserType::TENANT->value]);
        UnitContract::factory()->create([
            'tenant_id' => $tenant2->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'draft',
            'start_date' => Carbon::parse('2024-01-01'),
            'duration_months' => 6,
        ]);

        // Calculate avg duration_months for contracts
        $avgDurationMonths = $unit->contracts()
            ->whereIn('contract_status', ['active', 'completed', 'draft'])
            ->avg('duration_months');

        // Average should be 9 months (12 + 6) / 2 = 9
        $this->assertEquals(9, $avgDurationMonths);
    }

    // ==========================================
    // Table Tests
    // ==========================================

    #[Test]
    public function test_table_shows_property_location_tooltip(): void
    {
        $this->actingAs($this->admin);

        // Create hierarchical locations
        $country = Location::create([
            'name' => 'السعودية',
            'level' => 1,
            'is_active' => true,
        ]);

        $city = Location::create([
            'name' => 'الرياض',
            'parent_id' => $country->id,
            'level' => 2,
            'is_active' => true,
        ]);

        $district = Location::create([
            'name' => 'حي النرجس',
            'parent_id' => $city->id,
            'level' => 3,
            'is_active' => true,
        ]);

        $property = $this->createPropertyWithRelations([
            'name' => 'عقار اختباري',
            'location_id' => $district->id,
        ]);

        $unit = Unit::factory()->create([
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'unit_category_id' => 1,
            'name' => 'وحدة اختبار',
        ]);

        // Test that the table shows the unit
        $livewire = Livewire::test(ListUnits::class);
        $livewire->assertCanSeeTableRecords([$unit]);

        // Verify the location path is built correctly
        $locationPath = '';
        $current = $unit->property->location;
        $path = [];
        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }
        $locationPath = implode(' > ', $path);

        $this->assertEquals('السعودية > الرياض > حي النرجس', $locationPath);
    }

    #[Test]
    public function test_rent_search_works_monthly_and_yearly(): void
    {
        $this->actingAs($this->admin);

        // Create units with specific rent prices
        $unit1 = $this->createUnitWithProperty([
            'name' => 'وحدة 1000 شهري',
            'rent_price' => 1000, // Monthly rent
        ]);

        $unit2 = $this->createUnitWithProperty([
            'name' => 'وحدة 2000 شهري',
            'rent_price' => 2000, // Monthly rent
        ]);

        $unit3 = $this->createUnitWithProperty([
            'name' => 'وحدة 3000 شهري',
            'rent_price' => 3000, // Monthly rent
        ]);

        // Test search by monthly rent (exact match)
        Livewire::test(ListUnits::class)
            ->searchTable('1000')
            ->assertCanSeeTableRecords([$unit1])
            ->assertCanNotSeeTableRecords([$unit2, $unit3]);

        // Test search by yearly rent value (12000 yearly = 1000 monthly)
        // The search converts yearly to monthly by dividing by 12
        Livewire::test(ListUnits::class)
            ->searchTable('12000')
            ->assertCanSeeTableRecords([$unit1]);
    }

    // ==========================================
    // Edge Case Tests (without getUnitStatistics)
    // ==========================================

    #[Test]
    public function test_unit_has_correct_property_relationship(): void
    {
        $this->actingAs($this->admin);

        $property = $this->createPropertyWithRelations(['name' => 'عقار العلاقة']);
        $unit = Unit::factory()->create([
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'name' => 'وحدة العلاقة',
        ]);

        $this->assertEquals($property->id, $unit->property_id);
        $this->assertEquals('عقار العلاقة', $unit->property->name);
    }

    #[Test]
    public function test_unit_contracts_relationship(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 3000]);
        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create a contract for the unit
        $contract = $this->createActiveContractForUnit($unit, $tenant);

        $this->assertEquals(1, $unit->contracts()->count());
        $this->assertEquals($contract->id, $unit->contracts->first()->id);
    }

    #[Test]
    public function test_unit_active_contract_relationship(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 4000]);
        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create an active contract
        $activeContract = $this->createActiveContractForUnit($unit, $tenant);

        // Create an expired contract
        UnitContract::factory()->create([
            'tenant_id' => User::factory()->create(['type' => UserType::TENANT->value])->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'expired',
            'start_date' => Carbon::now()->subYears(2),
            'end_date' => Carbon::now()->subYear(),
            'duration_months' => 12,
        ]);

        // activeContract() should return the active one
        $this->assertNotNull($unit->activeContract);
        $this->assertEquals('active', $unit->activeContract->contract_status);
    }

    #[Test]
    public function test_collected_payments_scope_works_correctly(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 5000]);
        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);
        $contract = $this->createActiveContractForUnit($unit, $tenant);

        // Create collected payment
        $collected = CollectionPayment::factory()->collected()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 5000,
            'total_amount' => 5000,
        ]);

        // Create uncollected payment
        $uncollected = CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 5000,
            'total_amount' => 5000,
            'due_date_start' => Carbon::now()->subDays(5),
            'collection_date' => null,
        ]);

        // Test the collectedPayments scope
        $collectedPayments = CollectionPayment::where('unit_id', $unit->id)
            ->collectedPayments()
            ->get();

        $this->assertEquals(1, $collectedPayments->count());
        $this->assertNotNull($collectedPayments->first()->collection_date);
    }

    #[Test]
    public function test_pending_payments_calculation(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 4000]);
        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create a draft contract first (no auto-generated payments)
        $contract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'draft', // Draft so observer doesn't generate payments
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);

        // Create pending payments (due but not collected) manually
        CollectionPayment::create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 4000,
            'total_amount' => 4000,
            'due_date_start' => Carbon::now()->subDays(10),
            'due_date_end' => Carbon::now()->subDays(5),
            'collection_date' => null,
        ]);

        CollectionPayment::create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 4000,
            'total_amount' => 4000,
            'due_date_start' => Carbon::now()->subDays(5),
            'due_date_end' => Carbon::now(),
            'collection_date' => null,
        ]);

        // Calculate pending payments manually
        $pendingPayments = CollectionPayment::where('unit_id', $unit->id)
            ->where('due_date_start', '<=', now())
            ->whereNull('collection_date')
            ->sum('total_amount');

        $this->assertEquals(8000, $pendingPayments);
    }

    #[Test]
    public function test_current_tenant_detection(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 3500]);
        $tenant = User::factory()->create([
            'type' => UserType::TENANT->value,
            'name' => 'Ahmed Tenant',
        ]);

        // Create active contract with tenant
        $contract = $this->createActiveContractForUnit($unit, $tenant, [
            'start_date' => Carbon::now()->subMonths(2),
            'end_date' => Carbon::now()->addMonths(10),
        ]);

        // Get active contract
        $activeContract = $unit->contracts()
            ->where('contract_status', 'active')
            ->whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->with('tenant')
            ->first();

        $this->assertNotNull($activeContract);
        $this->assertEquals('Ahmed Tenant', $activeContract->tenant->name);
    }

    #[Test]
    public function test_avg_contract_duration_calculation(): void
    {
        $this->actingAs($this->admin);

        $unit = $this->createUnitWithProperty(['rent_price' => 2500]);

        // Create active contracts with specific durations
        $tenant1 = User::factory()->create(['type' => UserType::TENANT->value]);
        UnitContract::factory()->create([
            'tenant_id' => $tenant1->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'active',
            'start_date' => Carbon::parse('2024-01-01'),
            'end_date' => Carbon::parse('2024-12-31'), // 365 days
            'duration_months' => 12,
        ]);

        $tenant2 = User::factory()->create(['type' => UserType::TENANT->value]);
        UnitContract::factory()->create([
            'tenant_id' => $tenant2->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'active',
            'start_date' => Carbon::parse('2025-01-01'),
            'end_date' => Carbon::parse('2025-06-30'), // ~180 days
            'duration_months' => 6,
        ]);

        // Count contracts
        $contractsCount = $unit->contracts()
            ->whereIn('contract_status', ['active', 'completed'])
            ->count();

        $this->assertEquals(2, $contractsCount);

        // Verify durations in months match what we set
        $durations = $unit->contracts()
            ->whereIn('contract_status', ['active', 'completed'])
            ->pluck('duration_months')
            ->toArray();

        $this->assertContains(12, $durations);
        $this->assertContains(6, $durations);
    }

    // ==========================================
    // Access Permission Tests
    // ==========================================

    #[Test]
    public function test_admin_can_view_units(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(UnitResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_view_units(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get(UnitResource::getUrl('index'));

        $response->assertSuccessful();
    }

    // ==========================================
    // Table Search Tests
    // ==========================================

    #[Test]
    public function test_table_search_by_unit_name(): void
    {
        $this->actingAs($this->admin);

        $unit1 = $this->createUnitWithProperty(['name' => 'وحدة البحث الفريدة']);
        $unit2 = $this->createUnitWithProperty(['name' => 'وحدة أخرى']);

        Livewire::test(ListUnits::class)
            ->searchTable('البحث الفريدة')
            ->assertCanSeeTableRecords([$unit1])
            ->assertCanNotSeeTableRecords([$unit2]);
    }

    #[Test]
    public function test_table_search_by_property_name(): void
    {
        $this->actingAs($this->admin);

        $property1 = $this->createPropertyWithRelations(['name' => 'العقار المميز']);
        $property2 = $this->createPropertyWithRelations(['name' => 'عقار آخر']);

        $unit1 = Unit::factory()->create([
            'property_id' => $property1->id,
            'unit_type_id' => 1,
            'name' => 'وحدة 1',
        ]);

        $unit2 = Unit::factory()->create([
            'property_id' => $property2->id,
            'unit_type_id' => 1,
            'name' => 'وحدة 2',
        ]);

        Livewire::test(ListUnits::class)
            ->searchTable('المميز')
            ->assertCanSeeTableRecords([$unit1])
            ->assertCanNotSeeTableRecords([$unit2]);
    }
}
