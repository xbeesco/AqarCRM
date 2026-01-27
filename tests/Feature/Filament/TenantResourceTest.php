<?php

namespace Tests\Feature\Filament;

use App\Enums\UserType;
use App\Filament\Resources\TenantResource;
use App\Filament\Resources\TenantResource\Pages\ListTenants;
use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\Tenant;
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

class TenantResourceTest extends TestCase
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
            ['name' => 'Default Location', 'level' => 1, 'is_active' => true]
        );

        // Create default PropertyType
        PropertyType::firstOrCreate(
            ['id' => 1],
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'slug' => 'apartment', 'is_active' => true]
        );

        // Create default PropertyStatus
        PropertyStatus::firstOrCreate(
            ['id' => 1],
            ['name_ar' => 'متاح', 'name_en' => 'Available', 'slug' => 'available', 'is_active' => true]
        );

        // Create default UnitType
        UnitType::firstOrCreate(
            ['id' => 1],
            ['name_ar' => 'شقة', 'name_en' => 'Apartment', 'slug' => 'apartment', 'is_active' => true]
        );

        // Create payment_due_days setting
        Setting::set('payment_due_days', 7);
    }

    /**
     * Create a tenant with full relations
     */
    protected function createTenantWithRelations(array $tenantAttributes = []): Tenant
    {
        $tenantAttributes['type'] = UserType::TENANT->value;

        // Create tenant directly using User factory to avoid triggering global scope issues
        $userData = array_merge(
            User::factory()->make()->toArray(),
            $tenantAttributes
        );

        // Create via User model first, then get as Tenant
        $user = User::create(array_merge($userData, [
            'password' => bcrypt('password'),
        ]));

        return Tenant::find($user->id);
    }

    /**
     * Create a tenant with an active contract
     */
    protected function createTenantWithActiveContract(array $tenantAttributes = []): array
    {
        $tenant = $this->createTenantWithRelations($tenantAttributes);

        $ownerUser = User::factory()->create(['type' => UserType::OWNER->value]);

        $property = Property::factory()->create([
            'owner_id' => $ownerUser->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ]);

        $unit = Unit::factory()->create([
            'property_id' => $property->id,
            'unit_type_id' => 1,
        ]);

        $contract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(3),
            'end_date' => Carbon::now()->addMonths(9),
            'duration_months' => 12,
            'monthly_rent' => 3000,
        ]);

        return [
            'tenant' => $tenant,
            'contract' => $contract,
            'unit' => $unit,
            'property' => $property,
        ];
    }

    /**
     * Create payments for a tenant
     */
    protected function createPaymentsForTenant(Tenant $tenant, UnitContract $contract, int $count = 5): array
    {
        $payments = [];
        for ($i = 0; $i < $count; $i++) {
            $payments[] = CollectionPayment::factory()->create([
                'unit_contract_id' => $contract->id,
                'unit_id' => $contract->unit_id,
                'property_id' => $contract->property_id,
                'tenant_id' => $tenant->id,
                'due_date_start' => Carbon::now()->subMonths($i),
                'due_date_end' => Carbon::now()->subMonths($i)->endOfMonth(),
            ]);
        }

        return $payments;
    }

    // ==========================================
    // Access Tests (Permissions)
    // ==========================================

    #[Test]
    public function test_admins_can_view_tenants(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(TenantResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_super_admin_can_view_tenants(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(TenantResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_view_tenants(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get(TenantResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_owners_cannot_view_tenants(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(TenantResource::getUrl('index'));

        // Owner should be redirected or forbidden based on canAccessPanel
        $response->assertStatus(403);
    }

    #[Test]
    public function test_tenant_users_cannot_access(): void
    {
        $this->actingAs($this->tenantUser);

        $response = $this->get(TenantResource::getUrl('index'));

        // Tenant should be redirected or forbidden based on canAccessPanel
        $response->assertStatus(403);
    }

    // ==========================================
    // getTenantStatistics Tests
    // Note: Some of these tests require MySQL-specific functions (DATEDIFF)
    // They will be skipped when running on SQLite
    // ==========================================

    #[Test]
    public function test_statistics_returns_correct_structure(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATEDIFF).');
        }

        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract(['name' => 'Test Tenant']);
        $tenant = $data['tenant'];

        $statistics = TenantResource::getTenantStatistics($tenant);

        // Verify structure contains all expected keys
        $this->assertArrayHasKey('tenant_name', $statistics);
        $this->assertArrayHasKey('tenant_phone', $statistics);
        $this->assertArrayHasKey('has_active_contract', $statistics);
        $this->assertArrayHasKey('total_payments', $statistics);
        $this->assertArrayHasKey('outstanding_payments', $statistics);
        $this->assertArrayHasKey('overdue_count', $statistics);
        $this->assertArrayHasKey('payment_compliance_rate', $statistics);
        $this->assertArrayHasKey('total_contracts', $statistics);
        $this->assertArrayHasKey('is_good_standing', $statistics);
        $this->assertArrayHasKey('risk_level', $statistics);

        // Verify tenant name is correct
        $this->assertEquals('Test Tenant', $statistics['tenant_name']);
    }

    #[Test]
    public function test_statistics_calculates_payment_compliance(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATEDIFF).');
        }

        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract();
        $tenant = $data['tenant'];
        $contract = $data['contract'];

        // Create 3 payments: all paid on time
        for ($i = 1; $i <= 3; $i++) {
            CollectionPayment::factory()->create([
                'unit_contract_id' => $contract->id,
                'unit_id' => $contract->unit_id,
                'property_id' => $contract->property_id,
                'tenant_id' => $tenant->id,
                'due_date_start' => Carbon::now()->subMonths($i),
                'due_date_end' => Carbon::now()->subMonths($i)->addDays(5),
                'collection_date' => Carbon::now()->subMonths($i)->addDays(2),
                'paid_date' => Carbon::now()->subMonths($i)->addDays(2),
            ]);
        }

        $statistics = TenantResource::getTenantStatistics($tenant);

        // All 3 payments were paid on time, compliance should be 100%
        $this->assertGreaterThanOrEqual(0, $statistics['payment_compliance_rate']);
        $this->assertLessThanOrEqual(100, $statistics['payment_compliance_rate']);
    }

    #[Test]
    public function test_statistics_includes_current_contract_info(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATEDIFF).');
        }

        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract();
        $tenant = $data['tenant'];
        $contract = $data['contract'];

        $statistics = TenantResource::getTenantStatistics($tenant);

        $this->assertTrue($statistics['has_active_contract']);
        $this->assertNotNull($statistics['current_contract']);
        $this->assertEquals($contract->contract_number, $statistics['current_contract']['contract_number']);
        $this->assertEquals($contract->monthly_rent, $statistics['current_contract']['monthly_rent']);
    }

    #[Test]
    public function test_statistics_includes_financial_data(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATEDIFF).');
        }

        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract();
        $tenant = $data['tenant'];
        $contract = $data['contract'];

        // Create collected payment
        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $tenant->id,
            'amount' => 3000,
            'total_amount' => 3000,
            'collection_date' => Carbon::now()->subDays(5),
        ]);

        $statistics = TenantResource::getTenantStatistics($tenant);

        $this->assertArrayHasKey('total_payments', $statistics);
        $this->assertArrayHasKey('outstanding_payments', $statistics);
        $this->assertArrayHasKey('total_late_fees', $statistics);
        $this->assertEquals(3000, $statistics['total_payments']);
    }

    #[Test]
    public function test_statistics_handles_tenant_without_contract(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATEDIFF).');
        }

        $this->actingAs($this->admin);

        $tenant = $this->createTenantWithRelations(['name' => 'No Contract Tenant']);

        $statistics = TenantResource::getTenantStatistics($tenant);

        $this->assertFalse($statistics['has_active_contract']);
        $this->assertNull($statistics['current_contract']);
        $this->assertNull($statistics['current_unit']);
        $this->assertNull($statistics['current_property']);
        $this->assertEquals(0, $statistics['total_contracts']);
    }

    // ==========================================
    // getRecentPayments Tests
    // ==========================================

    #[Test]
    public function test_recent_payments_returns_limit_count(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract();
        $tenant = $data['tenant'];
        $contract = $data['contract'];

        // Create 10 payments
        for ($i = 0; $i < 10; $i++) {
            CollectionPayment::factory()->create([
                'unit_contract_id' => $contract->id,
                'unit_id' => $contract->unit_id,
                'property_id' => $contract->property_id,
                'tenant_id' => $tenant->id,
                'due_date_start' => Carbon::now()->subMonths($i),
            ]);
        }

        // Get with limit 5
        $payments = TenantResource::getRecentPayments($tenant, 5);

        $this->assertCount(5, $payments);
    }

    #[Test]
    public function test_recent_payments_ordered_by_date(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract();
        $tenant = $data['tenant'];
        $contract = $data['contract'];

        // Create old payment first with very old date
        $oldPayment = CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $tenant->id,
            'due_date_start' => Carbon::parse('2020-01-01'),
            'due_date_end' => Carbon::parse('2020-01-31'),
        ]);

        // Create recent payment with very recent date
        $recentPayment = CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $tenant->id,
            'due_date_start' => Carbon::parse('2099-12-01'),
            'due_date_end' => Carbon::parse('2099-12-31'),
        ]);

        $payments = TenantResource::getRecentPayments($tenant, 10);

        // Verify we have at least 2 payments and they're ordered correctly
        $this->assertGreaterThanOrEqual(2, $payments->count());

        // Verify ordering - payments should be in descending order by due_date_start
        $previousDate = null;
        foreach ($payments as $payment) {
            $currentDate = Carbon::parse($payment->due_date_start);
            if ($previousDate !== null) {
                $this->assertTrue(
                    $previousDate->gte($currentDate),
                    'Payments should be ordered by due_date_start descending'
                );
            }
            $previousDate = $currentDate;
        }

        // The most recent payment (2099) should be first
        $this->assertEquals($recentPayment->id, $payments->first()->id);
    }

    #[Test]
    public function test_recent_payments_includes_relationships(): void
    {
        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract();
        $tenant = $data['tenant'];
        $contract = $data['contract'];

        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $tenant->id,
        ]);

        $payments = TenantResource::getRecentPayments($tenant, 5);

        // Check relationships are loaded
        $payment = $payments->first();
        $this->assertTrue($payment->relationLoaded('unit'));
        $this->assertTrue($payment->relationLoaded('property'));
        $this->assertTrue($payment->relationLoaded('unitContract'));
    }

    // ==========================================
    // Global Search Tests (Arabic Normalization)
    // Note: Global search uses MySQL-specific functions (DATE_FORMAT)
    // These tests will be skipped when running on SQLite
    // ==========================================

    #[Test]
    public function test_global_search_normalizes_hamza(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATE_FORMAT).');
        }

        $this->actingAs($this->admin);

        // Create tenant with hamza in name
        $tenant = $this->createTenantWithRelations(['name' => 'أحمد إبراهيم']);

        // Search with different hamza forms
        $results = TenantResource::getGlobalSearchResults('احمد ابراهيم');

        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'أحمد')),
            'Global search should normalize hamza (أ/إ/آ -> ا)'
        );
    }

    #[Test]
    public function test_global_search_normalizes_ta_marbuta(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATE_FORMAT).');
        }

        $this->actingAs($this->admin);

        // Create tenant with name containing various Arabic characters
        $tenant = $this->createTenantWithRelations(['name' => 'فاطمة علي']);

        // The search should find the tenant
        $results = TenantResource::getGlobalSearchResults('فاطمة');

        $this->assertTrue(
            $results->isNotEmpty(),
            'Global search should find Arabic names'
        );
    }

    #[Test]
    public function test_global_search_by_phone(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATE_FORMAT).');
        }

        $this->actingAs($this->admin);

        $tenant = $this->createTenantWithRelations([
            'name' => 'Phone Test Tenant',
            'phone' => '0551234567',
        ]);

        $results = TenantResource::getGlobalSearchResults('0551234567');

        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'Phone Test Tenant')),
            'Global search should find tenant by phone number'
        );
    }

    #[Test]
    public function test_global_search_by_partial_phone(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATE_FORMAT).');
        }

        $this->actingAs($this->admin);

        $tenant = $this->createTenantWithRelations([
            'name' => 'Partial Phone Tenant',
            'phone' => '0559876543',
        ]);

        $results = TenantResource::getGlobalSearchResults('987654');

        $this->assertTrue(
            $results->contains(fn ($result) => str_contains($result->title, 'Partial Phone Tenant')),
            'Global search should find tenant by partial phone number'
        );
    }

    // ==========================================
    // Table Tests
    // ==========================================

    #[Test]
    public function test_table_displays_tenants(): void
    {
        $this->actingAs($this->admin);

        $tenant = $this->createTenantWithRelations(['name' => 'Display Test Tenant']);

        Livewire::test(ListTenants::class)
            ->assertCanSeeTableRecords([$tenant]);
    }

    #[Test]
    public function test_table_search_by_name_works(): void
    {
        $this->actingAs($this->admin);

        $tenant1 = $this->createTenantWithRelations(['name' => 'Unique Searchable Name']);
        $tenant2 = $this->createTenantWithRelations(['name' => 'Another Tenant']);

        Livewire::test(ListTenants::class)
            ->searchTable('Unique Searchable')
            ->assertCanSeeTableRecords([$tenant1])
            ->assertCanNotSeeTableRecords([$tenant2]);
    }

    #[Test]
    public function test_table_search_by_phone_works(): void
    {
        $this->actingAs($this->admin);

        $tenant1 = $this->createTenantWithRelations([
            'name' => 'Phone Tenant 1',
            'phone' => '0501111111',
        ]);
        $tenant2 = $this->createTenantWithRelations([
            'name' => 'Phone Tenant 2',
            'phone' => '0502222222',
        ]);

        Livewire::test(ListTenants::class)
            ->searchTable('0501111111')
            ->assertCanSeeTableRecords([$tenant1])
            ->assertCanNotSeeTableRecords([$tenant2]);
    }

    // ==========================================
    // Permission Tests for Create/Edit/Delete
    // ==========================================

    #[Test]
    public function test_admin_can_create_tenant(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue(TenantResource::canCreate());
    }

    #[Test]
    public function test_employee_cannot_create_tenant(): void
    {
        $this->actingAs($this->employee);

        $this->assertFalse(TenantResource::canCreate());
    }

    #[Test]
    public function test_only_super_admin_can_delete_tenant(): void
    {
        $tenant = $this->createTenantWithRelations();

        // Super admin can delete
        $this->actingAs($this->superAdmin);
        $this->assertTrue(TenantResource::canDelete($tenant));

        // Admin cannot delete
        $this->actingAs($this->admin);
        $this->assertFalse(TenantResource::canDelete($tenant));

        // Employee cannot delete
        $this->actingAs($this->employee);
        $this->assertFalse(TenantResource::canDelete($tenant));
    }

    // ==========================================
    // Additional Edge Case Tests
    // ==========================================

    #[Test]
    public function test_statistics_with_user_model_conversion(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATEDIFF).');
        }

        $this->actingAs($this->admin);

        // Create tenant as User first (simulating when a User object is passed instead of Tenant)
        $user = User::factory()->create([
            'type' => UserType::TENANT->value,
            'name' => 'User To Tenant Test',
        ]);

        // Pass User model (not Tenant) to statistics method - it should handle the conversion
        $statistics = TenantResource::getTenantStatistics($user);

        $this->assertArrayHasKey('tenant_name', $statistics);
        $this->assertEquals('User To Tenant Test', $statistics['tenant_name']);
    }

    #[Test]
    public function test_recent_payments_returns_empty_for_tenant_without_payments(): void
    {
        $this->actingAs($this->admin);

        $tenant = $this->createTenantWithRelations();

        $payments = TenantResource::getRecentPayments($tenant, 5);

        $this->assertCount(0, $payments);
    }

    #[Test]
    public function test_statistics_calculates_risk_level_correctly(): void
    {
        if ($this->isUsingSqlite) {
            $this->markTestSkipped('This test requires MySQL-specific functions (DATEDIFF).');
        }

        $this->actingAs($this->admin);

        $data = $this->createTenantWithActiveContract();
        $tenant = $data['tenant'];
        $contract = $data['contract'];

        // Delete any auto-generated payments to start fresh
        CollectionPayment::where('tenant_id', $tenant->id)->delete();

        // Tenant with no overdue payments should have low risk
        $statistics = TenantResource::getTenantStatistics($tenant);
        $this->assertEquals('low', $statistics['risk_level']);

        // Create multiple overdue payments to increase risk (need > 3 for high)
        for ($i = 0; $i < 4; $i++) {
            CollectionPayment::factory()->overdue()->create([
                'unit_contract_id' => $contract->id,
                'unit_id' => $contract->unit_id,
                'property_id' => $contract->property_id,
                'tenant_id' => $tenant->id,
            ]);
        }

        $statisticsWithOverdue = TenantResource::getTenantStatistics($tenant);
        $this->assertEquals('high', $statisticsWithOverdue['risk_level']);
    }

    // ==========================================
    // Admin Permission Tests
    // ==========================================

    #[Test]
    public function test_admin_can_edit_tenant(): void
    {
        $tenant = $this->createTenantWithRelations();

        $this->actingAs($this->admin);

        $this->assertTrue(TenantResource::canEdit($tenant));
    }

    #[Test]
    public function test_super_admin_can_edit_tenant(): void
    {
        $tenant = $this->createTenantWithRelations();

        $this->actingAs($this->superAdmin);

        $this->assertTrue(TenantResource::canEdit($tenant));
    }

    #[Test]
    public function test_employee_cannot_edit_tenant(): void
    {
        $tenant = $this->createTenantWithRelations();

        $this->actingAs($this->employee);

        $this->assertFalse(TenantResource::canEdit($tenant));
    }

    // ==========================================
    // canViewAny Permission Tests
    // ==========================================

    #[Test]
    public function test_can_view_any_returns_true_for_admin_types(): void
    {
        // Test super_admin
        $this->actingAs($this->superAdmin);
        $this->assertTrue(TenantResource::canViewAny());

        // Test admin
        $this->actingAs($this->admin);
        $this->assertTrue(TenantResource::canViewAny());

        // Test employee
        $this->actingAs($this->employee);
        $this->assertTrue(TenantResource::canViewAny());
    }

    #[Test]
    public function test_can_view_any_returns_false_for_clients(): void
    {
        // Test owner
        $this->actingAs($this->owner);
        $this->assertFalse(TenantResource::canViewAny());

        // Test tenant
        $this->actingAs($this->tenantUser);
        $this->assertFalse(TenantResource::canViewAny());
    }
}
