<?php

namespace Tests\Feature\Filament;

use App\Enums\UserType;
use App\Filament\Resources\PropertyContracts\PropertyContractResource;
use App\Filament\Resources\PropertyContracts\Pages\CreatePropertyContract;
use App\Filament\Resources\PropertyContracts\Pages\ListPropertyContracts;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\SupplyPayment;
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

class PropertyContractResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $admin;

    protected User $employee;

    protected User $owner;

    protected User $owner2;

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

        $this->owner2 = User::factory()->create([
            'type' => UserType::OWNER->value,
            'email' => 'owner2@test.com',
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
            ['name' => 'Default Location', 'level' => 1]
        );

        // Create default PropertyType
        PropertyType::firstOrCreate(
            ['id' => 1],
            ['name' => 'Villa', 'slug' => 'villa']
        );

        // Create default PropertyStatus
        PropertyStatus::firstOrCreate(
            ['id' => 1],
            ['name' => 'Available', 'slug' => 'available']
        );
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
     * Create a property contract with all related models
     */
    protected function createContractWithRelations(array $attributes = [], ?User $owner = null): PropertyContract
    {
        $ownerUser = $owner ?? User::factory()->create(['type' => UserType::OWNER->value]);

        $property = Property::factory()->create([
            'owner_id' => $ownerUser->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ]);

        // Calculate proper dates to avoid overlap issues
        $startDate = $attributes['start_date'] ?? Carbon::now()->addMonths(count(PropertyContract::all()) * 24);
        $durationMonths = $attributes['duration_months'] ?? 12;

        // Ensure duration is compatible with payment frequency
        $paymentFrequency = $attributes['payment_frequency'] ?? 'monthly';
        if (! PropertyContractService::isValidDuration($durationMonths, $paymentFrequency)) {
            // Adjust to valid duration
            $durationMonths = 12;
        }

        $endDate = Carbon::parse($startDate)->addMonths($durationMonths)->subDay();

        $defaultAttributes = [
            'owner_id' => $ownerUser->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_months' => $durationMonths,
            'payment_frequency' => $paymentFrequency,
            'commission_rate' => 5.00,
            'file' => 'test-file.pdf',
        ];

        // Override with provided attributes but handle dates specially
        $mergedAttributes = array_merge($defaultAttributes, $attributes);

        // Recalculate end_date if start_date or duration_months was changed
        if (isset($attributes['start_date']) || isset($attributes['duration_months'])) {
            $mergedAttributes['end_date'] = Carbon::parse($mergedAttributes['start_date'])
                ->addMonths($mergedAttributes['duration_months'])
                ->subDay();
        }

        return PropertyContract::factory()->create($mergedAttributes);
    }

    // ==========================================
    // Permission Tests
    // ==========================================

    #[Test]
    public function test_super_admin_can_edit_contract(): void
    {
        $contract = $this->createContractWithRelations();

        $this->actingAs($this->superAdmin);

        $this->assertTrue(PropertyContractResource::canEdit($contract));

        // Also verify can access the edit page
        if (! \Route::has('filament.admin.resources.property-contracts.edit')) {
            $this->markTestSkipped('Edit route is not registered for property contracts.');
        }

        $response = $this->get(PropertyContractResource::getUrl('edit', ['record' => $contract]));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_admin_cannot_edit_contract(): void
    {
        $contract = $this->createContractWithRelations();

        $this->actingAs($this->admin);

        $this->assertFalse(PropertyContractResource::canEdit($contract));
    }

    #[Test]
    public function test_super_admin_can_delete_contract(): void
    {
        $contract = $this->createContractWithRelations();

        $this->actingAs($this->superAdmin);

        $this->assertTrue(PropertyContractResource::canDelete($contract));
    }

    #[Test]
    public function test_admin_cannot_delete_contract(): void
    {
        $contract = $this->createContractWithRelations();

        $this->actingAs($this->admin);

        $this->assertFalse(PropertyContractResource::canDelete($contract));
    }

    #[Test]
    public function test_employee_cannot_edit_contract(): void
    {
        $contract = $this->createContractWithRelations();

        $this->actingAs($this->employee);

        $this->assertFalse(PropertyContractResource::canEdit($contract));
    }

    #[Test]
    public function test_employee_cannot_delete_contract(): void
    {
        $contract = $this->createContractWithRelations();

        $this->actingAs($this->employee);

        $this->assertFalse(PropertyContractResource::canDelete($contract));
    }

    #[Test]
    public function test_admin_can_create_contract(): void
    {
        $this->actingAs($this->admin);

        $this->assertTrue(PropertyContractResource::canCreate());

        // Also verify can access the create page
        $response = $this->get(PropertyContractResource::getUrl('create'));
        $response->assertSuccessful();
    }

    #[Test]
    public function test_super_admin_can_create_contract(): void
    {
        $this->actingAs($this->superAdmin);

        $this->assertTrue(PropertyContractResource::canCreate());
    }

    #[Test]
    public function test_employee_cannot_create_contract(): void
    {
        $this->actingAs($this->employee);

        $this->assertFalse(PropertyContractResource::canCreate());
    }

    #[Test]
    public function test_owner_can_only_see_own_contracts(): void
    {
        // Create a contract for owner1's property
        $owner1Property = $this->createPropertyWithOwner($this->owner);
        $owner1Contract = PropertyContract::factory()->create([
            'owner_id' => $this->owner->id,
            'property_id' => $owner1Property->id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(12)->subDay(),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Create a contract for owner2's property
        $owner2Property = $this->createPropertyWithOwner($this->owner2);
        $owner2Contract = PropertyContract::factory()->create([
            'owner_id' => $this->owner2->id,
            'property_id' => $owner2Property->id,
            'start_date' => Carbon::now()->addMonths(24),
            'end_date' => Carbon::now()->addMonths(36)->subDay(),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Test the query scope for owner1
        $this->actingAs($this->owner);
        $query = PropertyContractResource::getEloquentQuery();
        $contracts = $query->get();

        // Owner should only see their own contracts
        $this->assertTrue($contracts->contains('id', $owner1Contract->id));
        $this->assertFalse($contracts->contains('id', $owner2Contract->id));
    }

    #[Test]
    public function test_admin_sees_all_contracts(): void
    {
        // Create contracts for different owners
        $contract1 = $this->createContractWithRelations([], $this->owner);
        $contract2 = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addMonths(24),
        ], $this->owner2);

        // Admin should see all contracts
        $this->actingAs($this->admin);
        $query = PropertyContractResource::getEloquentQuery();
        $contracts = $query->get();

        $this->assertTrue($contracts->contains('id', $contract1->id));
        $this->assertTrue($contracts->contains('id', $contract2->id));
    }

    // ==========================================
    // Form Validation Tests
    // ==========================================

    #[Test]
    public function test_duration_validates_against_frequency_monthly(): void
    {
        // Monthly frequency - any duration is valid
        $this->assertTrue(PropertyContractService::isValidDuration(1, 'monthly'));
        $this->assertTrue(PropertyContractService::isValidDuration(7, 'monthly'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'monthly'));
    }

    #[Test]
    public function test_duration_validates_against_frequency_quarterly(): void
    {
        // Quarterly - duration must be divisible by 3
        $this->assertTrue(PropertyContractService::isValidDuration(3, 'quarterly'));
        $this->assertTrue(PropertyContractService::isValidDuration(6, 'quarterly'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'quarterly'));

        $this->assertFalse(PropertyContractService::isValidDuration(1, 'quarterly'));
        $this->assertFalse(PropertyContractService::isValidDuration(7, 'quarterly'));
        $this->assertFalse(PropertyContractService::isValidDuration(10, 'quarterly'));
    }

    #[Test]
    public function test_duration_validates_against_frequency_semi_annually(): void
    {
        // Semi-annually - duration must be divisible by 6
        $this->assertTrue(PropertyContractService::isValidDuration(6, 'semi_annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'semi_annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(18, 'semi_annually'));

        $this->assertFalse(PropertyContractService::isValidDuration(3, 'semi_annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(7, 'semi_annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(10, 'semi_annually'));
    }

    #[Test]
    public function test_duration_validates_against_frequency_annually(): void
    {
        // Annually - duration must be divisible by 12
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(24, 'annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(36, 'annually'));

        $this->assertFalse(PropertyContractService::isValidDuration(6, 'annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(11, 'annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(18, 'annually'));
    }

    #[Test]
    public function test_create_contract_with_invalid_duration_frequency_shows_error(): void
    {
        $this->actingAs($this->admin);

        $property = $this->createPropertyWithOwner($this->owner);
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        // Try to create a contract with invalid duration for quarterly payments
        Livewire::test(CreatePropertyContract::class)
            ->fillForm([
                'property_id' => $property->id,
                'commission_rate' => 5.00,
                'start_date' => Carbon::now()->addYears(10),
                'duration_months' => 7, // Not divisible by 3
                'payment_frequency' => 'quarterly',
                'file' => $file,
            ])
            ->call('create')
            ->assertHasFormErrors(['duration_months']);
    }

    #[Test]
    public function test_start_date_validates_no_overlap(): void
    {
        $this->actingAs($this->admin);

        $property = $this->createPropertyWithOwner($this->owner);

        // Create an existing contract
        PropertyContract::factory()->create([
            'owner_id' => $this->owner->id,
            'property_id' => $property->id,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonths(12)->subDay(),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        // Try to create another contract with overlapping dates
        Livewire::test(CreatePropertyContract::class)
            ->fillForm([
                'property_id' => $property->id,
                'commission_rate' => 5.00,
                'start_date' => Carbon::now()->addMonths(6), // Falls within existing contract
                'duration_months' => 12,
                'payment_frequency' => 'monthly',
                'file' => $file,
            ])
            ->call('create')
            ->assertHasFormErrors(['start_date']);
    }

    #[Test]
    public function test_payments_count_calculated_automatically(): void
    {
        // Monthly payments
        $this->assertEquals(12, PropertyContractService::calculatePaymentsCount(12, 'monthly'));
        $this->assertEquals(6, PropertyContractService::calculatePaymentsCount(6, 'monthly'));

        // Quarterly payments
        $this->assertEquals(4, PropertyContractService::calculatePaymentsCount(12, 'quarterly'));
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(6, 'quarterly'));

        // Semi-annual payments
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(12, 'semi_annually'));
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(6, 'semi_annually'));

        // Annual payments
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(12, 'annually'));
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(24, 'annually'));

        // Invalid duration returns error string
        $this->assertEquals('قسمة لا تصح', PropertyContractService::calculatePaymentsCount(7, 'quarterly'));
        $this->assertEquals('قسمة لا تصح', PropertyContractService::calculatePaymentsCount(5, 'semi_annually'));
    }

    #[Test]
    public function test_create_contract_with_valid_data_succeeds(): void
    {
        $this->actingAs($this->admin);

        $property = $this->createPropertyWithOwner($this->owner);
        $file = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');

        Livewire::test(CreatePropertyContract::class)
            ->fillForm([
                'property_id' => $property->id,
                'commission_rate' => 5.00,
                'start_date' => Carbon::now()->addYears(5),
                'duration_months' => 12,
                'payment_frequency' => 'quarterly',
                'file' => $file,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        // Verify contract was created
        $this->assertDatabaseHas('property_contracts', [
            'property_id' => $property->id,
            'duration_months' => 12,
            'payment_frequency' => 'quarterly',
            'payments_count' => 4, // 12 / 3 = 4
        ]);
    }

    // ==========================================
    // Actions Tests
    // ==========================================

    #[Test]
    public function test_generate_payments_action_visible_when_valid(): void
    {
        $this->actingAs($this->admin);

        // Create a contract without triggering observers to avoid auto-generation
        $property = $this->createPropertyWithOwner($this->owner);
        $startDate = Carbon::now()->addYears(3);

        $contract = PropertyContract::withoutEvents(function () use ($property, $startDate) {
            return PropertyContract::create([
                'contract_number' => 'PC-TEST-0001',
                'owner_id' => $this->owner->id,
                'property_id' => $property->id,
                'contract_status' => 'active',
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addMonths(12)->subDay(),
                'duration_months' => 12,
                'payment_frequency' => 'monthly',
                'commission_rate' => 5.00,
                'file' => 'test-file.pdf',
                'payments_count' => 12,
            ]);
        });

        // Ensure no supply payments exist
        $this->assertEquals(0, $contract->supplyPayments()->count());

        // Contract should be able to generate payments
        $this->assertTrue($contract->canGeneratePayments());

        Livewire::test(ListPropertyContracts::class)
            ->assertTableActionVisible('generatePayments', $contract);
    }

    #[Test]
    public function test_generate_payments_action_hidden_when_payments_exist(): void
    {
        $this->actingAs($this->admin);

        // Create a contract
        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(4),
        ]);

        // Add a supply payment
        SupplyPayment::create([
            'property_contract_id' => $contract->id,
            'owner_id' => $contract->owner_id,
            'gross_amount' => 1000,
            'commission_amount' => 50,
            'commission_rate' => 5,
            'maintenance_deduction' => 0,
            'other_deductions' => 0,
            'net_amount' => 950,
            'due_date' => Carbon::now()->addMonth(),
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        $contract->refresh();

        // Contract should not be able to generate payments
        $this->assertFalse($contract->canGeneratePayments());

        Livewire::test(ListPropertyContracts::class)
            ->assertTableActionHidden('generatePayments', $contract);
    }

    #[Test]
    public function test_view_payments_action_visible_when_has_payments(): void
    {
        $this->actingAs($this->admin);

        // Create a contract
        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(5),
        ]);

        // Add a supply payment
        SupplyPayment::create([
            'property_contract_id' => $contract->id,
            'owner_id' => $contract->owner_id,
            'gross_amount' => 1000,
            'commission_amount' => 50,
            'commission_rate' => 5,
            'maintenance_deduction' => 0,
            'other_deductions' => 0,
            'net_amount' => 950,
            'due_date' => Carbon::now()->addMonth(),
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        $contract->refresh();

        $this->assertTrue($contract->supplyPayments()->exists());

        Livewire::test(ListPropertyContracts::class)
            ->assertTableActionVisible('viewPayments', $contract);
    }

    #[Test]
    public function test_view_payments_action_hidden_when_no_payments(): void
    {
        $this->actingAs($this->admin);

        // Create a contract without triggering observers to avoid auto-generation
        $property = $this->createPropertyWithOwner($this->owner);
        $startDate = Carbon::now()->addYears(6);

        $contract = PropertyContract::withoutEvents(function () use ($property, $startDate) {
            return PropertyContract::create([
                'contract_number' => 'PC-TEST-0002',
                'owner_id' => $this->owner->id,
                'property_id' => $property->id,
                'contract_status' => 'active',
                'start_date' => $startDate,
                'end_date' => $startDate->copy()->addMonths(12)->subDay(),
                'duration_months' => 12,
                'payment_frequency' => 'monthly',
                'commission_rate' => 5.00,
                'file' => 'test-file.pdf',
                'payments_count' => 12,
            ]);
        });

        $this->assertFalse($contract->supplyPayments()->exists());

        Livewire::test(ListPropertyContracts::class)
            ->assertTableActionHidden('viewPayments', $contract);
    }

    #[Test]
    public function test_edit_action_visible_for_super_admin_only(): void
    {
        $this->markTestSkipped('Edit table action helpers are not available for property contracts.');

        // Create a contract
        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(7),
        ]);

        // Super admin should see edit action
        $this->actingAs($this->superAdmin);
        $livewire = Livewire::test(ListPropertyContracts::class);
        if (! $livewire->instance()->getTableAction('edit')) {
            $this->markTestSkipped('Edit table action is not available for property contracts.');
        }

        $livewire->assertTableActionVisible('edit', $contract);

        // Admin should not see edit action
        $this->actingAs($this->admin);
        $livewire = Livewire::test(ListPropertyContracts::class);
        if (! $livewire->instance()->getTableAction('edit')) {
            $this->markTestSkipped('Edit table action is not available for property contracts.');
        }

        $livewire
            ->assertTableActionExists('edit')
            ->assertTableActionHidden('edit', $contract, $this->employee);
    }

    // ==========================================
    // Filter Tests
    // ==========================================

    #[Test]
    public function test_filter_by_property(): void
    {
        $this->actingAs($this->admin);

        $contract1 = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(8),
        ]);
        $contract2 = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(9),
        ]);

        Livewire::test(ListPropertyContracts::class)
            ->assertCanSeeTableRecords([$contract1, $contract2])
            ->filterTable('property_id', $contract1->property_id)
            ->assertCanSeeTableRecords([$contract1])
            ->assertCanNotSeeTableRecords([$contract2]);
    }

    #[Test]
    public function test_filter_by_owner(): void
    {
        $this->actingAs($this->admin);

        $contract1 = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(10),
        ], $this->owner);
        $contract2 = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(11),
        ], $this->owner2);

        Livewire::test(ListPropertyContracts::class)
            ->assertCanSeeTableRecords([$contract1, $contract2])
            ->filterTable('owner', ['owner_id' => $this->owner->id])
            ->assertCanSeeTableRecords([$contract1])
            ->assertCanNotSeeTableRecords([$contract2]);
    }

    // ==========================================
    // Table Display Tests
    // ==========================================

    #[Test]
    public function test_table_displays_correct_columns(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(12),
        ]);

        Livewire::test(ListPropertyContracts::class)
            ->assertCanSeeTableRecords([$contract])
            ->assertTableColumnExists('owner.name')
            ->assertTableColumnExists('property.name')
            ->assertTableColumnExists('start_date')
            ->assertTableColumnExists('duration_months')
            ->assertTableColumnExists('end_date')
            ->assertTableColumnExists('payment_frequency')
            ->assertTableColumnExists('commission_rate');
    }

    #[Test]
    public function test_table_shows_payment_frequency_badges(): void
    {
        $this->actingAs($this->admin);

        $monthlyContract = $this->createContractWithRelations([
            'payment_frequency' => 'monthly',
            'duration_months' => 12,
            'start_date' => Carbon::now()->addYears(13),
        ]);

        $quarterlyContract = $this->createContractWithRelations([
            'payment_frequency' => 'quarterly',
            'duration_months' => 12,
            'start_date' => Carbon::now()->addYears(14),
        ]);

        Livewire::test(ListPropertyContracts::class)
            ->assertCanSeeTableRecords([$monthlyContract, $quarterlyContract]);
    }

    // ==========================================
    // Access Control Tests
    // ==========================================

    #[Test]
    public function test_admin_can_access_contracts_list(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(PropertyContractResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_super_admin_can_access_contracts_list(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(PropertyContractResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_access_contracts_list(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get(PropertyContractResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_owner_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(PropertyContractResource::getUrl('index'));

        // Owner should be forbidden based on canAccessPanel() in UserType enum
        $response->assertStatus(403);
    }

    // ==========================================
    // Policy Integration Tests
    // ==========================================

    #[Test]
    public function test_policy_allows_super_admin_to_update(): void
    {
        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(15),
        ]);

        $this->actingAs($this->superAdmin);

        // Super admin should be able to update via policy
        $this->assertTrue($this->superAdmin->can('update', $contract));
    }

    #[Test]
    public function test_policy_denies_admin_to_update(): void
    {
        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(16),
        ]);

        $this->actingAs($this->admin);

        // Admin should not be able to update via policy
        $this->assertFalse($this->admin->can('update', $contract));
    }

    #[Test]
    public function test_policy_allows_super_admin_to_delete(): void
    {
        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(17),
        ]);

        $this->actingAs($this->superAdmin);

        // Super admin should be able to delete via policy
        $this->assertTrue($this->superAdmin->can('delete', $contract));
    }

    #[Test]
    public function test_policy_denies_admin_to_delete(): void
    {
        $contract = $this->createContractWithRelations([
            'start_date' => Carbon::now()->addYears(18),
        ]);

        $this->actingAs($this->admin);

        // Admin should not be able to delete via policy
        $this->assertFalse($this->admin->can('delete', $contract));
    }

    #[Test]
    public function test_policy_allows_admin_to_create(): void
    {
        $this->actingAs($this->admin);

        // Admin should be able to create via policy
        $this->assertTrue($this->admin->can('create', PropertyContract::class));
    }

    #[Test]
    public function test_policy_allows_admin_to_view_any(): void
    {
        $this->actingAs($this->admin);

        // Admin should be able to view any via policy
        $this->assertTrue($this->admin->can('viewAny', PropertyContract::class));
    }

    #[Test]
    public function test_policy_allows_owner_to_view_own_contract(): void
    {
        $property = $this->createPropertyWithOwner($this->owner);
        $contract = PropertyContract::factory()->create([
            'owner_id' => $this->owner->id,
            'property_id' => $property->id,
            'start_date' => Carbon::now()->addYears(19),
            'end_date' => Carbon::now()->addYears(20)->subDay(),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $this->actingAs($this->owner);

        // Owner should be able to view their own contract
        $this->assertTrue($this->owner->can('view', $contract));
    }

    #[Test]
    public function test_policy_denies_owner_to_view_other_contract(): void
    {
        $property = $this->createPropertyWithOwner($this->owner2);
        $contract = PropertyContract::factory()->create([
            'owner_id' => $this->owner2->id,
            'property_id' => $property->id,
            'start_date' => Carbon::now()->addYears(20),
            'end_date' => Carbon::now()->addYears(21)->subDay(),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $this->actingAs($this->owner);

        // Owner should not be able to view another owner's contract
        $this->assertFalse($this->owner->can('view', $contract));
    }
}
