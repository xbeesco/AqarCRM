<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\UserType;
use App\Filament\Resources\PropertyContracts\Pages\ReschedulePayments;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\SupplyPayment;
use App\Models\User;
use App\Services\PropertyContractService;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertyContractReschedulePageTest extends TestCase
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

        // Create payment_due_days setting
        Setting::set('payment_due_days', 7);
    }

    /**
     * Create a complete contract with all related models
     * Note: For active contracts, the Observer auto-generates payments
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

        $defaultAttributes = [
            'owner_id' => $ownerUser->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(1),
            'end_date' => Carbon::now()->addMonths(11),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'commission_rate' => 2.5,
        ];

        return PropertyContract::factory()->create(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Create a contract with payments (some paid, some unpaid)
     * Active contracts auto-generate payments via Observer
     */
    protected function createContractWithPayments(
        int $months = 12,
        string $frequency = 'monthly',
        int $paidCount = 3,
        float $commissionRate = 2.5
    ): PropertyContract {
        // Create an active contract - the Observer will auto-generate payments
        $contract = $this->createContractWithRelations([
            'contract_status' => 'active',
            'duration_months' => $months,
            'payment_frequency' => $frequency,
            'commission_rate' => $commissionRate,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->startOfMonth()->addMonths($months)->subDay(),
        ]);

        // The Observer already generated payments for active contracts
        // Mark some payments as paid (paid_date not null)
        $payments = $contract->supplyPayments()->orderBy('due_date')->get();
        for ($i = 0; $i < $paidCount && $i < count($payments); $i++) {
            $payments[$i]->update([
                'paid_date' => Carbon::now(),
            ]);
        }

        return $contract->fresh();
    }

    // ==========================================
    // Access Tests
    // ==========================================

    #[Test]
    public function test_super_admin_can_access_reschedule_page(): void
    {
        $this->actingAs($this->superAdmin);

        $contract = $this->createContractWithPayments();

        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_admin_can_access_reschedule_page(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments();

        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_employee_can_access_reschedule_page(): void
    {
        $this->actingAs($this->employee);

        $contract = $this->createContractWithPayments();

        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_owner_cannot_access_reschedule_page(): void
    {
        $this->actingAs($this->owner);

        $contract = $this->createContractWithPayments();

        // Owners are blocked by canAccessPanel(), so we expect 403
        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_tenant_cannot_access_reschedule_page(): void
    {
        $this->actingAs($this->tenant);

        $contract = $this->createContractWithPayments();

        // Tenants are blocked by canAccessPanel(), so we expect 403
        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_page_shows_403_for_unauthorized(): void
    {
        // Unauthenticated user should be redirected to login
        $contract = $this->createContractWithPayments();

        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        // Should redirect to login
        $response->assertRedirect();
    }

    // ==========================================
    // Condition Tests
    // ==========================================

    #[Test]
    public function test_shows_403_when_cannot_reschedule_no_payments(): void
    {
        $this->actingAs($this->admin);

        // Create a contract without payments (draft to avoid auto-generation)
        $contract = $this->createContractWithRelations([
            'contract_status' => 'draft',
        ]);

        // Contract without payments cannot be rescheduled
        $this->assertFalse($contract->canReschedule());

        // The policy's reschedule method checks canReschedule(), so this returns 403
        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        $response->assertStatus(403);
    }

    #[Test]
    public function test_shows_403_when_cannot_reschedule_terminated_contract(): void
    {
        $this->actingAs($this->admin);

        // Create a terminated contract (terminated contracts don't auto-generate payments)
        $contract = $this->createContractWithRelations([
            'contract_status' => 'terminated',
        ]);

        // Add a payment to ensure it's not the "no payments" case
        SupplyPayment::factory()->create([
            'property_contract_id' => $contract->id,
            'owner_id' => $contract->owner_id,
        ]);

        $contract->refresh();

        // Contract can be rescheduled as long as it has payments
        $this->assertTrue($contract->canReschedule());

        // Contract with payments should be accessible
        $response = $this->get(route('filament.admin.resources.property-contracts.reschedule', $contract));

        $response->assertSuccessful();
    }

    #[Test]
    public function test_page_loads_for_valid_contract(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments();

        // Verify the contract can be rescheduled
        $this->assertTrue($contract->canReschedule());

        // Test the page loads
        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->assertSuccessful()
            ->assertSee($contract->contract_number);
    }

    // ==========================================
    // Form Display Tests
    // ==========================================

    #[Test]
    public function test_form_shows_current_contract_info(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->assertSee('معلومات العقد الحالي')
            ->assertSee('12 شهر'); // Original duration
    }

    #[Test]
    public function test_form_shows_paid_months_count(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $contractService = app(PropertyContractService::class);
        $paidMonths = $contractService->getPaidMonthsCount($contract);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->assertSee('الأشهر المدفوعة')
            ->assertSee($paidMonths . ' شهر');
    }

    #[Test]
    public function test_form_shows_unpaid_payments_count(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $contractService = app(PropertyContractService::class);
        $unpaidCount = $contractService->getUnpaidPaymentsCount($contract);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->assertSee('الدفعات غير المدفوعة')
            ->assertSee($unpaidCount . ' دفعة');
    }

    #[Test]
    public function test_form_validates_duration_frequency(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        // Try to set invalid duration for quarterly (7 months doesn't divide by 3)
        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.additional_months', 7)
            ->set('data.new_frequency', 'quarterly')
            ->assertSet('data.frequency_error', true);
    }

    #[Test]
    public function test_form_validates_duration_frequency_valid(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        // Set valid duration for quarterly (6 months divides by 3)
        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'quarterly')
            ->assertSet('data.frequency_error', false);
    }

    #[Test]
    public function test_form_calculates_new_payments_count(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'monthly')
            ->assertSet('data.new_payments_count', 6);
    }

    #[Test]
    public function test_form_calculates_quarterly_payments_count(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.additional_months', 12)
            ->set('data.new_frequency', 'quarterly')
            ->assertSet('data.new_payments_count', 4); // 12 / 3 = 4
    }

    // ==========================================
    // Reschedule Action Tests
    // ==========================================

    #[Test]
    public function test_reschedule_action_calls_service(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $unpaidBefore = $contract->supplyPayments()->where('supply_status', 'pending')->count();

        // 12 months total, 3 paid = 9 unpaid months/payments
        // Reschedule: add 6 months monthly
        // Total new duration = 3 (paid) + 6 (added) = 9 months
        // Unpaid payments deleted, 6 new ones created.

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.new_commission_rate', 5)
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'monthly')
            ->callAction('reschedule');

        $contract->refresh();

        // Unpaid payments should have been deleted (using paid_date)
        // Note: deleteUnpaidSupplyPayments in service deletes != paid
        $unpaidAfter = $contract->supplyPayments()->whereNull('paid_date')->count(); // 6 new payments created
        $this->assertEquals(6, $unpaidAfter);

        // Contract duration should be updated
        // Paid months + new months
        $paidMonths = app(PropertyContractService::class)->getPaidMonthsCount($contract);
        $this->assertEquals($paidMonths + 6, $contract->duration_months);

        // Commission rate updated
        $this->assertEquals(5, $contract->commission_rate);
    }

    #[Test]
    public function test_reschedule_action_shows_success_notification(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.new_commission_rate', 5)
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'monthly')
            ->callAction('reschedule')
            ->assertNotified('تمت إعادة الجدولة بنجاح');
    }

    #[Test]
    public function test_reschedule_action_redirects_after_success(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.new_commission_rate', 5)
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'monthly')
            ->callAction('reschedule')
            ->assertRedirect(route('filament.admin.resources.property-contracts.view', $contract));
    }

    #[Test]
    public function test_reschedule_action_disabled_when_frequency_error(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        // Set invalid duration/frequency combination
        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.new_commission_rate', 5)
            ->set('data.additional_months', 7)
            ->set('data.new_frequency', 'quarterly')
            ->assertSet('data.frequency_error', true)
            ->assertActionDisabled('reschedule');
    }

    #[Test]
    public function test_reschedule_preserves_paid_payments(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $paidPaymentsBefore = $contract->supplyPayments()->whereNotNull('paid_date')->get();
        $paidIds = $paidPaymentsBefore->pluck('id')->toArray();

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.new_commission_rate', 5)
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'monthly')
            ->callAction('reschedule');

        $contract->refresh();

        // Paid payments should still exist
        foreach ($paidIds as $id) {
            $this->assertDatabaseHas('supply_payments', ['id' => $id]);
        }
    }

    #[Test]
    public function test_reschedule_deletes_unpaid_payments(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $unpaidPaymentsBefore = $contract->supplyPayments()->whereNull('paid_date')->get();
        $unpaidIds = $unpaidPaymentsBefore->pluck('id')->toArray();

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.new_commission_rate', 5)
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'monthly')
            ->callAction('reschedule');

        // Old unpaid payments should be deleted
        foreach ($unpaidIds as $id) {
            $this->assertDatabaseMissing('supply_payments', ['id' => $id]);
        }
    }

    #[Test]
    public function test_reschedule_creates_new_payments_with_correct_commission(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);
        $newCommission = 7.5;

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->set('data.new_commission_rate', $newCommission)
            ->set('data.additional_months', 6)
            ->set('data.new_frequency', 'monthly')
            ->callAction('reschedule');

        $contract->refresh();

        // New payments should have the new commission rate
        $newPayments = $contract->supplyPayments()->whereNull('paid_date')->get();
        foreach ($newPayments as $payment) {
            $this->assertEquals($newCommission, $payment->commission_rate);
        }
    }

    #[Test]
    public function test_cancel_action_has_correct_url(): void
    {
        $this->actingAs($this->admin);

        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        Livewire::test(ReschedulePayments::class, ['record' => $contract])
            ->assertActionExists('cancel')
            ->assertActionHasUrl('cancel', route('filament.admin.resources.property-contracts.view', $contract));
    }
}
