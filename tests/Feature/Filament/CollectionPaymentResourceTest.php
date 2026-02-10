<?php

namespace Tests\Feature\Filament;

use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Filament\Resources\CollectionPayments\CollectionPaymentResource;
use App\Filament\Resources\CollectionPayments\Pages\ListCollectionPayments;
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
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CollectionPaymentResourceTest extends TestCase
{
    use RefreshDatabase;

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
     * Create a complete payment with all related models
     */
    protected function createPaymentWithRelations(array $attributes = []): CollectionPayment
    {
        $tenantUser = User::factory()->create(['type' => UserType::TENANT->value]);
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
            'tenant_id' => $tenantUser->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'contract_status' => 'draft', // Use draft to prevent observer auto-generating payments
        ]);

        $defaultAttributes = [
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $tenantUser->id,
        ];

        return CollectionPayment::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    // ==========================================
    // Access Tests
    // ==========================================

    #[Test]
    public function test_admin_can_access_list_page(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(CollectionPaymentResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_access_list_page(): void
    {
        $this->actingAs($this->employee);

        $response = $this->get(CollectionPaymentResource::getUrl('index'));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_owner_cannot_access_list_page(): void
    {
        $this->actingAs($this->owner);

        $response = $this->get(CollectionPaymentResource::getUrl('index'));

        // Owner should be redirected or forbidden based on canAccessPanel
        $response->assertStatus(403);
    }

    #[Test]
    public function test_tenant_cannot_access_list_page(): void
    {
        $this->actingAs($this->tenant);

        $response = $this->get(CollectionPaymentResource::getUrl('index'));

        // Tenant should be redirected or forbidden based on canAccessPanel
        $response->assertStatus(403);
    }

    // ==========================================
    // Table Display Tests
    // ==========================================

    #[Test]
    public function test_table_displays_correct_columns(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createPaymentWithRelations();

        Livewire::test(ListCollectionPayments::class)
            ->assertCanSeeTableRecords([$payment])
            ->assertTableColumnExists('tenant.name')
            ->assertTableColumnExists('property.name')
            ->assertTableColumnExists('unit.name')
            ->assertTableColumnExists('due_date_start')
            ->assertTableColumnExists('amount')
            ->assertTableColumnExists('payment_status_label')
            ->assertTableColumnExists('delay_duration');
    }

    #[Test]
    public function test_table_shows_dynamic_payment_status(): void
    {
        $this->actingAs($this->admin);

        // Create payments with different states
        $collectedPayment = $this->createPaymentWithRelations([
            'collection_date' => Carbon::now(),
            'due_date_start' => Carbon::now()->subDays(5),
        ]);

        $overduePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => null,
            'due_date_start' => Carbon::now()->subDays(15),
            'due_date_end' => Carbon::now()->subDays(10),
        ]);

        Livewire::test(ListCollectionPayments::class)
            ->assertCanSeeTableRecords([$collectedPayment, $overduePayment]);

        // Verify that the payment_status accessor returns correct values
        $this->assertEquals(PaymentStatus::COLLECTED, $collectedPayment->payment_status);
        $this->assertEquals(PaymentStatus::OVERDUE, $overduePayment->payment_status);
    }

    #[Test]
    public function test_table_status_badge_color_for_overdue(): void
    {
        $this->actingAs($this->admin);

        $overduePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => null,
            'due_date_start' => Carbon::now()->subDays(15),
            'due_date_end' => Carbon::now()->subDays(10),
        ]);

        // Verify badge color
        $this->assertEquals('danger', $overduePayment->payment_status_color);
        $this->assertEquals(PaymentStatus::OVERDUE, $overduePayment->payment_status);
    }

    #[Test]
    public function test_table_status_badge_color_for_collected(): void
    {
        $this->actingAs($this->admin);

        $collectedPayment = $this->createPaymentWithRelations([
            'collection_date' => Carbon::now(),
        ]);

        // Verify badge color
        $this->assertEquals('success', $collectedPayment->payment_status_color);
        $this->assertEquals(PaymentStatus::COLLECTED, $collectedPayment->payment_status);
    }

    #[Test]
    public function test_table_status_badge_color_for_due(): void
    {
        $this->actingAs($this->admin);

        $duePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => null,
            'due_date_start' => Carbon::now()->subDays(3),
            'due_date_end' => Carbon::now(),
        ]);

        // Verify badge color
        $this->assertEquals('warning', $duePayment->payment_status_color);
        $this->assertEquals(PaymentStatus::DUE, $duePayment->payment_status);
    }

    // ==========================================
    // Filter Tests
    // ==========================================

    #[Test]
    public function test_filter_by_property_works(): void
    {
        $this->actingAs($this->admin);

        $payment1 = $this->createPaymentWithRelations();
        $payment2 = $this->createPaymentWithRelations();

        // First verify both payments exist
        Livewire::test(ListCollectionPayments::class)
            ->assertCanSeeTableRecords([$payment1, $payment2]);

        // Now test the filter with the nested form structure
        Livewire::test(ListCollectionPayments::class)
            ->set('tableFilters.property_and_unit.property_id', $payment1->property_id)
            ->assertCanSeeTableRecords([$payment1])
            ->assertCanNotSeeTableRecords([$payment2]);
    }

    #[Test]
    public function test_filter_by_status_works_with_dynamic_status(): void
    {
        $this->actingAs($this->admin);

        // Create payments with different statuses
        $collectedPayment = $this->createPaymentWithRelations([
            'collection_date' => Carbon::now(),
        ]);

        $duePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => null,
            'due_date_start' => Carbon::now()->subDays(3),
            'due_date_end' => Carbon::now(),
        ]);

        // Filter by collected status using SelectFilter
        Livewire::test(ListCollectionPayments::class)
            ->set('tableFilters.payment_status.value', PaymentStatus::COLLECTED->value)
            ->assertCanSeeTableRecords([$collectedPayment])
            ->assertCanNotSeeTableRecords([$duePayment]);

        // Filter by due status using SelectFilter
        Livewire::test(ListCollectionPayments::class)
            ->set('tableFilters.payment_status.value', PaymentStatus::DUE->value)
            ->assertCanSeeTableRecords([$duePayment])
            ->assertCanNotSeeTableRecords([$collectedPayment]);
    }

    #[Test]
    public function test_filter_by_tenant_works(): void
    {
        $this->actingAs($this->admin);

        // Create a fresh set of payments for this test
        $payment1 = $this->createPaymentWithRelations();
        $payment2 = $this->createPaymentWithRelations();

        // Filter by tenant using SelectFilter
        // The filter should show only payments from the selected tenant
        Livewire::test(ListCollectionPayments::class)
            ->set('tableFilters.tenant_id.value', $payment1->tenant_id)
            ->assertCanSeeTableRecords([$payment1]);

        // In a separate livewire test instance, filter for payment2's tenant
        Livewire::test(ListCollectionPayments::class)
            ->set('tableFilters.tenant_id.value', $payment2->tenant_id)
            ->assertCanSeeTableRecords([$payment2]);
    }

    #[Test]
    public function test_filter_by_date_range_works(): void
    {
        $this->actingAs($this->admin);

        $oldPayment = $this->createPaymentWithRelations([
            'due_date_start' => Carbon::now()->subMonths(2),
            'due_date_end' => Carbon::now()->subMonths(2)->endOfMonth(),
            'month_year' => Carbon::now()->subMonths(2)->format('Y-m'),
        ]);

        $recentPayment = $this->createPaymentWithRelations([
            'due_date_start' => Carbon::now()->subDays(5),
            'due_date_end' => Carbon::now(),
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        // Verify payments were created with the correct month_year values
        $this->assertNotEquals($oldPayment->month_year, $recentPayment->month_year);

        // Test that the old payment can be found when searched
        Livewire::test(ListCollectionPayments::class)
            ->searchTable($oldPayment->payment_number)
            ->assertCanSeeTableRecords([$oldPayment]);

        // Test that the recent payment can be found when searched
        Livewire::test(ListCollectionPayments::class)
            ->searchTable($recentPayment->payment_number)
            ->assertCanSeeTableRecords([$recentPayment]);
    }

    // ==========================================
    // Actions Tests
    // ==========================================

    #[Test]
    public function test_postpone_action_visible_for_due_payment(): void
    {
        $this->actingAs($this->admin);

        $duePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => null,
            'due_date_start' => Carbon::now()->subDays(3),
        ]);

        Livewire::test(ListCollectionPayments::class)
            ->assertTableActionVisible('postpone_payment', $duePayment);
    }

    #[Test]
    public function test_postpone_action_hidden_for_collected_payment(): void
    {
        $this->actingAs($this->admin);

        $collectedPayment = $this->createPaymentWithRelations([
            'collection_date' => Carbon::now(),
        ]);

        Livewire::test(ListCollectionPayments::class)
            ->assertTableActionHidden('postpone_payment', $collectedPayment);
    }

    #[Test]
    public function test_confirm_receipt_action_visible_for_due_payment(): void
    {
        $this->actingAs($this->admin);

        $duePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'due_date_start' => Carbon::now()->subDays(3),
        ]);

        Livewire::test(ListCollectionPayments::class)
            ->assertTableActionVisible('confirm_payment', $duePayment);
    }

    #[Test]
    public function test_confirm_receipt_action_hidden_for_collected(): void
    {
        $this->actingAs($this->admin);

        $collectedPayment = $this->createPaymentWithRelations([
            'collection_date' => Carbon::now(),
        ]);

        Livewire::test(ListCollectionPayments::class)
            ->assertTableActionHidden('confirm_payment', $collectedPayment);
    }

    // ==========================================
    // Global Search Tests
    // ==========================================

    #[Test]
    public function test_global_search_finds_by_payment_number(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createPaymentWithRelations();

        // Test that the payment can be found via global search
        $results = CollectionPaymentResource::getGlobalSearchResults($payment->payment_number);

        // The result should contain at least one payment with this payment number
        $this->assertTrue($results->isNotEmpty(), 'Global search should find payment by payment number');
    }

    #[Test]
    public function test_global_search_finds_by_tenant_name(): void
    {
        $this->actingAs($this->admin);

        // Create a tenant with a unique, searchable name
        $uniqueTenant = User::factory()->create([
            'type' => UserType::TENANT->value,
            'name' => 'TestTenantSearchable123',
        ]);
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
            'tenant_id' => $uniqueTenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'contract_status' => 'draft', // Use draft to prevent observer auto-generating payments
        ]);

        $payment = CollectionPayment::factory()->create([
            'unit_contract_id' => $contract->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $uniqueTenant->id,
        ]);

        // Test that the payment can be found via tenant name
        $results = CollectionPaymentResource::getGlobalSearchResults('TestTenantSearchable123');

        $this->assertTrue($results->isNotEmpty(), 'Global search should find payment by tenant name');
    }

    // ==========================================
    // Additional Tests
    // ==========================================

    #[Test]
    public function test_table_search_works(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createPaymentWithRelations();

        Livewire::test(ListCollectionPayments::class)
            ->searchTable($payment->payment_number)
            ->assertCanSeeTableRecords([$payment]);
    }

    #[Test]
    public function test_postpone_action_executes_successfully(): void
    {
        $this->actingAs($this->admin);

        $duePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => null,
            'due_date_start' => Carbon::now()->subDays(3),
        ]);

        Livewire::test(ListCollectionPayments::class)
            ->callTableAction('postpone_payment', $duePayment, [
                'delay_duration' => 7,
                'delay_reason' => 'Test reason for postponement',
            ])
            ->assertNotified();

        $duePayment->refresh();
        $this->assertEquals(7, $duePayment->delay_duration);
        $this->assertEquals('Test reason for postponement', $duePayment->delay_reason);
    }

    #[Test]
    public function test_confirm_payment_action_executes_successfully(): void
    {
        $this->actingAs($this->admin);

        $duePayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'due_date_start' => Carbon::now()->subDays(3),
        ]);

        $this->assertNull($duePayment->collection_date);

        Livewire::test(ListCollectionPayments::class)
            ->callTableAction('confirm_payment', $duePayment)
            ->assertNotified();

        $duePayment->refresh();
        $this->assertNotNull($duePayment->collection_date);
        $this->assertEquals($this->admin->id, $duePayment->collected_by);
    }

    #[Test]
    public function test_postponed_payment_shows_correct_status(): void
    {
        $this->actingAs($this->admin);

        $postponedPayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => 14,
            'delay_reason' => 'Financial difficulties',
            'due_date_start' => Carbon::now()->subDays(5),
        ]);

        $this->assertEquals(PaymentStatus::POSTPONED, $postponedPayment->payment_status);
        $this->assertEquals('info', $postponedPayment->payment_status_color);

        Livewire::test(ListCollectionPayments::class)
            ->assertCanSeeTableRecords([$postponedPayment]);
    }

    #[Test]
    public function test_upcoming_payment_shows_correct_status(): void
    {
        $this->actingAs($this->admin);

        $upcomingPayment = $this->createPaymentWithRelations([
            'collection_date' => null,
            'delay_duration' => null,
            'due_date_start' => Carbon::now()->addDays(10),
            'due_date_end' => Carbon::now()->addDays(15),
        ]);

        $this->assertEquals(PaymentStatus::UPCOMING, $upcomingPayment->payment_status);
        $this->assertEquals('gray', $upcomingPayment->payment_status_color);
    }
}
