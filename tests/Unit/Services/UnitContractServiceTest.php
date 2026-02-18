<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use App\Services\UnitContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitContractServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UnitContractService $service;

    protected User $owner;

    protected User $tenant;

    protected Property $property;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(UnitContractService::class);

        // Create required dependencies
        $this->createDependencies();
    }

    protected function createDependencies(): void
    {
        // Create location
        $location = Location::firstOrCreate(
            ['name' => 'Test Location'],
            ['level' => 1]
        );

        // Create property status
        $propertyStatus = PropertyStatus::firstOrCreate(
            ['slug' => 'available'],
            ['name' => 'Available']
        );

        // Create property type
        $propertyType = PropertyType::firstOrCreate(
            ['slug' => 'building'],
            ['name' => 'Building']
        );

        // Create unit type
        $unitType = UnitType::firstOrCreate(
            ['slug' => 'apartment'],
            ['name' => 'Apartment']
        );

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
            'status_id' => $propertyStatus->id,
            'type_id' => $propertyType->id,
            'location_id' => $location->id,
            'address' => 'Test Address',
        ]);

        // Create unit
        $this->unit = Unit::create([
            'property_id' => $this->property->id,
            'name' => 'Test Unit 101',
            'unit_type_id' => $unitType->id,
            'floor_number' => 1,
            'area_sqm' => 100,
            'rooms_count' => 2,
            'bathrooms_count' => 1,
            'rent_price' => 5000,
        ]);
    }

    protected function createContract(array $attributes = []): UnitContract
    {
        $startDate = $attributes['start_date'] ?? Carbon::now()->startOfMonth();
        $durationMonths = $attributes['duration_months'] ?? 12;

        $defaults = [
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 5000,
            'security_deposit' => 5000,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'contract_status' => 'active',
            'payment_frequency' => 'monthly',
            'payment_method' => 'bank_transfer',
            'grace_period_days' => 5,
            'late_fee_rate' => 5.00,
            'utilities_included' => false,
            'furnished' => false,
            'evacuation_notice_days' => 30,
            'created_by' => 1,
        ];

        // Only include end_date if explicitly provided
        if (isset($attributes['end_date'])) {
            $defaults['end_date'] = $attributes['end_date'];
        }

        return UnitContract::create(array_merge($defaults, $attributes));
    }

    protected function createCollectionPayment(UnitContract $contract, array $attributes = []): CollectionPayment
    {
        $defaults = [
            'unit_contract_id' => $contract->id,
            'unit_id' => $contract->unit_id,
            'property_id' => $contract->property_id,
            'tenant_id' => $contract->tenant_id,
            'amount' => $contract->monthly_rent,
            'late_fee' => 0,
            'total_amount' => $contract->monthly_rent,
            'due_date_start' => Carbon::now()->startOfMonth(),
            'due_date_end' => Carbon::now()->endOfMonth(),
            'month_year' => Carbon::now()->format('Y-m'),
        ];

        return CollectionPayment::create(array_merge($defaults, $attributes));
    }

    // ==========================================
    // canReschedule Tests
    // ==========================================

    public function test_can_reschedule_returns_true_for_active_contract_with_payments(): void
    {
        $contract = $this->createContract(['contract_status' => 'active']);
        $this->createCollectionPayment($contract);

        $result = $this->service->canReschedule($contract);

        $this->assertTrue($result);
    }

    public function test_can_reschedule_returns_false_for_inactive_contract(): void
    {
        $contract = $this->createContract(['contract_status' => 'expired']);
        $this->createCollectionPayment($contract);

        $result = $this->service->canReschedule($contract);

        $this->assertFalse($result);
    }

    public function test_can_reschedule_returns_false_when_no_payments(): void
    {
        // Use draft status to prevent Observer from auto-generating payments
        $contract = $this->createContract(['contract_status' => 'draft']);

        // Delete any auto-generated payments to ensure clean state
        $contract->collectionPayments()->delete();

        $result = $this->service->canReschedule($contract);

        $this->assertFalse($result);
    }

    public function test_can_reschedule_returns_false_for_terminated_contract(): void
    {
        $contract = $this->createContract(['contract_status' => 'terminated']);
        $this->createCollectionPayment($contract);

        $result = $this->service->canReschedule($contract);

        $this->assertFalse($result);
    }

    public function test_can_reschedule_returns_true_for_draft_contract_with_payments(): void
    {
        $contract = $this->createContract(['contract_status' => 'draft']);
        $this->createCollectionPayment($contract);

        $result = $this->service->canReschedule($contract);

        $this->assertTrue($result);
    }

    // ==========================================
    // getRemainingMonths Tests
    // ==========================================

    public function test_get_remaining_months_calculates_correctly(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Create 3 months of paid payments (each 30 days)
        for ($i = 0; $i < 3; $i++) {
            $startDate = Carbon::now()->startOfMonth()->addMonths($i);
            $endDate = $startDate->copy()->addDays(29); // 30 days total

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getRemainingMonths($contract);

        // 12 total - 3 paid = 9 remaining
        $this->assertEquals(9, $result);
    }

    public function test_get_remaining_months_returns_zero_when_all_paid(): void
    {
        $contract = $this->createContract([
            'duration_months' => 3,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Create all 3 months of paid payments
        for ($i = 0; $i < 3; $i++) {
            $startDate = Carbon::now()->startOfMonth()->addMonths($i);
            $endDate = $startDate->copy()->addDays(29); // 30 days total

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getRemainingMonths($contract);

        $this->assertEquals(0, $result);
    }

    public function test_get_remaining_months_handles_partial_payments(): void
    {
        $contract = $this->createContract([
            'duration_months' => 6,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Create only 2 paid payments (each 30 days)
        for ($i = 0; $i < 2; $i++) {
            $startDate = Carbon::now()->startOfMonth()->addMonths($i);
            $endDate = $startDate->copy()->addDays(29); // 30 days total

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getRemainingMonths($contract);

        // 6 total - 2 paid = 4 remaining
        $this->assertEquals(4, $result);
    }

    // ==========================================
    // getPaidMonthsCount Tests
    // ==========================================

    public function test_get_paid_months_count_returns_correct_count(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Create 4 paid payments (each 30 days)
        for ($i = 0; $i < 4; $i++) {
            $startDate = Carbon::now()->startOfMonth()->addMonths($i);
            $endDate = $startDate->copy()->addDays(29); // 30 days total

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getPaidMonthsCount($contract);

        $this->assertEquals(4, $result);
    }

    public function test_get_paid_months_count_returns_zero_when_no_payments(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        $result = $this->service->getPaidMonthsCount($contract);

        $this->assertEquals(0, $result);
    }

    public function test_get_paid_months_count_excludes_unpaid_payments(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Create 2 paid payments (each 30 days)
        for ($i = 0; $i < 2; $i++) {
            $startDate = Carbon::now()->startOfMonth()->addMonths($i);
            $endDate = $startDate->copy()->addDays(29); // 30 days total

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        // Create 3 unpaid payments
        for ($i = 2; $i < 5; $i++) {
            $startDate = Carbon::now()->startOfMonth()->addMonths($i);
            $endDate = $startDate->copy()->addDays(29);

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => null, // Unpaid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getPaidMonthsCount($contract);

        // Only 2 paid, 3 unpaid should not be counted
        $this->assertEquals(2, $result);
    }

    // ==========================================
    // getUnpaidPaymentsCount Tests
    // ==========================================

    public function test_get_unpaid_payments_count_returns_correct_count(): void
    {
        // Use draft status to prevent Observer from auto-generating payments
        $contract = $this->createContract([
            'contract_status' => 'draft',
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Delete any auto-generated payments to ensure clean state
        $contract->collectionPayments()->delete();

        // Create 3 unpaid payments
        for ($i = 0; $i < 3; $i++) {
            $startDate = Carbon::now()->addMonths($i)->startOfMonth();
            $endDate = Carbon::now()->addMonths($i)->endOfMonth();

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => null, // Unpaid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getUnpaidPaymentsCount($contract);

        $this->assertEquals(3, $result);
    }

    public function test_get_unpaid_payments_count_returns_zero_when_all_paid(): void
    {
        // Use draft status to prevent Observer from auto-generating payments
        $contract = $this->createContract([
            'contract_status' => 'draft',
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Delete any auto-generated payments to ensure clean state
        $contract->collectionPayments()->delete();

        // Create 3 paid payments
        for ($i = 0; $i < 3; $i++) {
            $startDate = Carbon::now()->addMonths($i)->startOfMonth();
            $endDate = Carbon::now()->addMonths($i)->endOfMonth();

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getUnpaidPaymentsCount($contract);

        $this->assertEquals(0, $result);
    }

    // ==========================================
    // getPaidPaymentsCount Tests
    // ==========================================

    public function test_get_paid_payments_count_returns_correct_count(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Create 5 paid payments
        for ($i = 0; $i < 5; $i++) {
            $startDate = Carbon::now()->addMonths($i)->startOfMonth();
            $endDate = Carbon::now()->addMonths($i)->endOfMonth();

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        // Create 2 unpaid payments
        for ($i = 5; $i < 7; $i++) {
            $startDate = Carbon::now()->addMonths($i)->startOfMonth();
            $endDate = Carbon::now()->addMonths($i)->endOfMonth();

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => null, // Unpaid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $result = $this->service->getPaidPaymentsCount($contract);

        $this->assertEquals(5, $result);
    }

    // ==========================================
    // canGeneratePayments Tests
    // ==========================================

    public function test_can_generate_payments_returns_true_when_valid(): void
    {
        // Use draft status to prevent Observer from auto-generating payments
        $contract = $this->createContract([
            'contract_status' => 'draft',
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Delete any auto-generated payments to ensure clean state
        $contract->collectionPayments()->delete();

        // Now change status to active for the test (without triggering observer)
        UnitContract::where('id', $contract->id)->update(['contract_status' => 'active']);
        $contract->refresh();

        $result = $this->service->canGeneratePayments($contract);

        $this->assertTrue($result);
    }

    public function test_can_generate_payments_returns_false_when_payments_exist(): void
    {
        $contract = $this->createContract([
            'contract_status' => 'active',
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Create an existing payment
        $this->createCollectionPayment($contract);

        $result = $this->service->canGeneratePayments($contract);

        $this->assertFalse($result);
    }

    public function test_can_generate_payments_returns_false_for_invalid_duration(): void
    {
        // Create a valid contract first then modify it
        $contract = $this->createContract([
            'contract_status' => 'active',
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Update the duration_months to 0 directly in DB
        UnitContract::where('id', $contract->id)->update(['duration_months' => 0]);
        $contract->refresh();

        $result = $this->service->canGeneratePayments($contract);

        $this->assertFalse($result);
    }

    public function test_can_generate_payments_returns_false_when_missing_required_fields(): void
    {
        // Create a valid contract first then modify it
        $contract = $this->createContract([
            'contract_status' => 'active',
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Update monthly_rent to 0 directly in DB
        UnitContract::where('id', $contract->id)->update(['monthly_rent' => 0]);
        $contract->refresh();

        $result = $this->service->canGeneratePayments($contract);

        $this->assertFalse($result);
    }

    // ==========================================
    // calculateTotalContractValue Tests
    // ==========================================

    public function test_calculate_total_contract_value(): void
    {
        $contract = $this->createContract([
            'monthly_rent' => 5000,
            'duration_months' => 12,
        ]);

        $result = $this->service->calculateTotalContractValue($contract);

        // 5000 * 12 = 60000
        $this->assertEquals(60000, $result);
    }

    public function test_calculate_total_contract_value_with_different_durations(): void
    {
        $contract = $this->createContract([
            'monthly_rent' => 3500,
            'duration_months' => 6,
        ]);

        $result = $this->service->calculateTotalContractValue($contract);

        // 3500 * 6 = 21000
        $this->assertEquals(21000, $result);
    }

    // ==========================================
    // calculateRemainingValue Tests
    // ==========================================

    public function test_calculate_remaining_value(): void
    {
        $contract = $this->createContract([
            'monthly_rent' => 5000,
            'duration_months' => 12,
            'start_date' => Carbon::now()->startOfMonth(),
        ]);

        // Create 3 paid payments (each 30 days)
        for ($i = 0; $i < 3; $i++) {
            $startDate = Carbon::now()->startOfMonth()->addMonths($i);
            $endDate = $startDate->copy()->addDays(29); // 30 days total

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        // Total value = 5000 * 12 = 60000
        // Remaining months = 12 - 3 = 9
        // Remaining value = 5000 * 9 = 45000
        $remainingMonths = $this->service->getRemainingMonths($contract);
        $remainingValue = $contract->monthly_rent * $remainingMonths;

        $this->assertEquals(9, $remainingMonths);
        $this->assertEquals(45000, $remainingValue);
    }

    // ==========================================
    // calculatePaymentsCount Tests
    // ==========================================

    public function test_calculate_payments_count_monthly(): void
    {
        $result = UnitContractService::calculatePaymentsCount(12, 'monthly');
        $this->assertEquals(12, $result);
    }

    public function test_calculate_payments_count_quarterly(): void
    {
        $result = UnitContractService::calculatePaymentsCount(12, 'quarterly');
        $this->assertEquals(4, $result); // 12 / 3 = 4
    }

    public function test_calculate_payments_count_semi_annual(): void
    {
        $result = UnitContractService::calculatePaymentsCount(12, 'semi_annual');
        $this->assertEquals(2, $result); // 12 / 6 = 2
    }

    public function test_calculate_payments_count_annual(): void
    {
        $result = UnitContractService::calculatePaymentsCount(12, 'annual');
        $this->assertEquals(1, $result); // 12 / 12 = 1
    }

    public function test_calculate_payments_count_handles_odd_months(): void
    {
        // 7 months quarterly should be ceil(7/3) = 3
        $result = UnitContractService::calculatePaymentsCount(7, 'quarterly');
        $this->assertEquals(3, $result);
    }

    // ==========================================
    // Additional Tests
    // ==========================================

    public function test_get_remaining_days_for_active_contract(): void
    {
        // Create contract with explicit dates
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now()->addDays(60);

        $contract = $this->createContract([
            'contract_status' => 'active',
            'start_date' => $startDate,
            'duration_months' => 3,
        ]);

        // Manually update end_date to our test value
        UnitContract::where('id', $contract->id)->update(['end_date' => $endDate]);
        $contract->refresh();

        $result = $this->service->getRemainingDays($contract);

        // Should be approximately 60 days
        $this->assertGreaterThanOrEqual(59, $result);
        $this->assertLessThanOrEqual(61, $result);
    }

    public function test_get_remaining_days_returns_zero_for_expired_contract(): void
    {
        $startDate = Carbon::now()->subMonths(12);
        $endDate = Carbon::now()->subDays(10);

        $contract = $this->createContract([
            'contract_status' => 'active',
            'start_date' => $startDate,
            'duration_months' => 12,
        ]);

        // Manually update end_date
        UnitContract::where('id', $contract->id)->update(['end_date' => $endDate]);
        $contract->refresh();

        $result = $this->service->getRemainingDays($contract);

        $this->assertEquals(0, $result);
    }

    public function test_can_renew_returns_true_for_active_contract(): void
    {
        $contract = $this->createContract(['contract_status' => 'active']);

        $result = $this->service->canRenew($contract);

        $this->assertTrue($result);
    }

    public function test_can_renew_returns_false_for_terminated_contract(): void
    {
        $contract = $this->createContract(['contract_status' => 'terminated']);

        $result = $this->service->canRenew($contract);

        $this->assertFalse($result);
    }

    public function test_can_terminate_early_returns_true_for_active_contract_with_future_end(): void
    {
        $contract = $this->createContract([
            'contract_status' => 'active',
            'duration_months' => 12,
        ]);

        // Ensure end_date is in the future
        UnitContract::where('id', $contract->id)->update([
            'end_date' => Carbon::now()->addMonths(6),
        ]);
        $contract->refresh();

        $result = $this->service->canTerminateEarly($contract);

        $this->assertTrue($result);
    }

    public function test_can_terminate_early_returns_false_for_expired_contract(): void
    {
        $contract = $this->createContract([
            'contract_status' => 'active',
            'duration_months' => 12,
        ]);

        // Set end_date in the past
        UnitContract::where('id', $contract->id)->update([
            'end_date' => Carbon::now()->subDays(5),
        ]);
        $contract->refresh();

        $result = $this->service->canTerminateEarly($contract);

        $this->assertFalse($result);
    }

    public function test_calculate_early_termination_penalty(): void
    {
        $startDate = Carbon::now()->subDays(30);

        $contract = $this->createContract([
            'contract_status' => 'active',
            'monthly_rent' => 5000,
            'start_date' => $startDate,
            'duration_months' => 12,
        ]);

        // Set end_date to 90 days from now (about 3 months remaining)
        UnitContract::where('id', $contract->id)->update([
            'end_date' => Carbon::now()->addDays(90),
        ]);
        $contract->refresh();

        $penalty = $this->service->calculateEarlyTerminationPenalty($contract);

        // Penalty should be 2 months rent (minimum of 2 or remaining months)
        $this->assertEquals(10000, $penalty); // 5000 * 2 = 10000
    }

    public function test_get_financial_summary(): void
    {
        // Use draft status to prevent Observer from auto-generating payments
        $contract = $this->createContract([
            'contract_status' => 'draft',
            'monthly_rent' => 5000,
            'duration_months' => 12,
            'security_deposit' => 5000,
        ]);

        // Delete any auto-generated payments to ensure clean state
        $contract->collectionPayments()->delete();

        // Create 3 paid payments
        for ($i = 0; $i < 3; $i++) {
            $startDate = Carbon::now()->addMonths($i)->startOfMonth();
            $endDate = Carbon::now()->addMonths($i)->endOfMonth();

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => Carbon::now(), // Paid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        // Create 2 unpaid payments
        for ($i = 3; $i < 5; $i++) {
            $startDate = Carbon::now()->addMonths($i)->startOfMonth();
            $endDate = Carbon::now()->addMonths($i)->endOfMonth();

            $this->createCollectionPayment($contract, [
                'due_date_start' => $startDate,
                'due_date_end' => $endDate,
                'collection_date' => null, // Unpaid
                'month_year' => $startDate->format('Y-m'),
            ]);
        }

        $summary = $this->service->getFinancialSummary($contract);

        $this->assertEquals(60000, $summary['total_contract_value']); // 5000 * 12
        $this->assertEquals(5, $summary['total_payments']);
        $this->assertEquals(3, $summary['paid_payments_count']);
        $this->assertEquals(2, $summary['unpaid_payments_count']);
        $this->assertEquals(15000, $summary['total_paid_amount']); // 5000 * 3
        $this->assertEquals(10000, $summary['total_pending_amount']); // 5000 * 2
        $this->assertEquals(5000, $summary['security_deposit']);
        $this->assertEquals(60, $summary['collection_rate']); // 3/5 * 100 = 60%
    }
}
