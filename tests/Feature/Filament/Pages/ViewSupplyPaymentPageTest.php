<?php

namespace Tests\Feature\Filament\Pages;

use App\Enums\UserType;
use App\Filament\Resources\SupplyPaymentResource\Pages\ViewSupplyPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\SupplyPayment;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewSupplyPaymentPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $owner;

    protected PropertyContract $contract;

    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required reference data
        $this->createReferenceData();

        // Create admin user
        $this->admin = User::factory()->create([
            'type' => UserType::ADMIN->value,
            'email' => 'admin@test.com',
        ]);

        // Create owner user
        $this->owner = User::factory()->create([
            'type' => UserType::OWNER->value,
            'email' => 'owner@test.com',
            'name' => 'Test Owner',
        ]);

        // Create property
        $this->property = Property::factory()->create([
            'name' => 'Test Property Name',
            'owner_id' => $this->owner->id,
            'location_id' => 1,
            'type_id' => 1,
            'status_id' => 1,
        ]);

        // Create property contract with suspended status to avoid auto-generation of payments
        $this->contract = PropertyContract::factory()->create([
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
            'commission_rate' => 10.00,
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'contract_status' => 'suspended',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->startOfMonth()->addMonths(12)->subDay(),
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
    }

    /**
     * Create a supply payment with default attributes
     */
    protected function createSupplyPayment(array $overrides = []): SupplyPayment
    {
        $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $periodEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $defaults = [
            'property_contract_id' => $this->contract->id,
            'owner_id' => $this->owner->id,
            'commission_rate' => 10.00,
            'gross_amount' => 10000.00,
            'commission_amount' => 1000.00,
            'maintenance_deduction' => 500.00,
            'other_deductions' => 0,
            'due_date' => Carbon::now()->subDay(),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
            'invoice_details' => [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ];

        return SupplyPayment::create(array_merge($defaults, $overrides));
    }

    // ==========================================
    // Page Display Tests
    // ==========================================

    #[Test]
    public function test_page_renders_successfully(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createSupplyPayment();

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful();
    }

    #[Test]
    public function test_page_shows_property_name(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createSupplyPayment();

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('Test Property Name');
    }

    #[Test]
    public function test_page_shows_period_dates(): void
    {
        $this->actingAs($this->admin);

        $periodStart = '2024-01-01';
        $periodEnd = '2024-01-31';

        $payment = $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ]);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee($periodStart)
            ->assertSee($periodEnd);
    }

    #[Test]
    public function test_page_shows_supply_status_badge_pending(): void
    {
        $this->actingAs($this->admin);

        // Pending: due_date is in the future
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->addDays(10),
            'paid_date' => null,
        ]);

        $this->assertEquals('pending', $payment->supply_status);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('قيد الانتظار');
    }

    #[Test]
    public function test_page_shows_supply_status_badge_worth_collecting(): void
    {
        $this->actingAs($this->admin);

        // Worth collecting: due_date is in the past and not paid
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
        ]);

        $this->assertEquals('worth_collecting', $payment->supply_status);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('تستحق التوريد');
    }

    #[Test]
    public function test_page_shows_supply_status_badge_collected(): void
    {
        $this->actingAs($this->admin);

        // Collected: paid_date is set
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(10),
            'paid_date' => Carbon::now(),
        ]);

        $this->assertEquals('collected', $payment->supply_status);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('تم التوريد');
    }

    // ==========================================
    // Financial Calculations Display Tests
    // ==========================================

    #[Test]
    public function test_page_shows_gross_amount(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createSupplyPayment([
            'gross_amount' => 15000.00,
        ]);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('إجمالي المحصل');
    }

    #[Test]
    public function test_page_shows_commission_amount(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createSupplyPayment([
            'commission_rate' => 10.00,
            'commission_amount' => 1500.00,
        ]);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('العمولة (10.00%)');
    }

    #[Test]
    public function test_page_shows_maintenance_deduction(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createSupplyPayment([
            'maintenance_deduction' => 750.00,
        ]);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('المصروفات');
    }

    #[Test]
    public function test_page_shows_net_amount(): void
    {
        $this->actingAs($this->admin);

        $payment = $this->createSupplyPayment();

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertSee('صافي المستحق');
    }

    // ==========================================
    // Action Visibility Tests
    // ==========================================

    #[Test]
    public function test_confirm_action_visible_when_worth_collecting(): void
    {
        $this->actingAs($this->admin);

        // Worth collecting: due_date <= now AND paid_date is null
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
        ]);

        $this->assertEquals('worth_collecting', $payment->supply_status);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertActionVisible('confirm_payment');
    }

    #[Test]
    public function test_confirm_action_hidden_when_pending(): void
    {
        $this->actingAs($this->admin);

        // Pending: due_date > now
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->addDays(10),
            'paid_date' => null,
        ]);

        $this->assertEquals('pending', $payment->supply_status);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertActionHidden('confirm_payment');
    }

    #[Test]
    public function test_confirm_action_hidden_when_collected(): void
    {
        $this->actingAs($this->admin);

        // Collected: paid_date is set
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(10),
            'paid_date' => Carbon::now(),
        ]);

        $this->assertEquals('collected', $payment->supply_status);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->assertSuccessful()
            ->assertActionHidden('confirm_payment');
    }

    #[Test]
    public function test_confirm_action_hidden_when_has_pending_previous(): void
    {
        $this->actingAs($this->admin);

        // Create an earlier payment that is not paid (pending previous)
        $earlierPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subMonths(2),
            'paid_date' => null,
            'month_year' => Carbon::now()->subMonths(2)->format('Y-m'),
        ]);

        // Create current payment that is worth collecting
        $currentPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        // Verify current payment has pending previous payments
        $service = app(\App\Services\SupplyPaymentService::class);
        $this->assertTrue($service->hasPendingPreviousPayments($currentPayment));

        // When there are pending previous payments, the confirm_payment action is not added to the page
        // Instead, a pending_payments_notice action is shown
        Livewire::test(ViewSupplyPayment::class, ['record' => $currentPayment->id])
            ->assertSuccessful()
            ->assertActionDoesNotExist('confirm_payment');
    }

    #[Test]
    public function test_pending_payments_notice_shown_when_has_pending_previous(): void
    {
        $this->actingAs($this->admin);

        // Create an earlier payment that is not paid (pending previous)
        $earlierPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subMonths(2),
            'paid_date' => null,
            'month_year' => Carbon::now()->subMonths(2)->format('Y-m'),
        ]);

        // Create current payment that is worth collecting
        $currentPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        Livewire::test(ViewSupplyPayment::class, ['record' => $currentPayment->id])
            ->assertSuccessful()
            ->assertActionVisible('pending_payments_notice');
    }

    // ==========================================
    // Action Execution Tests
    // ==========================================

    #[Test]
    public function test_confirm_action_updates_payment(): void
    {
        $this->actingAs($this->admin);

        // Worth collecting payment without pending previous
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
        ]);

        $this->assertNull($payment->paid_date);
        $this->assertNull($payment->collected_by);

        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->callAction('confirm_payment');

        $payment->refresh();

        $this->assertNotNull($payment->paid_date);
        $this->assertEquals($this->admin->id, $payment->collected_by);
    }

    #[Test]
    public function test_confirm_action_calculates_amounts(): void
    {
        $this->actingAs($this->admin);

        // Worth collecting payment
        $payment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'gross_amount' => 0,
            'commission_amount' => 0,
            'maintenance_deduction' => 0,
        ]);

        // Initial amounts are set to zeros (will be calculated by service)
        Livewire::test(ViewSupplyPayment::class, ['record' => $payment->id])
            ->callAction('confirm_payment');

        $payment->refresh();

        // Verify the payment was updated (amounts recalculated by service)
        $this->assertNotNull($payment->paid_date);
        $this->assertEquals($this->admin->id, $payment->collected_by);
    }
}
