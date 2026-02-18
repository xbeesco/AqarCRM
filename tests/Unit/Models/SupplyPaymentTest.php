<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\SupplyPayment;
use App\Models\User;
use App\Services\SupplyPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected User $owner;

    protected Property $property;

    protected PropertyContract $propertyContract;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time to ensure consistent behavior across tests
        Carbon::setTestNow(Carbon::now());

        // Clear cache
        \Illuminate\Support\Facades\Cache::flush();

        // Use existing lookup data seeded by TestCase::seedLookupData()
        $this->location = Location::first();
        $this->propertyType = PropertyType::first();
        $this->propertyStatus = PropertyStatus::first();

        // Create owner, property, and contract
        $this->owner = $this->createOwner();
        $this->property = $this->createProperty();
        $this->propertyContract = $this->createPropertyContract();
    }

    protected function tearDown(): void
    {
        // Reset Carbon test time
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Helper method to create an owner user
     */
    protected function createOwner(): User
    {
        return User::factory()->create([
            'type' => UserType::OWNER->value,
        ]);
    }

    /**
     * Helper method to create an admin user
     */
    protected function createAdmin(): User
    {
        return User::factory()->create([
            'type' => UserType::ADMIN->value,
        ]);
    }

    /**
     * Helper method to create a property
     */
    protected function createProperty(): Property
    {
        return Property::create([
            'name' => 'Test Property '.uniqid(),
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
    }

    /**
     * Helper method to create a property contract
     */
    protected function createPropertyContract(): PropertyContract
    {
        $startDate = Carbon::now()->subMonths(3);
        $durationMonths = 12;

        return PropertyContract::create([
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'commission_rate' => 5.00,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths($durationMonths)->subDay(),
            'contract_status' => 'active',
            'payment_day' => 1,
            'auto_renew' => false,
            'notice_period_days' => 30,
            'payment_frequency' => 'monthly',
            'created_by' => 1,
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
            'maintenance_deduction' => 200.00,
            'other_deductions' => 100.00,
            'net_amount' => 9200.00,
            'due_date' => now()->addDays(5),
            'paid_date' => null,
            'month_year' => now()->format('Y-m'),
            'approval_status' => 'pending',
        ];

        return SupplyPayment::create(array_merge($defaults, $overrides));
    }

    // ==========================================
    // Dynamic Status - supply_status Accessor
    // ==========================================

    public function test_supply_status_returns_pending_when_due_date_not_reached(): void
    {
        // Payment with due date in the future and no paid_date
        $payment = $this->createSupplyPayment([
            'due_date' => now()->addDays(10),
            'paid_date' => null,
        ]);

        $this->assertEquals('pending', $payment->supply_status);
        $this->assertEquals('قيد الانتظار', $payment->supply_status_label);
        $this->assertEquals('warning', $payment->supply_status_color);
    }

    public function test_supply_status_returns_worth_collecting_when_due_date_passed(): void
    {
        // Payment with due date in the past and no paid_date
        $payment = $this->createSupplyPayment([
            'due_date' => now()->subDays(5),
            'paid_date' => null,
        ]);

        $this->assertEquals('worth_collecting', $payment->supply_status);
        $this->assertEquals('تستحق التوريد', $payment->supply_status_label);
        $this->assertEquals('info', $payment->supply_status_color);
    }

    public function test_supply_status_returns_worth_collecting_when_due_date_is_today(): void
    {
        // Payment with due date = today and no paid_date
        $payment = $this->createSupplyPayment([
            'due_date' => now()->startOfDay(),
            'paid_date' => null,
        ]);

        $this->assertEquals('worth_collecting', $payment->supply_status);
    }

    public function test_supply_status_returns_collected_when_paid_date_exists(): void
    {
        // Payment that has been paid
        $payment = $this->createSupplyPayment([
            'due_date' => now()->subDays(10),
            'paid_date' => now()->subDays(5),
        ]);

        $this->assertEquals('collected', $payment->supply_status);
        $this->assertEquals('تم التوريد', $payment->supply_status_label);
        $this->assertEquals('success', $payment->supply_status_color);
    }

    public function test_supply_status_collected_takes_priority_over_due_date(): void
    {
        // Even if due date is in future, if paid_date exists, it's collected
        $payment = $this->createSupplyPayment([
            'due_date' => now()->addDays(10),
            'paid_date' => now(),
        ]);

        $this->assertEquals('collected', $payment->supply_status);
    }

    // ==========================================
    // Scopes Tests
    // ==========================================

    public function test_pending_scope_filters_correctly(): void
    {
        // Create different payment statuses
        $pendingPayment = $this->createSupplyPayment([
            'due_date' => now()->addDays(10),
            'paid_date' => null,
        ]);

        $worthCollectingPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(5),
            'paid_date' => null,
        ]);

        $collectedPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(10),
            'paid_date' => now(),
        ]);

        $pendingPayments = SupplyPayment::pending()->get();

        $this->assertTrue($pendingPayments->contains('id', $pendingPayment->id));
        $this->assertFalse($pendingPayments->contains('id', $worthCollectingPayment->id));
        $this->assertFalse($pendingPayments->contains('id', $collectedPayment->id));
    }

    public function test_worth_collecting_scope_filters_correctly(): void
    {
        // Pending - due date in future
        $pendingPayment = $this->createSupplyPayment([
            'due_date' => now()->addDays(10),
            'paid_date' => null,
        ]);

        // Worth collecting - due date passed, not paid
        $worthCollectingPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(5),
            'paid_date' => null,
        ]);

        // Collected - has paid_date
        $collectedPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(10),
            'paid_date' => now(),
        ]);

        $worthCollectingPayments = SupplyPayment::worthCollecting()->get();

        $this->assertFalse($worthCollectingPayments->contains('id', $pendingPayment->id));
        $this->assertTrue($worthCollectingPayments->contains('id', $worthCollectingPayment->id));
        $this->assertFalse($worthCollectingPayments->contains('id', $collectedPayment->id));
    }

    public function test_collected_scope_filters_correctly(): void
    {
        $pendingPayment = $this->createSupplyPayment([
            'due_date' => now()->addDays(10),
            'paid_date' => null,
        ]);

        $worthCollectingPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(5),
            'paid_date' => null,
        ]);

        $collectedPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(10),
            'paid_date' => now(),
        ]);

        $collectedPayments = SupplyPayment::collected()->get();

        $this->assertFalse($collectedPayments->contains('id', $pendingPayment->id));
        $this->assertFalse($collectedPayments->contains('id', $worthCollectingPayment->id));
        $this->assertTrue($collectedPayments->contains('id', $collectedPayment->id));
    }

    public function test_by_owner_scope_filters_correctly(): void
    {
        $otherOwner = $this->createOwner();
        $otherProperty = Property::create([
            'name' => 'Other Property',
            'owner_id' => $otherOwner->id,
            'location_id' => $this->location->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'address' => 'Other Address',
            'postal_code' => '54321',
            'parking_spots' => 3,
            'elevators' => 1,
            'build_year' => 2020,
            'floors_count' => 2,
        ]);

        $otherContract = PropertyContract::create([
            'owner_id' => $otherOwner->id,
            'property_id' => $otherProperty->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addMonths(11),
            'contract_status' => 'active',
            'payment_day' => 1,
            'auto_renew' => false,
            'notice_period_days' => 30,
            'payment_frequency' => 'monthly',
            'created_by' => 1,
        ]);

        // Payment for main owner
        $ownerPayment = $this->createSupplyPayment();

        // Payment for other owner
        $otherOwnerPayment = SupplyPayment::create([
            'property_contract_id' => $otherContract->id,
            'owner_id' => $otherOwner->id,
            'gross_amount' => 5000.00,
            'commission_amount' => 250.00,
            'commission_rate' => 5.00,
            'maintenance_deduction' => 100.00,
            'other_deductions' => 50.00,
            'net_amount' => 4600.00,
            'due_date' => now()->addDays(5),
            'month_year' => now()->format('Y-m'),
            'approval_status' => 'pending',
        ]);

        $ownerPayments = SupplyPayment::byOwner($this->owner->id)->get();

        $this->assertTrue($ownerPayments->contains('id', $ownerPayment->id));
        $this->assertFalse($ownerPayments->contains('id', $otherOwnerPayment->id));
    }

    public function test_awaiting_approval_scope_filters_correctly(): void
    {
        $pendingApproval = $this->createSupplyPayment([
            'approval_status' => 'pending',
        ]);

        $approved = $this->createSupplyPayment([
            'approval_status' => 'approved',
        ]);

        $rejected = $this->createSupplyPayment([
            'approval_status' => 'rejected',
        ]);

        $awaitingApproval = SupplyPayment::awaitingApproval()->get();

        $this->assertTrue($awaitingApproval->contains('id', $pendingApproval->id));
        $this->assertFalse($awaitingApproval->contains('id', $approved->id));
        $this->assertFalse($awaitingApproval->contains('id', $rejected->id));
    }

    public function test_approved_scope_filters_correctly(): void
    {
        $pendingApproval = $this->createSupplyPayment([
            'approval_status' => 'pending',
        ]);

        $approved = $this->createSupplyPayment([
            'approval_status' => 'approved',
        ]);

        $approvedPayments = SupplyPayment::approved()->get();

        $this->assertFalse($approvedPayments->contains('id', $pendingApproval->id));
        $this->assertTrue($approvedPayments->contains('id', $approved->id));
    }

    public function test_by_month_scope_filters_correctly(): void
    {
        $januaryPayment = $this->createSupplyPayment([
            'month_year' => '2025-01',
        ]);

        $februaryPayment = $this->createSupplyPayment([
            'month_year' => '2025-02',
        ]);

        $januaryPayments = SupplyPayment::byMonth('2025-01')->get();

        $this->assertTrue($januaryPayments->contains('id', $januaryPayment->id));
        $this->assertFalse($januaryPayments->contains('id', $februaryPayment->id));
    }

    // ==========================================
    // Relationships Tests
    // ==========================================

    public function test_belongs_to_property_contract_relationship(): void
    {
        $payment = $this->createSupplyPayment();

        $this->assertInstanceOf(PropertyContract::class, $payment->propertyContract);
        $this->assertEquals($this->propertyContract->id, $payment->propertyContract->id);
    }

    public function test_belongs_to_owner_relationship(): void
    {
        $payment = $this->createSupplyPayment();

        $this->assertInstanceOf(User::class, $payment->owner);
        $this->assertEquals($this->owner->id, $payment->owner->id);
    }

    public function test_belongs_to_approver_relationship(): void
    {
        $admin = $this->createAdmin();
        $payment = $this->createSupplyPayment([
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $this->assertInstanceOf(User::class, $payment->approver);
        $this->assertEquals($admin->id, $payment->approver->id);
    }

    public function test_belongs_to_collected_by_relationship(): void
    {
        $admin = $this->createAdmin();
        $payment = $this->createSupplyPayment([
            'collected_by' => $admin->id,
            'paid_date' => now(),
        ]);

        $this->assertInstanceOf(User::class, $payment->collectedBy);
        $this->assertEquals($admin->id, $payment->collectedBy->id);
    }

    // ==========================================
    // Calculations Tests
    // ==========================================

    public function test_net_amount_calculation_on_create(): void
    {
        // Create a supply payment without specifying net_amount
        // The boot method should calculate it automatically
        $payment = SupplyPayment::create([
            'property_contract_id' => $this->propertyContract->id,
            'owner_id' => $this->owner->id,
            'gross_amount' => 10000.00,
            'commission_amount' => 500.00,
            'commission_rate' => 5.00,
            'maintenance_deduction' => 300.00,
            'other_deductions' => 200.00,
            'due_date' => now()->addDays(5),
            'month_year' => now()->format('Y-m'),
        ]);

        // Net amount = gross_amount - commission - maintenance - other
        // 10000 - 500 - 300 - 200 = 9000
        $expectedNetAmount = 10000.00 - 500.00 - 300.00 - 200.00;

        $this->assertEquals($expectedNetAmount, (float) $payment->net_amount);
    }

    public function test_net_amount_calculation_on_update(): void
    {
        $payment = $this->createSupplyPayment([
            'gross_amount' => 10000.00,
            'commission_amount' => 500.00,
            'maintenance_deduction' => 200.00,
            'other_deductions' => 100.00,
        ]);

        // Update deductions
        $payment->update([
            'maintenance_deduction' => 500.00,
            'other_deductions' => 300.00,
        ]);

        $payment->refresh();

        // New net amount = 10000 - 500 - 500 - 300 = 8700
        $expectedNetAmount = 10000.00 - 500.00 - 500.00 - 300.00;

        $this->assertEquals($expectedNetAmount, (float) $payment->net_amount);
    }

    public function test_commission_calculation_via_service(): void
    {
        $payment = $this->createSupplyPayment([
            'gross_amount' => 10000.00,
            'commission_rate' => 7.50,
        ]);

        $service = app(SupplyPaymentService::class);
        $commission = $service->calculateCommission($payment);

        // Commission = 10000 * 7.50 / 100 = 750
        $this->assertEquals(750.00, $commission);
    }

    public function test_net_amount_calculation_via_service(): void
    {
        $payment = $this->createSupplyPayment([
            'gross_amount' => 10000.00,
            'commission_amount' => 500.00,
            'maintenance_deduction' => 200.00,
            'other_deductions' => 100.00,
        ]);

        $service = app(SupplyPaymentService::class);
        $netAmount = $service->calculateNetAmount($payment);

        // Net = 10000 - 500 - 200 - 100 = 9200
        $this->assertEquals(9200.00, $netAmount);
    }

    public function test_deduction_breakdown_via_service(): void
    {
        $payment = $this->createSupplyPayment([
            'gross_amount' => 10000.00,
            'commission_amount' => 500.00,
            'commission_rate' => 5.00,
            'maintenance_deduction' => 300.00,
            'other_deductions' => 200.00,
            'deduction_details' => ['penalty' => 100, 'fees' => 100],
        ]);

        $service = app(SupplyPaymentService::class);
        $breakdown = $service->getDeductionBreakdown($payment);

        $this->assertArrayHasKey('commission', $breakdown);
        $this->assertArrayHasKey('maintenance', $breakdown);
        $this->assertArrayHasKey('other', $breakdown);

        $this->assertEquals(500.00, $breakdown['commission']['amount']);
        $this->assertEquals('5.00%', $breakdown['commission']['rate']);
        $this->assertEquals(300.00, $breakdown['maintenance']['amount']);
        $this->assertEquals(200.00, $breakdown['other']['amount']);
    }

    // ==========================================
    // Additional Accessors Tests
    // ==========================================

    public function test_requires_approval_attribute(): void
    {
        $pendingPayment = $this->createSupplyPayment([
            'approval_status' => 'pending',
        ]);

        $approvedPayment = $this->createSupplyPayment([
            'approval_status' => 'approved',
        ]);

        $this->assertTrue($pendingPayment->requires_approval);
        $this->assertFalse($approvedPayment->requires_approval);
    }

    public function test_is_collected_method(): void
    {
        $collectedPayment = $this->createSupplyPayment([
            'paid_date' => now(),
        ]);

        $uncollectedPayment = $this->createSupplyPayment([
            'paid_date' => null,
        ]);

        $this->assertTrue($collectedPayment->isCollected());
        $this->assertFalse($uncollectedPayment->isCollected());
    }

    public function test_is_worth_collecting_method(): void
    {
        $worthCollectingPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(5),
            'paid_date' => null,
        ]);

        $pendingPayment = $this->createSupplyPayment([
            'due_date' => now()->addDays(10),
            'paid_date' => null,
        ]);

        $collectedPayment = $this->createSupplyPayment([
            'due_date' => now()->subDays(5),
            'paid_date' => now(),
        ]);

        $this->assertTrue($worthCollectingPayment->isWorthCollecting());
        $this->assertFalse($pendingPayment->isWorthCollecting());
        $this->assertFalse($collectedPayment->isWorthCollecting());
    }

    // ==========================================
    // Payment Number Auto-Generation Tests
    // ==========================================

    public function test_payment_number_is_auto_generated(): void
    {
        $payment = $this->createSupplyPayment();

        $this->assertNotNull($payment->payment_number);
        $this->assertStringStartsWith('SUP-', $payment->payment_number);
        $this->assertStringContainsString(date('Y'), $payment->payment_number);
    }

    public function test_payment_number_is_unique(): void
    {
        $payment1 = $this->createSupplyPayment();
        $payment2 = $this->createSupplyPayment();

        $this->assertNotEquals($payment1->payment_number, $payment2->payment_number);
    }

    // ==========================================
    // Casts Tests
    // ==========================================

    public function test_due_date_is_cast_to_date(): void
    {
        $payment = $this->createSupplyPayment([
            'due_date' => '2025-06-15',
        ]);

        $this->assertInstanceOf(Carbon::class, $payment->due_date);
    }

    public function test_paid_date_is_cast_to_date(): void
    {
        $payment = $this->createSupplyPayment([
            'paid_date' => '2025-06-20',
        ]);

        $this->assertInstanceOf(Carbon::class, $payment->paid_date);
    }

    public function test_invoice_details_is_cast_to_array(): void
    {
        $invoiceDetails = [
            'period_start' => '2025-01-01',
            'period_end' => '2025-01-31',
            'items' => ['rent', 'utilities'],
        ];

        $payment = $this->createSupplyPayment([
            'invoice_details' => $invoiceDetails,
        ]);

        $this->assertIsArray($payment->invoice_details);
        $this->assertEquals($invoiceDetails, $payment->invoice_details);
    }

    public function test_deduction_details_is_cast_to_array(): void
    {
        $deductionDetails = [
            'maintenance_fee' => 200,
            'cleaning_fee' => 100,
        ];

        $payment = $this->createSupplyPayment([
            'deduction_details' => $deductionDetails,
        ]);

        $this->assertIsArray($payment->deduction_details);
        $this->assertEquals($deductionDetails, $payment->deduction_details);
    }

    public function test_decimal_fields_are_cast_correctly(): void
    {
        $payment = $this->createSupplyPayment([
            'gross_amount' => 10000.555,
            'commission_rate' => 5.25,
        ]);

        $this->assertIsString($payment->gross_amount);
        $this->assertIsString($payment->commission_rate);
    }
}
