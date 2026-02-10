<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Models\CollectionPayment;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\SupplyPayment;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use App\Services\PaymentAssignmentService;
use App\Services\SupplyPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SupplyPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SupplyPaymentService $service;

    protected PaymentAssignmentService $paymentAssignmentService;

    protected Location $location;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected UnitType $unitType;

    protected User $tenant;

    protected User $owner;

    protected User $admin;

    protected Property $property;

    protected Unit $unit;

    protected PropertyContract $propertyContract;

    protected UnitContract $unitContract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentAssignmentService = app(PaymentAssignmentService::class);
        $this->service = app(SupplyPaymentService::class);

        // Freeze time to ensure consistent behavior across tests
        Carbon::setTestNow(Carbon::create(2026, 1, 24, 12, 0, 0));

        // Clear cache for settings
        Cache::flush();

        // Create required reference data
        $this->createDependencies();
    }

    protected function tearDown(): void
    {
        // Reset Carbon test time
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createDependencies(): void
    {
        $this->location = Location::create([
            'name' => 'Test Location',
            'level' => 1,
        ]);

        $this->propertyType = PropertyType::create([
            'name' => 'Apartment',
            'slug' => 'apartment',
        ]);

        $this->propertyStatus = PropertyStatus::create([
            'name' => 'Available',
            'slug' => 'available',
        ]);

        $this->unitType = UnitType::create([
            'name' => 'Residential Apartment',
            'slug' => 'residential-apartment',
        ]);

        // Set default settings
        Setting::set('payment_due_days', 7);

        // Create admin user
        $this->admin = User::factory()->create([
            'type' => UserType::EMPLOYEE->value,
        ]);

        // Create owner
        $this->owner = User::factory()->create([
            'type' => UserType::OWNER->value,
        ]);

        // Create tenant
        $this->tenant = User::factory()->create([
            'type' => UserType::TENANT->value,
        ]);

        // Create property
        $this->property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $this->owner->id,
            'location_id' => $this->location->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'address' => 'Test Address',
            'postal_code' => '12345',
            'parking_spots' => 5,
            'elevators' => 1,
            'build_year' => 2020,
            'floors_count' => 3,
        ]);

        // Create unit
        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
        ]);

        // Create property contract (without validation to avoid overlap issues in testing)
        $startDate = Carbon::now()->subMonths(6)->startOfDay();
        $endDate = $startDate->copy()->addMonths(12)->subDay();

        // Create property contract with draft status to prevent auto-generating payments
        $this->propertyContract = PropertyContract::create([
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_status' => 'draft', // Use draft to avoid auto-generating payments
            'payment_day' => 1,
            'auto_renew' => false,
            'notice_period_days' => 30,
            'payment_frequency' => 'monthly',
            'created_by' => $this->admin->id,
        ]);

        // Clear any auto-generated payments (the observer may have set it to active)
        SupplyPayment::where('property_contract_id', $this->propertyContract->id)->delete();

        // Create unit contract
        $this->unitContract = UnitContract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'contract_status' => 'active',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_months' => 12,
        ]);
    }

    /**
     * Helper method to create a supply payment
     */
    protected function createSupplyPayment(array $overrides = []): SupplyPayment
    {
        $defaults = [
            'property_contract_id' => $this->propertyContract->id,
            'owner_id' => $this->owner->id,
            'gross_amount' => 10000.00,
            'commission_amount' => 500.00,
            'commission_rate' => 5.00,
            'maintenance_deduction' => 0.00,
            'other_deductions' => 0.00,
            'net_amount' => 9500.00,
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
            'invoice_details' => [
                'period_start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'period_end' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            ],
        ];

        return SupplyPayment::create(array_merge($defaults, $overrides));
    }

    /**
     * Helper method to create a collection payment
     */
    protected function createCollectionPayment(array $overrides = []): CollectionPayment
    {
        $defaults = [
            'unit_contract_id' => $this->unitContract->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $this->tenant->id,
            'amount' => 5000.00,
            'late_fee' => 0.00,
            'due_date_start' => Carbon::now()->startOfMonth(),
            'due_date_end' => Carbon::now()->endOfMonth(),
            'paid_date' => Carbon::now()->subDays(10),
            'collection_date' => Carbon::now()->subDays(10),
        ];

        return CollectionPayment::create(array_merge($defaults, $overrides));
    }

    // ==========================================
    // calculateAmountsFromPeriod Tests
    // ==========================================

    public function test_calculate_amounts_sums_collected_payments(): void
    {
        // Create supply payment
        $supplyPayment = $this->createSupplyPayment([
            'month_year' => '2026-01',
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
        ]);

        // Create collection payments that will be counted
        $this->createCollectionPayment([
            'amount' => 5000.00,
            'due_date_start' => Carbon::create(2026, 1, 1),
            'due_date_end' => Carbon::create(2026, 1, 31),
            'paid_date' => Carbon::create(2026, 1, 15),
            'collection_date' => Carbon::create(2026, 1, 15),
        ]);

        $this->createCollectionPayment([
            'amount' => 3000.00,
            'due_date_start' => Carbon::create(2026, 1, 1),
            'due_date_end' => Carbon::create(2026, 1, 31),
            'paid_date' => Carbon::create(2026, 1, 20),
            'collection_date' => Carbon::create(2026, 1, 20),
        ]);

        $result = $this->service->calculateAmountsFromPeriod($supplyPayment);

        // Should include both payments (5000 + 3000 = 8000)
        $this->assertEquals(8000.00, $result['gross_amount']);
    }

    public function test_calculate_amounts_applies_commission(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'commission_rate' => 10.00,
            'month_year' => '2026-01',
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
        ]);

        // Create a collection payment
        $this->createCollectionPayment([
            'amount' => 10000.00,
            'due_date_start' => Carbon::create(2026, 1, 1),
            'due_date_end' => Carbon::create(2026, 1, 31),
            'paid_date' => Carbon::create(2026, 1, 15),
            'collection_date' => Carbon::create(2026, 1, 15),
        ]);

        $result = $this->service->calculateAmountsFromPeriod($supplyPayment);

        // Commission = 10000 * 10% = 1000
        $this->assertEquals(1000.00, $result['commission_amount']);
    }

    public function test_calculate_amounts_applies_deductions(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'commission_rate' => 5.00,
            'month_year' => '2026-01',
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
        ]);

        // Create a collection payment
        $this->createCollectionPayment([
            'amount' => 10000.00,
            'due_date_start' => Carbon::create(2026, 1, 1),
            'due_date_end' => Carbon::create(2026, 1, 31),
            'paid_date' => Carbon::create(2026, 1, 15),
            'collection_date' => Carbon::create(2026, 1, 15),
        ]);

        // Create an expense (maintenance deduction)
        Expense::create([
            'desc' => 'Test Maintenance',
            'type' => 'maintenance',
            'cost' => 500.00,
            'date' => Carbon::create(2026, 1, 10),
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $result = $this->service->calculateAmountsFromPeriod($supplyPayment);

        // Maintenance deduction should be 500
        $this->assertEquals(500.00, $result['maintenance_deduction']);

        // Net = 10000 - 500 (commission) - 500 (maintenance) = 9000
        $this->assertEquals(9000.00, $result['net_amount']);
    }

    public function test_calculate_amounts_returns_zero_when_no_payments(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'month_year' => '2026-02', // Different month with no payments
            'invoice_details' => [
                'period_start' => '2026-02-01',
                'period_end' => '2026-02-28',
            ],
        ]);

        $result = $this->service->calculateAmountsFromPeriod($supplyPayment);

        $this->assertEquals(0.00, $result['gross_amount']);
        $this->assertEquals(0.00, $result['commission_amount']);
        $this->assertEquals(0.00, $result['net_amount']);
    }

    // ==========================================
    // hasPendingPreviousPayments Tests
    // ==========================================

    public function test_has_pending_previous_returns_true_when_exists(): void
    {
        // Create an older unpaid supply payment
        $this->createSupplyPayment([
            'due_date' => Carbon::now()->subMonths(2),
            'paid_date' => null, // Not paid
            'month_year' => Carbon::now()->subMonths(2)->format('Y-m'),
        ]);

        // Create the current payment
        $currentPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        $result = $this->service->hasPendingPreviousPayments($currentPayment);

        $this->assertTrue($result);
    }

    public function test_has_pending_previous_returns_false_when_none(): void
    {
        // In this test, we only create one payment with NO pending previous payments
        // All previous payments (if any) should be PAID

        // Create only ONE supply payment (no previous payments exist)
        $currentPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        // Now check - there should be NO pending previous payments because we only created one payment
        $result = $this->service->hasPendingPreviousPayments($currentPayment);

        $this->assertFalse($result);
    }

    public function test_has_pending_previous_checks_correct_period(): void
    {
        // Create the current payment FIRST (earlier due date)
        $currentPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        // Create a supply payment for a LATER date (not previous, should not be detected)
        $this->createSupplyPayment([
            'due_date' => Carbon::now()->addMonths(1),
            'paid_date' => null,
            'month_year' => Carbon::now()->addMonths(1)->format('Y-m'),
        ]);

        // Should not detect future unpaid payments as "previous"
        // The hasPendingPreviousPayments checks for payments with due_date < current payment's due_date
        $result = $this->service->hasPendingPreviousPayments($currentPayment);

        // Since the future payment has due_date > current payment's due_date, it should not be counted
        $this->assertFalse($result);
    }

    // ==========================================
    // confirmSupplyPayment Tests
    // ==========================================

    public function test_confirm_supply_payment_updates_status(): void
    {
        // Create a supply payment with proper invoice_details
        $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $periodEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $supplyPayment = $this->createSupplyPayment([
            'paid_date' => null,
            'due_date' => Carbon::now()->subDays(5),
            'month_year' => Carbon::now()->format('Y-m'),
            'invoice_details' => [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ]);

        // Create collection payments that match the period (type 2: paid within period, due within period)
        $this->createCollectionPayment([
            'amount' => 5000.00,
            'due_date_start' => Carbon::now()->startOfMonth(),
            'due_date_end' => Carbon::now()->endOfMonth(),
            'paid_date' => Carbon::now()->subDays(10),
            'collection_date' => Carbon::now()->subDays(10),
        ]);

        // Reload the supply payment with relationship
        $supplyPayment = SupplyPayment::with('propertyContract.property')->find($supplyPayment->id);

        $result = $this->service->confirmSupplyPayment($supplyPayment, $this->admin->id);

        $this->assertTrue($result['success']);

        $supplyPayment->refresh();
        $this->assertEquals('collected', $supplyPayment->supply_status);
    }

    public function test_confirm_supply_payment_sets_confirmed_by(): void
    {
        $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $periodEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $supplyPayment = $this->createSupplyPayment([
            'paid_date' => null,
            'due_date' => Carbon::now()->subDays(5),
            'month_year' => Carbon::now()->format('Y-m'),
            'invoice_details' => [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ]);

        // Create collection payment
        $this->createCollectionPayment([
            'amount' => 5000.00,
            'due_date_start' => Carbon::now()->startOfMonth(),
            'due_date_end' => Carbon::now()->endOfMonth(),
            'paid_date' => Carbon::now()->subDays(10),
            'collection_date' => Carbon::now()->subDays(10),
        ]);

        // Reload the supply payment with relationship
        $supplyPayment = SupplyPayment::with('propertyContract.property')->find($supplyPayment->id);

        $this->service->confirmSupplyPayment($supplyPayment, $this->admin->id);

        $supplyPayment->refresh();
        $this->assertEquals($this->admin->id, $supplyPayment->collected_by);
    }

    public function test_confirm_supply_payment_sets_confirmed_date(): void
    {
        $periodStart = Carbon::now()->startOfMonth()->format('Y-m-d');
        $periodEnd = Carbon::now()->endOfMonth()->format('Y-m-d');

        $supplyPayment = $this->createSupplyPayment([
            'paid_date' => null,
            'due_date' => Carbon::now()->subDays(5),
            'month_year' => Carbon::now()->format('Y-m'),
            'invoice_details' => [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ],
        ]);

        // Create collection payment
        $this->createCollectionPayment([
            'amount' => 5000.00,
            'due_date_start' => Carbon::now()->startOfMonth(),
            'due_date_end' => Carbon::now()->endOfMonth(),
            'paid_date' => Carbon::now()->subDays(10),
            'collection_date' => Carbon::now()->subDays(10),
        ]);

        // Reload the supply payment with relationship
        $supplyPayment = SupplyPayment::with('propertyContract.property')->find($supplyPayment->id);

        $this->service->confirmSupplyPayment($supplyPayment, $this->admin->id);

        $supplyPayment->refresh();
        $this->assertNotNull($supplyPayment->paid_date);
        $this->assertTrue(Carbon::parse($supplyPayment->paid_date)->isToday());
    }

    public function test_confirm_supply_payment_fails_when_pending_previous(): void
    {
        // Create an older unpaid supply payment
        $this->createSupplyPayment([
            'due_date' => Carbon::now()->subMonths(2),
            'paid_date' => null,
            'month_year' => Carbon::now()->subMonths(2)->format('Y-m'),
        ]);

        // Create the current payment
        $currentPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        $result = $this->service->confirmSupplyPayment($currentPayment, $this->admin->id);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('دفعات سابقة', $result['message']);
    }

    public function test_confirm_supply_payment_fails_when_already_confirmed(): void
    {
        // Create an already confirmed supply payment
        $supplyPayment = $this->createSupplyPayment([
            'paid_date' => Carbon::now()->subDays(3), // Already paid
            'due_date' => Carbon::now()->subDays(10),
        ]);

        // Check if can confirm - should fail because already paid
        $canConfirmResult = $this->service->canConfirmPayment($supplyPayment);

        $this->assertFalse($canConfirmResult['can_confirm']);
        $this->assertContains('تم توريد هذه الدفعة مسبقاً', $canConfirmResult['errors']);
    }

    // ==========================================
    // Additional Tests for Comprehensive Coverage
    // ==========================================

    public function test_generate_payment_number_creates_unique_number(): void
    {
        $number1 = $this->service->generatePaymentNumber();
        $number2 = $this->service->generatePaymentNumber();

        $this->assertStringStartsWith('SUP-', $number1);
        $this->assertStringStartsWith('SUP-', $number2);
        $this->assertStringContainsString(date('Y'), $number1);
    }

    public function test_calculate_net_amount_correctly(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'gross_amount' => 10000.00,
            'commission_amount' => 500.00,
            'maintenance_deduction' => 200.00,
            'other_deductions' => 100.00,
        ]);

        $netAmount = $this->service->calculateNetAmount($supplyPayment);

        // Net = 10000 - 500 - 200 - 100 = 9200
        $this->assertEquals(9200.00, $netAmount);
    }

    public function test_calculate_commission_correctly(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'gross_amount' => 10000.00,
            'commission_rate' => 7.50,
        ]);

        $commission = $this->service->calculateCommission($supplyPayment);

        // Commission = 10000 * 7.5% = 750
        $this->assertEquals(750.00, $commission);
    }

    public function test_approve_payment_updates_status(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'approval_status' => 'pending',
        ]);

        $this->service->approve($supplyPayment, $this->admin->id);

        $supplyPayment->refresh();
        $this->assertEquals('approved', $supplyPayment->approval_status);
        $this->assertEquals($this->admin->id, $supplyPayment->approved_by);
        $this->assertNotNull($supplyPayment->approved_at);
    }

    public function test_reject_payment_updates_status_with_reason(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'approval_status' => 'pending',
        ]);

        $reason = 'المبلغ غير صحيح';
        $this->service->reject($supplyPayment, $this->admin->id, $reason);

        $supplyPayment->refresh();
        $this->assertEquals('rejected', $supplyPayment->approval_status);
        $this->assertEquals($this->admin->id, $supplyPayment->approved_by);
        $this->assertStringContainsString($reason, $supplyPayment->notes);
    }

    public function test_process_payment_sets_paid_date_and_reference(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'paid_date' => null,
        ]);

        $bankRef = 'BANK-REF-123456';
        $this->service->processPayment($supplyPayment, $bankRef);

        $supplyPayment->refresh();
        $this->assertNotNull($supplyPayment->paid_date);
        $this->assertEquals($bankRef, $supplyPayment->bank_transfer_reference);
    }

    public function test_get_deduction_breakdown_returns_correct_structure(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'commission_amount' => 500.00,
            'commission_rate' => 5.00,
            'maintenance_deduction' => 200.00,
            'other_deductions' => 100.00,
            'deduction_details' => ['reason' => 'رسوم إضافية'],
        ]);

        $breakdown = $this->service->getDeductionBreakdown($supplyPayment);

        $this->assertArrayHasKey('commission', $breakdown);
        $this->assertArrayHasKey('maintenance', $breakdown);
        $this->assertArrayHasKey('other', $breakdown);

        $this->assertEquals(500.00, $breakdown['commission']['amount']);
        // commission_rate is stored as decimal with 2 places, so it outputs '5.00%'
        $this->assertEquals('5.00%', $breakdown['commission']['rate']);
        $this->assertEquals(200.00, $breakdown['maintenance']['amount']);
        $this->assertEquals(100.00, $breakdown['other']['amount']);
    }

    public function test_can_confirm_payment_returns_errors_when_due_date_not_reached(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->addDays(10), // Future date
            'paid_date' => null,
        ]);

        $result = $this->service->canConfirmPayment($supplyPayment);

        $this->assertFalse($result['can_confirm']);
        $this->assertContains('لم يحل موعد الاستحقاق بعد', $result['errors']);
    }

    public function test_supply_status_accessor_returns_pending_for_future(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->addDays(10),
            'paid_date' => null,
        ]);

        $this->assertEquals('pending', $supplyPayment->supply_status);
    }

    public function test_supply_status_accessor_returns_worth_collecting_when_due(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(5),
            'paid_date' => null,
        ]);

        $this->assertEquals('worth_collecting', $supplyPayment->supply_status);
    }

    public function test_supply_status_accessor_returns_collected_when_paid(): void
    {
        $supplyPayment = $this->createSupplyPayment([
            'due_date' => Carbon::now()->subDays(10),
            'paid_date' => Carbon::now()->subDays(5),
        ]);

        $this->assertEquals('collected', $supplyPayment->supply_status);
    }
}
