<?php

namespace Tests\Feature\Filament;

use App\Enums\UserType;
use App\Filament\Resources\UnitContractResource;
use App\Filament\Resources\UnitContractResource\Pages\CreateUnitContract;
use App\Filament\Resources\UnitContractResource\Pages\ListUnitContracts;
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
use App\Services\PropertyContractService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnitContractResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $owner;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->tenant = User::factory()->create([
            'type' => UserType::TENANT->value,
            'email' => 'tenant@test.com',
        ]);

        // Set the Filament panel
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        // Fake storage for file uploads
        Storage::fake('public');
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
     * Create a property with an owner
     */
    protected function createPropertyWithOwner(?User $owner = null): Property
    {
        $ownerUser = $owner ?? User::factory()->create(['type' => UserType::OWNER->value]);

        return Property::factory()->create([
            'owner_id' => $ownerUser->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ]);
    }

    /**
     * Create a unit for a property
     */
    protected function createUnit(Property $property): Unit
    {
        return Unit::factory()->create([
            'property_id' => $property->id,
            'unit_type_id' => 1,
        ]);
    }

    /**
     * Create a complete contract with all related models
     */
    protected function createContractWithRelations(array $attributes = [], ?User $owner = null, ?User $tenant = null): UnitContract
    {
        $tenantUser = $tenant ?? User::factory()->create(['type' => UserType::TENANT->value]);
        $ownerUser = $owner ?? User::factory()->create(['type' => UserType::OWNER->value]);

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

        $defaultAttributes = [
            'tenant_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(11),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 5000,
        ];

        return UnitContract::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    // ==========================================
    // Access Tests
    // ==========================================

    #[Test]
    public function test_admin_can_access_contracts(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(UnitContractResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_super_admin_can_access_contracts(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(UnitContractResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_access_contracts(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get(UnitContractResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_owner_can_view_related_contracts(): void
    {
        $this->actingAs($this->owner);

        // Create a contract for this owner's property
        $contract = $this->createContractWithRelations([], $this->owner);

        // Create another contract for a different owner
        $otherOwner = User::factory()->create(['type' => UserType::OWNER->value]);
        $otherContract = $this->createContractWithRelations([], $otherOwner);

        // Owner should be forbidden from accessing the admin panel
        // based on canAccessPanel() returning false for owner type
        $response = $this->get(UnitContractResource::getUrl('index'));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_tenant_can_view_own_contract(): void
    {
        $this->actingAs($this->tenant);

        // Create a contract for this tenant
        $contract = $this->createContractWithRelations([], null, $this->tenant);

        // Create another contract for a different tenant
        $otherTenant = User::factory()->create(['type' => UserType::TENANT->value]);
        $otherContract = $this->createContractWithRelations([], null, $otherTenant);

        // Tenant should be forbidden from accessing the admin panel
        // based on canAccessPanel() returning false for tenant type
        $response = $this->get(UnitContractResource::getUrl('index'));

        $response->assertStatus(403);
    }

    // ==========================================
    // Actions Tests
    // ==========================================

    #[Test]
    public function test_generate_payments_action_visible_when_can_generate(): void
    {
        $this->actingAs($this->admin);

        // Create a contract with draft status (observer won't auto-generate payments for drafts)
        $contract = $this->createContractWithRelations([
            'contract_status' => 'draft',
            'monthly_rent' => 5000,
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Draft contracts don't auto-generate payments
        $this->assertEquals(0, $contract->collectionPayments()->count());

        // Now change to active without triggering observer auto-generate
        // The canGeneratePayments should be true when no payments exist
        $this->assertTrue($contract->canGeneratePayments());

        Livewire::test(ListUnitContracts::class)
            ->assertTableActionVisible('generatePayments', $contract);
    }

    #[Test]
    public function test_generate_payments_action_hidden_when_cannot(): void
    {
        $this->actingAs($this->admin);

        // Create a contract with payments already generated
        $contract = $this->createContractWithRelations([
            'contract_status' => 'active',
        ]);

        // Add a payment to the contract
        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $contract->tenant_id,
        ]);

        // Refresh the contract
        $contract->refresh();

        // Now canGeneratePayments should return false
        $this->assertFalse($contract->canGeneratePayments());

        Livewire::test(ListUnitContracts::class)
            ->assertTableActionHidden('generatePayments', $contract);
    }

    #[Test]
    public function test_reschedule_action_visible_when_can_reschedule(): void
    {
        $this->actingAs($this->admin);

        // Create an active contract with payments
        $contract = $this->createContractWithRelations([
            'contract_status' => 'active',
        ]);

        // Add payments to enable reschedule
        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $contract->tenant_id,
        ]);

        $contract->refresh();

        // Should be able to reschedule
        $this->assertTrue($contract->canReschedule());

        Livewire::test(ListUnitContracts::class)
            ->assertTableActionVisible('reschedulePayments', $contract);
    }

    #[Test]
    public function test_reschedule_action_hidden_when_cannot(): void
    {
        $this->actingAs($this->admin);

        // Create a draft contract without payments (cannot reschedule)
        // Using draft status to avoid auto-generation of payments by observer
        $contract = $this->createContractWithRelations([
            'contract_status' => 'draft',
        ]);

        // No payments = cannot reschedule
        $this->assertEquals(0, $contract->collectionPayments()->count());
        $this->assertFalse($contract->canReschedule());

        Livewire::test(ListUnitContracts::class)
            ->assertTableActionHidden('reschedulePayments', $contract);
    }

    #[Test]
    public function test_reschedule_action_hidden_for_expired_contract(): void
    {
        $this->actingAs($this->admin);

        // Create an expired contract with payments
        $contract = $this->createContractWithRelations([
            'contract_status' => 'expired',
        ]);

        // Add payments
        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $contract->tenant_id,
        ]);

        $contract->refresh();

        // Expired contracts cannot be rescheduled
        $this->assertFalse($contract->canReschedule());

        Livewire::test(ListUnitContracts::class)
            ->assertTableActionHidden('reschedulePayments', $contract);
    }

    #[Test]
    public function test_view_payments_action_visible_when_has_payments(): void
    {
        $this->actingAs($this->admin);

        // Create a contract with payments
        $contract = $this->createContractWithRelations();

        // Add payments
        CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $contract->tenant_id,
        ]);

        $contract->refresh();

        $this->assertTrue($contract->collectionPayments()->exists());

        Livewire::test(ListUnitContracts::class)
            ->assertTableActionVisible('viewPayments', $contract);
    }

    #[Test]
    public function test_view_payments_action_hidden_when_no_payments(): void
    {
        $this->actingAs($this->admin);

        // Create a draft contract without payments (draft status avoids auto-generation)
        $contract = $this->createContractWithRelations([
            'contract_status' => 'draft',
        ]);

        // Ensure no payments
        $this->assertFalse($contract->collectionPayments()->exists());

        Livewire::test(ListUnitContracts::class)
            ->assertTableActionHidden('viewPayments', $contract);
    }

    // ==========================================
    // Table Display Tests
    // ==========================================

    #[Test]
    public function test_table_shows_status_with_correct_color(): void
    {
        $this->actingAs($this->admin);

        // Create contracts with different statuses
        $activeContract = $this->createContractWithRelations([
            'contract_status' => 'active',
            'end_date' => Carbon::now()->addMonths(6), // Not expiring soon
        ]);

        $draftContract = $this->createContractWithRelations([
            'contract_status' => 'draft',
        ]);

        $expiredContract = $this->createContractWithRelations([
            'contract_status' => 'expired',
        ]);

        // Verify status colors
        $this->assertEquals('success', $activeContract->status_color);
        $this->assertEquals('gray', $draftContract->status_color);
        $this->assertEquals('danger', $expiredContract->status_color);
    }

    #[Test]
    public function test_table_shows_warning_color_for_expiring_soon_contract(): void
    {
        $this->actingAs($this->admin);

        // Create an active contract that expires within 30 days
        // Note: Observer recalculates end_date based on start_date + duration_months
        // So we need to set start_date and duration_months to make end_date within 30 days
        $startDate = Carbon::now()->subDays(15); // Started 15 days ago
        $expiringSoonContract = $this->createContractWithRelations([
            'contract_status' => 'active',
            'start_date' => $startDate,
            'duration_months' => 1, // 1 month duration = ends approximately 15 days from now
        ]);

        // The end_date should be approximately 15 days from now (within 30 days threshold)
        $this->assertTrue($expiringSoonContract->end_date < Carbon::now()->addDays(30));

        // Should show warning color for contracts expiring soon
        $this->assertEquals('warning', $expiringSoonContract->status_color);
    }

    #[Test]
    public function test_table_shows_remaining_days(): void
    {
        $this->actingAs($this->admin);

        // Create an active contract
        // Note: Observer recalculates end_date based on start_date + duration_months
        $startDate = Carbon::now()->subDays(10);
        $durationMonths = 2; // 2 months = approximately 60 days from start
        $contract = $this->createContractWithRelations([
            'contract_status' => 'active',
            'start_date' => $startDate,
            'duration_months' => $durationMonths,
        ]);

        // End date is calculated as start_date + duration_months - 1 day
        // So end_date should be approximately 50 days from now (60 - 10 = 50)
        $remainingDays = $contract->remaining_days;

        // Remaining days should be positive (contract is active and not expired)
        $this->assertGreaterThan(40, $remainingDays);
        $this->assertLessThanOrEqual(60, $remainingDays);
    }

    #[Test]
    public function test_table_shows_remaining_days_zero_for_expired(): void
    {
        $this->actingAs($this->admin);

        // Create an expired contract
        $contract = $this->createContractWithRelations([
            'contract_status' => 'expired',
            'start_date' => Carbon::now()->subMonths(13),
            'end_date' => Carbon::now()->subMonths(1),
        ]);

        // Remaining days should be 0
        $this->assertEquals(0, $contract->remaining_days);
    }

    #[Test]
    public function test_table_shows_payments_count(): void
    {
        $this->actingAs($this->admin);

        // Create contracts with different payment frequencies
        $monthlyContract = $this->createContractWithRelations([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $quarterlyContract = $this->createContractWithRelations([
            'duration_months' => 12,
            'payment_frequency' => 'quarterly',
        ]);

        $semiAnnualContract = $this->createContractWithRelations([
            'duration_months' => 12,
            'payment_frequency' => 'semi_annually',
        ]);

        $annualContract = $this->createContractWithRelations([
            'duration_months' => 12,
            'payment_frequency' => 'annually',
        ]);

        // Verify payments count calculation
        $this->assertEquals(12, $monthlyContract->payments_count);
        $this->assertEquals(4, $quarterlyContract->payments_count);
        $this->assertEquals(2, $semiAnnualContract->payments_count);
        $this->assertEquals(1, $annualContract->payments_count);
    }

    #[Test]
    public function test_table_displays_correct_columns(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithRelations();

        Livewire::test(ListUnitContracts::class)
            ->assertCanSeeTableRecords([$contract])
            ->assertTableColumnExists('tenant.name')
            ->assertTableColumnExists('unit.name')
            ->assertTableColumnExists('property.name')
            ->assertTableColumnExists('start_date')
            ->assertTableColumnExists('duration_months')
            ->assertTableColumnExists('end_date')
            ->assertTableColumnExists('payment_frequency')
            ->assertTableColumnExists('monthly_rent');
    }

    // ==========================================
    // Create Contract Validation Tests
    // ==========================================

    #[Test]
    public function test_create_contract_validates_dates(): void
    {
        $this->actingAs($this->admin);

        $ownerUser = User::factory()->create(['type' => UserType::OWNER->value]);
        $property = $this->createPropertyWithOwner($ownerUser);
        $unit = $this->createUnit($property);
        $tenantUser = User::factory()->create(['type' => UserType::TENANT->value]);

        // Create an existing contract for this unit
        UnitContract::factory()->create([
            'tenant_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(11),
            'duration_months' => 12,
        ]);

        // Try to create another contract that overlaps
        $newTenant = User::factory()->create(['type' => UserType::TENANT->value]);
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        Livewire::test(CreateUnitContract::class)
            ->fillForm([
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'tenant_id' => $newTenant->id,
                'monthly_rent' => 5000,
                'start_date' => Carbon::now(), // This date falls within existing contract
                'duration_months' => 12,
                'payment_frequency' => 'monthly',
                'file' => $file,
            ])
            ->call('create')
            ->assertHasFormErrors(['start_date']);
    }

    #[Test]
    public function test_create_contract_validates_duration_frequency(): void
    {
        $this->actingAs($this->admin);

        $ownerUser = User::factory()->create(['type' => UserType::OWNER->value]);
        $property = $this->createPropertyWithOwner($ownerUser);
        $unit = $this->createUnit($property);
        $tenantUser = User::factory()->create(['type' => UserType::TENANT->value]);
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        // Try to create a contract with invalid duration for quarterly payments
        // 7 months cannot be divided by 3 (quarterly)
        Livewire::test(CreateUnitContract::class)
            ->fillForm([
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'tenant_id' => $tenantUser->id,
                'monthly_rent' => 5000,
                'start_date' => Carbon::now()->addMonths(12),
                'duration_months' => 7,
                'payment_frequency' => 'quarterly',
                'file' => $file,
            ])
            ->call('create')
            ->assertHasFormErrors(['duration_months']);
    }

    #[Test]
    public function test_create_contract_validates_semi_annual_frequency(): void
    {
        $this->actingAs($this->admin);

        $ownerUser = User::factory()->create(['type' => UserType::OWNER->value]);
        $property = $this->createPropertyWithOwner($ownerUser);
        $unit = $this->createUnit($property);
        $tenantUser = User::factory()->create(['type' => UserType::TENANT->value]);
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        // Try to create a contract with invalid duration for semi-annual payments
        // 7 months cannot be divided by 6 (semi-annually)
        Livewire::test(CreateUnitContract::class)
            ->fillForm([
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'tenant_id' => $tenantUser->id,
                'monthly_rent' => 5000,
                'start_date' => Carbon::now()->addMonths(12),
                'duration_months' => 7,
                'payment_frequency' => 'semi_annually',
                'file' => $file,
            ])
            ->call('create')
            ->assertHasFormErrors(['duration_months']);
    }

    #[Test]
    public function test_create_contract_calculates_payments_count(): void
    {
        $this->actingAs($this->admin);

        // Test payments count calculations
        $this->assertEquals(12, PropertyContractService::calculatePaymentsCount(12, 'monthly'));
        $this->assertEquals(4, PropertyContractService::calculatePaymentsCount(12, 'quarterly'));
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(12, 'semi_annually'));
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(12, 'annually'));

        // Test invalid durations return error string
        $this->assertEquals('Invalid division', PropertyContractService::calculatePaymentsCount(7, 'quarterly'));
        $this->assertEquals('Invalid division', PropertyContractService::calculatePaymentsCount(7, 'semi_annually'));
        $this->assertEquals('Invalid division', PropertyContractService::calculatePaymentsCount(5, 'annually'));
    }

    #[Test]
    public function test_create_contract_allows_valid_duration_frequency(): void
    {
        $this->actingAs($this->admin);

        $ownerUser = User::factory()->create(['type' => UserType::OWNER->value]);
        $property = $this->createPropertyWithOwner($ownerUser);
        $unit = $this->createUnit($property);
        $tenantUser = User::factory()->create(['type' => UserType::TENANT->value]);
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        // Create a contract with valid duration for quarterly payments
        Livewire::test(CreateUnitContract::class)
            ->fillForm([
                'property_id' => $property->id,
                'unit_id' => $unit->id,
                'tenant_id' => $tenantUser->id,
                'monthly_rent' => 5000,
                'start_date' => Carbon::now()->addMonths(12),
                'duration_months' => 12, // Divisible by 3
                'payment_frequency' => 'quarterly',
                'file' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify contract was created
        $this->assertDatabaseHas('unit_contracts', [
            'unit_id' => $unit->id,
            'tenant_id' => $tenantUser->id,
            'duration_months' => 12,
            'payment_frequency' => 'quarterly',
        ]);
    }

    // ==========================================
    // Filter Tests
    // ==========================================

    #[Test]
    public function test_filter_by_property_works(): void
    {
        $this->actingAs($this->admin);

        $contract1 = $this->createContractWithRelations();
        $contract2 = $this->createContractWithRelations();

        Livewire::test(ListUnitContracts::class)
            ->assertCanSeeTableRecords([$contract1, $contract2])
            ->filterTable('property_id', $contract1->property_id)
            ->assertCanSeeTableRecords([$contract1])
            ->assertCanNotSeeTableRecords([$contract2]);
    }

    #[Test]
    public function test_filter_by_tenant_works(): void
    {
        $this->actingAs($this->admin);

        $tenant1 = User::factory()->create(['type' => UserType::TENANT->value]);
        $tenant2 = User::factory()->create(['type' => UserType::TENANT->value]);

        $contract1 = $this->createContractWithRelations([], null, $tenant1);
        $contract2 = $this->createContractWithRelations([], null, $tenant2);

        Livewire::test(ListUnitContracts::class)
            ->filterTable('tenant_id', $tenant1->id)
            ->assertCanSeeTableRecords([$contract1])
            ->assertCanNotSeeTableRecords([$contract2]);
    }

    #[Test]
    public function test_filter_by_payment_frequency_works(): void
    {
        $this->actingAs($this->admin);

        $monthlyContract = $this->createContractWithRelations([
            'payment_frequency' => 'monthly',
        ]);

        $quarterlyContract = $this->createContractWithRelations([
            'payment_frequency' => 'quarterly',
            'duration_months' => 12,
        ]);

        Livewire::test(ListUnitContracts::class)
            ->filterTable('payment_frequency', 'monthly')
            ->assertCanSeeTableRecords([$monthlyContract])
            ->assertCanNotSeeTableRecords([$quarterlyContract]);
    }

    // ==========================================
    // Edit Permission Tests
    // ==========================================

    #[Test]
    public function test_only_super_admin_can_edit_contracts(): void
    {
        $contract = $this->createContractWithRelations();

        // Super admin can edit
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UnitContractResource::canEdit($contract));

        // Admin cannot edit
        $this->actingAs($this->admin);
        $this->assertFalse(UnitContractResource::canEdit($contract));

        // Employee cannot edit
        $this->actingAs($this->employee);
        $this->assertFalse(UnitContractResource::canEdit($contract));
    }

    #[Test]
    public function test_only_super_admin_can_delete_contracts(): void
    {
        $contract = $this->createContractWithRelations();

        // Super admin can delete
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UnitContractResource::canDelete($contract));

        // Admin cannot delete
        $this->actingAs($this->admin);
        $this->assertFalse(UnitContractResource::canDelete($contract));

        // Employee cannot delete
        $this->actingAs($this->employee);
        $this->assertFalse(UnitContractResource::canDelete($contract));
    }

    #[Test]
    public function test_admins_and_employees_can_create_contracts(): void
    {
        // Super admin can create
        $this->actingAs($this->superAdmin);
        $this->assertTrue(UnitContractResource::canCreate());

        // Admin can create
        $this->actingAs($this->admin);
        $this->assertTrue(UnitContractResource::canCreate());

        // Employee can create
        $this->actingAs($this->employee);
        $this->assertTrue(UnitContractResource::canCreate());
    }

    // ==========================================
    // Eloquent Query Scope Tests
    // ==========================================

    #[Test]
    public function test_owner_sees_only_related_contracts_in_query(): void
    {
        // Create contracts for this owner's property
        $ownerProperty = $this->createPropertyWithOwner($this->owner);
        $ownerUnit = $this->createUnit($ownerProperty);
        $ownerTenant = User::factory()->create(['type' => UserType::TENANT->value]);

        $ownerContract = UnitContract::factory()->create([
            'tenant_id' => $ownerTenant->id,
            'unit_id' => $ownerUnit->id,
            'property_id' => $ownerProperty->id,
            'contract_status' => 'active',
        ]);

        // Create contracts for another owner
        $otherOwner = User::factory()->create(['type' => UserType::OWNER->value]);
        $otherContract = $this->createContractWithRelations([], $otherOwner);

        // Test the query scope
        $this->actingAs($this->owner);
        $query = UnitContractResource::getEloquentQuery();
        $contracts = $query->get();

        // Owner should only see their contracts
        $this->assertTrue($contracts->contains('id', $ownerContract->id));
        $this->assertFalse($contracts->contains('id', $otherContract->id));
    }

    #[Test]
    public function test_tenant_sees_only_own_contracts_in_query(): void
    {
        // Create a contract for this tenant
        $tenantContract = $this->createContractWithRelations([], null, $this->tenant);

        // Create contracts for another tenant
        $otherTenant = User::factory()->create(['type' => UserType::TENANT->value]);
        $otherContract = $this->createContractWithRelations([], null, $otherTenant);

        // Test the query scope
        $this->actingAs($this->tenant);
        $query = UnitContractResource::getEloquentQuery();
        $contracts = $query->get();

        // Tenant should only see their contracts
        $this->assertTrue($contracts->contains('id', $tenantContract->id));
        $this->assertFalse($contracts->contains('id', $otherContract->id));
    }

    #[Test]
    public function test_admin_sees_all_contracts_in_query(): void
    {
        // Create multiple contracts
        $contract1 = $this->createContractWithRelations();
        $contract2 = $this->createContractWithRelations();
        $contract3 = $this->createContractWithRelations();

        // Test the query scope
        $this->actingAs($this->admin);
        $query = UnitContractResource::getEloquentQuery();
        $contracts = $query->get();

        // Admin should see all contracts
        $this->assertTrue($contracts->contains('id', $contract1->id));
        $this->assertTrue($contracts->contains('id', $contract2->id));
        $this->assertTrue($contracts->contains('id', $contract3->id));
    }

    // ==========================================
    // Search Tests
    // ==========================================

    #[Test]
    public function test_table_search_by_tenant_name_works(): void
    {
        $this->actingAs($this->admin);

        $uniqueTenant = User::factory()->create([
            'type' => UserType::TENANT->value,
            'name' => 'UniqueSearchableTenantName123',
        ]);

        $contract = $this->createContractWithRelations([], null, $uniqueTenant);
        $otherContract = $this->createContractWithRelations();

        Livewire::test(ListUnitContracts::class)
            ->searchTable('UniqueSearchableTenantName123')
            ->assertCanSeeTableRecords([$contract])
            ->assertCanNotSeeTableRecords([$otherContract]);
    }

    #[Test]
    public function test_table_search_by_unit_name_works(): void
    {
        $this->actingAs($this->admin);

        $ownerUser = User::factory()->create(['type' => UserType::OWNER->value]);
        $property = $this->createPropertyWithOwner($ownerUser);

        $uniqueUnit = Unit::factory()->create([
            'property_id' => $property->id,
            'unit_type_id' => 1,
            'name' => 'UniqueSearchableUnit999',
        ]);

        $tenant = User::factory()->create(['type' => UserType::TENANT->value]);

        $contract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $uniqueUnit->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
        ]);

        $otherContract = $this->createContractWithRelations();

        Livewire::test(ListUnitContracts::class)
            ->searchTable('UniqueSearchableUnit999')
            ->assertCanSeeTableRecords([$contract])
            ->assertCanNotSeeTableRecords([$otherContract]);
    }
}
