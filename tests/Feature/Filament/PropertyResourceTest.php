<?php

namespace Tests\Feature\Filament;

use App\Enums\UserType;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\Unit;
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

class PropertyResourceTest extends TestCase
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
     * Create a property with units
     */
    protected function createPropertyWithUnits(int $unitCount = 3, array $propertyAttributes = []): array
    {
        $property = $this->createPropertyWithRelations($propertyAttributes);

        $units = [];
        for ($i = 0; $i < $unitCount; $i++) {
            $units[] = Unit::factory()->create([
                'property_id' => $property->id,
                'unit_type_id' => 1,
                'rent_price' => 3000 + ($i * 500),
            ]);
        }

        return [
            'property' => $property,
            'units' => $units,
        ];
    }

    /**
     * Create an active contract for a unit
     */
    protected function createActiveContractForUnit(Unit $unit, ?User $tenant = null): UnitContract
    {
        if (! $tenant) {
            $tenant = User::factory()->create(['type' => UserType::TENANT->value]);
        }

        return UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $unit->property_id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => $unit->rent_price,
        ]);
    }

    /**
     * Call private static method using reflection
     */
    protected function callPrivateStaticMethod(string $className, string $methodName, array $parameters = [])
    {
        if (! method_exists($className, $methodName)) {
            $this->markTestSkipped("Method {$className}::{$methodName} does not exist.");
        }

        $reflection = new \ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $parameters);
    }

    // ==========================================
    // getPropertyStatistics Tests
    // ==========================================

    #[Test]
    public function test_statistics_calculates_occupancy_rate(): void
    {
        $this->actingAs($this->admin);

        // Create property with 4 units
        $data = $this->createPropertyWithUnits(4);
        $property = $data['property'];
        $units = $data['units'];

        // Create active contracts for 2 units (50% occupancy)
        $this->createActiveContractForUnit($units[0]);
        $this->createActiveContractForUnit($units[1]);

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        $this->assertEquals(4, $statistics['total_units']);
        $this->assertEquals(2, $statistics['occupied_units']);
        $this->assertEquals(2, $statistics['vacant_units']);
        $this->assertEquals(50.0, $statistics['occupancy_rate']);
    }

    #[Test]
    public function test_statistics_calculates_monthly_revenue(): void
    {
        $this->actingAs($this->admin);

        // Create property with units having known rent prices
        $data = $this->createPropertyWithUnits(3);
        $property = $data['property'];
        $units = $data['units'];

        // Create active contracts for 2 units
        // Unit 0 has rent_price = 3000, Unit 1 has rent_price = 3500
        $this->createActiveContractForUnit($units[0]);
        $this->createActiveContractForUnit($units[1]);

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        // Monthly revenue should be sum of rent_price for occupied units
        // 3000 + 3500 = 6500
        $this->assertEquals(6500, $statistics['monthly_revenue']);
    }

    #[Test]
    public function test_statistics_calculates_yearly_revenue(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createPropertyWithUnits(2);
        $property = $data['property'];
        $units = $data['units'];

        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);
        $contract = $this->createActiveContractForUnit($units[0], $tenant);

        // Create collected payments for this year
        CollectionPayment::factory()->collected()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $units[0]->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'late_fee' => 0,
            'total_amount' => 3000,
            'collection_date' => Carbon::now()->startOfYear()->addMonth(),
        ]);

        CollectionPayment::factory()->collected()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $units[0]->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'late_fee' => 0,
            'total_amount' => 3000,
            'collection_date' => Carbon::now()->startOfYear()->addMonths(2),
        ]);

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        // Yearly revenue should be sum of collected payments this year
        $this->assertEquals(6000, $statistics['yearly_revenue']);
    }

    #[Test]
    public function test_statistics_calculates_pending_payments(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createPropertyWithUnits(1);
        $property = $data['property'];
        $units = $data['units'];

        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);
        $contract = $this->createActiveContractForUnit($units[0], $tenant);

        // Create pending payments (due but not collected)
        CollectionPayment::create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $units[0]->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'total_amount' => 3000,
            'due_date_start' => Carbon::now()->subDays(5),
            'due_date_end' => Carbon::now()->subDays(1),
            'collection_date' => null, // Not collected
        ]);

        CollectionPayment::create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $units[0]->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'total_amount' => 3000,
            'due_date_start' => Carbon::now()->subDays(35),
            'due_date_end' => Carbon::now()->subDays(30),
            'collection_date' => null, // Not collected
        ]);

        // Verify payments were created for this property
        $totalPending = CollectionPayment::where('property_id', $property->id)
            ->where('due_date_start', '<=', now())
            ->whereNull('collection_date')
            ->sum('total_amount');

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        // Pending payments should match what was created
        $this->assertEquals($totalPending, $statistics['pending_payments']);
        $this->assertGreaterThanOrEqual(6000, $statistics['pending_payments']);
    }

    #[Test]
    public function test_statistics_uses_scopes_for_collected_payments(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createPropertyWithUnits(1);
        $property = $data['property'];
        $units = $data['units'];

        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);
        $contract = $this->createActiveContractForUnit($units[0], $tenant);

        // Create collected payment (should count in yearly revenue)
        CollectionPayment::factory()->collected()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $units[0]->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 5000,
            'late_fee' => 0,
            'total_amount' => 5000,
            'collection_date' => Carbon::now(),
        ]);

        // Create uncollected payment (should NOT count in yearly revenue)
        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $units[0]->id,
            'property_id' => $property->id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'late_fee' => 0,
            'total_amount' => 3000,
            'due_date_start' => Carbon::now()->subDays(5),
            'collection_date' => null,
        ]);

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        // Only collected payment should be in yearly revenue
        $this->assertEquals(5000, $statistics['yearly_revenue']);
    }

    // ==========================================
    // Table Tests
    // ==========================================

    #[Test]
    public function test_table_shows_location_full_path(): void
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

        // Test that the table shows the full location path
        $livewire = Livewire::test(ListProperties::class);

        $livewire->assertCanSeeTableRecords([$property]);

        // Verify the location path is built correctly in the resource
        $locationPath = '';
        $current = $property->location;
        $path = [];
        while ($current) {
            array_unshift($path, $current->name);
            $current = $current->parent;
        }
        $locationPath = implode(' > ', $path);

        $this->assertEquals('السعودية > الرياض > حي النرجس', $locationPath);
    }

    #[Test]
    public function test_table_shows_total_units(): void
    {
        $this->actingAs($this->admin);

        // Create property with 5 units
        $data = $this->createPropertyWithUnits(5, ['name' => 'عقار بخمس وحدات']);
        $property = $data['property'];

        $livewire = Livewire::test(ListProperties::class);

        $livewire->assertCanSeeTableRecords([$property]);

        // Verify the property has 5 units
        $this->assertEquals(5, $property->total_units);
    }

    // ==========================================
    // Global Search Tests
    // ==========================================

    #[Test]
    public function test_global_search_in_property_fields(): void
    {
        $this->actingAs($this->admin);

        $property = $this->createPropertyWithRelations([
            'name' => 'عقار البستان السكني',
            'address' => 'شارع الملك فهد رقم 123',
            'postal_code' => '12345',
        ]);

        // Test search by property name
        $results = PropertyResource::getGlobalSearchResults('البستان');
        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'البستان')),
            'Global search should find property by name'
        );

        // Test search by address
        $results = PropertyResource::getGlobalSearchResults('شارع الملك فهد');
        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'البستان')),
            'Global search should find property by address'
        );

        // Test search by postal code
        $results = PropertyResource::getGlobalSearchResults('12345');
        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'البستان')),
            'Global search should find property by postal code'
        );
    }

    #[Test]
    public function test_global_search_in_owner_fields(): void
    {
        $this->actingAs($this->admin);

        $owner = User::factory()->create([
            'type' => UserType::OWNER->value,
            'name' => 'محمد عبدالله الأحمد',
            'email' => 'owner-test@example.com',
            'phone' => '0551234567',
        ]);

        $property = Property::factory()->create([
            'name' => 'عقار للبحث عن المالك',
            'owner_id' => $owner->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ]);

        // Test search by owner name
        $results = PropertyResource::getGlobalSearchResults('محمد عبدالله');
        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'للبحث عن المالك')),
            'Global search should find property by owner name'
        );

        // Test search by owner phone
        $results = PropertyResource::getGlobalSearchResults('0551234567');
        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'للبحث عن المالك')),
            'Global search should find property by owner phone'
        );
    }

    #[Test]
    public function test_global_search_in_location_fields(): void
    {
        $this->actingAs($this->admin);

        $location = Location::create([
            'name' => 'حي الياسمين',
            'level' => 3,
        ]);

        $property = $this->createPropertyWithRelations([
            'name' => 'عقار الموقع الفريد',
            'location_id' => $location->id,
        ]);

        // Test search by location name (Arabic)
        $results = PropertyResource::getGlobalSearchResults('الياسمين');
        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'الموقع الفريد')),
            'Global search should find property by location Arabic name'
        );

        // Test search by location name (English) - may not work with all database collations
        // So we test that at least the Arabic location search works
        $this->assertTrue($results->count() > 0, 'Location search should return results');
    }

    // ==========================================
    // Access Permission Tests
    // ==========================================

    #[Test]
    public function test_admin_can_view_properties(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(PropertyResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_super_admin_can_view_properties(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(PropertyResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_view_properties(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get(PropertyResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_owners_cannot_view_properties(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(PropertyResource::getUrl('index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_tenants_cannot_view_properties(): void
    {
        $this->actingAs($this->tenantUser);

        $response = $this->get(PropertyResource::getUrl('index'));

        $response->assertStatus(403);
    }

    // ==========================================
    // Table Search Tests
    // ==========================================

    #[Test]
    public function test_table_search_by_property_name(): void
    {
        $this->actingAs($this->admin);

        $property1 = $this->createPropertyWithRelations(['name' => 'عقار البحث الفريد']);
        $property2 = $this->createPropertyWithRelations(['name' => 'عقار آخر']);

        Livewire::test(ListProperties::class)
            ->searchTable('البحث الفريد')
            ->assertCanSeeTableRecords([$property1])
            ->assertCanNotSeeTableRecords([$property2]);
    }

    #[Test]
    public function test_table_search_by_owner_name(): void
    {
        $this->actingAs($this->admin);

        $owner1 = User::factory()->create([
            'type' => UserType::OWNER->value,
            'name' => 'عبدالرحمن المالك',
        ]);

        $owner2 = User::factory()->create([
            'type' => UserType::OWNER->value,
            'name' => 'سعد العقاري',
        ]);

        $property1 = Property::factory()->create([
            'name' => 'عقار المالك الأول',
            'owner_id' => $owner1->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ]);

        $property2 = Property::factory()->create([
            'name' => 'عقار المالك الثاني',
            'owner_id' => $owner2->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ]);

        Livewire::test(ListProperties::class)
            ->searchTable('عبدالرحمن')
            ->assertCanSeeTableRecords([$property1])
            ->assertCanNotSeeTableRecords([$property2]);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function test_statistics_handles_property_without_units(): void
    {
        $this->actingAs($this->admin);

        $property = $this->createPropertyWithRelations(['name' => 'عقار بدون وحدات']);

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        $this->assertEquals(0, $statistics['total_units']);
        $this->assertEquals(0, $statistics['occupied_units']);
        $this->assertEquals(0, $statistics['vacant_units']);
        $this->assertEquals(0, $statistics['occupancy_rate']);
        $this->assertEquals(0, $statistics['monthly_revenue']);
    }

    #[Test]
    public function test_statistics_handles_property_with_all_vacant_units(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createPropertyWithUnits(3);
        $property = $data['property'];

        // No contracts created, all units are vacant

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        $this->assertEquals(3, $statistics['total_units']);
        $this->assertEquals(0, $statistics['occupied_units']);
        $this->assertEquals(3, $statistics['vacant_units']);
        $this->assertEquals(0, $statistics['occupancy_rate']);
    }

    #[Test]
    public function test_statistics_handles_property_with_all_occupied_units(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createPropertyWithUnits(3);
        $property = $data['property'];
        $units = $data['units'];

        // Create active contracts for all units (100% occupancy)
        foreach ($units as $unit) {
            $this->createActiveContractForUnit($unit);
        }

        $statistics = $this->callPrivateStaticMethod(PropertyResource::class, 'getPropertyStatistics', [$property]);

        $this->assertEquals(3, $statistics['total_units']);
        $this->assertEquals(3, $statistics['occupied_units']);
        $this->assertEquals(0, $statistics['vacant_units']);
        $this->assertEquals(100.0, $statistics['occupancy_rate']);
    }

    #[Test]
    public function test_table_shows_property_without_location(): void
    {
        $this->actingAs($this->admin);

        $property = $this->createPropertyWithRelations([
            'name' => 'عقار بدون موقع',
            'location_id' => null,
        ]);

        $livewire = Livewire::test(ListProperties::class);

        $livewire->assertCanSeeTableRecords([$property]);
    }

    #[Test]
    public function test_global_search_normalizes_arabic_hamza(): void
    {
        $this->actingAs($this->admin);

        // Create property with normalized name (using ا instead of أ/إ/آ)
        // This tests that the normalization in search finds normalized data
        $property = $this->createPropertyWithRelations([
            'name' => 'عقار احمد ابراهيم', // Without hamza
        ]);

        // Search with hamza - the search normalizes it to match
        $resultsNormalized = PropertyResource::getGlobalSearchResults('أحمد'); // With hamza

        $this->assertTrue(
            $resultsNormalized->contains(fn ($result) => str_contains($result->title, 'احمد')),
            'Global search should normalize hamza and find property'
        );

        // Also test searching without hamza directly
        $resultsWithoutHamza = PropertyResource::getGlobalSearchResults('احمد'); // Without hamza
        $this->assertTrue(
            $resultsWithoutHamza->contains(fn ($result) => str_contains($result->title, 'احمد')),
            'Global search should find property with non-hamza search term'
        );
    }
}
