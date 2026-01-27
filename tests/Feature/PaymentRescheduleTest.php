<?php

namespace Tests\Feature;

use App\Models\CollectionPayment;
use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use App\Services\PaymentGeneratorService;
use App\Services\UnitContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentRescheduleTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentGeneratorService $service;

    protected UnitContractService $contractService;

    protected User $superAdmin;

    protected Property $property;

    protected Unit $unit;

    protected User $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Create super_admin user
        $this->superAdmin = User::factory()->create(['type' => 'super_admin']);
        $this->actingAs($this->superAdmin);

        // Initialize services
        $this->service = app(PaymentGeneratorService::class);
        $this->contractService = app(UnitContractService::class);

        // Create required reference data
        $this->createReferenceData();

        // Create base data
        $owner = User::factory()->create(['type' => 'owner']);
        $this->property = Property::factory()->create(['owner_id' => $owner->id]);
        $this->unit = Unit::factory()->create(['property_id' => $this->property->id]);
        $this->tenant = User::factory()->create(['type' => 'tenant']);
    }

    /**
     * Create required reference data (lookup tables) for tests.
     */
    private function createReferenceData(): void
    {
        // Create PropertyStatus if not exists
        if (! PropertyStatus::where('id', 1)->exists()) {
            PropertyStatus::create([
                'id' => 1,
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'slug' => 'available',
                'is_active' => true,
                'is_available' => true,
            ]);
        }

        // Create PropertyType if not exists
        if (! PropertyType::where('id', 1)->exists()) {
            PropertyType::create([
                'id' => 1,
                'name_ar' => 'فيلا',
                'name_en' => 'Villa',
                'slug' => 'villa',
                'is_active' => true,
            ]);
        }

        // Create Location if not exists
        if (! Location::where('id', 1)->exists()) {
            Location::create([
                'id' => 1,
                'name' => 'الرياض',
                'code' => 'RYD',
                'level' => 1,
                'is_active' => true,
            ]);
        }

        // Create UnitType if not exists
        if (! UnitType::where('id', 1)->exists()) {
            UnitType::create([
                'id' => 1,
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'slug' => 'apartment',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Helper: Create a contract with payments.
     */
    private function createContractWithPayments(
        int $months,
        string $frequency,
        int $paidCount = 0,
        float $monthlyRent = 1000
    ): UnitContract {
        // Use unique identifier to avoid duplicate contract numbers
        static $contractCounter = 0;
        $contractCounter++;
        $uniqueId = uniqid().$contractCounter;

        // Note: The Observer automatically generates payments when creating an active contract
        // So we don't need to call generateTenantPayments manually
        $contract = UnitContract::create([
            'contract_number' => 'UC-TEST-'.$uniqueId,
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'monthly_rent' => $monthlyRent,
            'duration_months' => $months,
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->startOfMonth()->addMonths($months)->subDay(),
            'payment_frequency' => $frequency,
            'contract_status' => 'active',
        ]);

        // Get automatically generated payments from Observer
        $payments = $contract->collectionPayments()->orderBy('due_date_start')->get();

        // Mark some payments as paid (via collection_date)
        for ($i = 0; $i < $paidCount && $i < count($payments); $i++) {
            $payments[$i]->update([
                'collection_date' => Carbon::now(),
            ]);
        }

        return $contract->fresh();
    }

    /**
     * Helper: Assert payment exists.
     */
    private function assertPaymentExists(CollectionPayment $payment, array $expectedData): void
    {
        $this->assertDatabaseHas('collection_payments', [
            'id' => $payment->id,
            'amount' => $expectedData['amount'] ?? $payment->amount,
        ]);
    }

    /**
     * Helper: Assert payment was deleted.
     */
    private function assertPaymentDeleted(int $paymentId): void
    {
        $this->assertDatabaseMissing('collection_payments', ['id' => $paymentId]);
    }

    /**
     * Helper: Assert dates are continuous.
     */
    private function assertDatesAreContinuous($payments): void
    {
        // Convert Collection to array if needed
        if ($payments instanceof \Illuminate\Support\Collection) {
            $payments = $payments->values()->all();
        }

        for ($i = 1; $i < count($payments); $i++) {
            $prevEnd = Carbon::parse($payments[$i - 1]->due_date_end);
            $currentStart = Carbon::parse($payments[$i]->due_date_start);

            // Start date should be the day after previous period end
            $this->assertEquals(
                $prevEnd->addDay()->format('Y-m-d'),
                $currentStart->format('Y-m-d'),
                "Date gap between payment {$i} and payment ".($i + 1)
            );
        }
    }

    // ============ Group 1: Duration Reduction Tests ============

    public function test_reschedule_reduce_duration_from_12_to_7_months()
    {
        // Original contract: 12 months quarterly (4 payments)
        $contract = $this->createContractWithPayments(12, 'quarterly', 2); // 2 payments paid

        $paidPayments = $contract->collectionPayments()->paid()->get();
        $unpaidPayments = $contract->collectionPayments()->unpaid()->get();

        $this->assertCount(2, $paidPayments);
        $this->assertCount(2, $unpaidPayments);

        // Reschedule: Add 1 month monthly
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1500, // New rent
            1,    // 1 additional month
            'monthly'
        );

        // Verify results
        $this->assertEquals(2, $result['deleted_count']);
        $this->assertCount(1, $result['new_payments']);
        $this->assertEquals(6, $result['paid_months']);
        $this->assertEquals(7, $result['total_months']);

        // Verify payments
        $contract->refresh();
        $allPayments = $contract->collectionPayments()->orderBy('due_date_start')->get();

        $this->assertCount(3, $allPayments); // 2 paid + 1 new

        // Paid payments should remain unchanged
        $this->assertNotNull($allPayments[0]->collection_date);
        $this->assertNotNull($allPayments[1]->collection_date);
        $this->assertEquals(1000 * 3, $allPayments[0]->amount); // Quarterly with old price

        // New payment
        $this->assertNull($allPayments[2]->collection_date);
        $this->assertEquals(1500, $allPayments[2]->amount); // Monthly with new price

        // Verify contract update
        $this->assertEquals(7, $contract->duration_months);
    }

    public function test_reschedule_reduce_from_24_to_15_months_mixed_frequency()
    {
        // Original contract: 24 months semi-annually (4 payments)
        $contract = $this->createContractWithPayments(24, 'semi_annually', 2); // 12 months paid

        // Reschedule: Add 3 months monthly
        $result = $this->service->rescheduleContractPayments(
            $contract,
            2000,
            3,
            'monthly'
        );

        $this->assertEquals(2, $result['deleted_count']);
        $this->assertCount(3, $result['new_payments']);
        $this->assertEquals(15, $result['total_months']);

        // Verify payments
        $allPayments = $contract->collectionPayments()->orderBy('due_date_start')->get();
        $this->assertCount(5, $allPayments); // 2 semi-annual + 3 monthly

        // Verify date continuity
        $this->assertDatesAreContinuous($allPayments);
    }

    // ============ Group 2: Duration Extension Tests ============

    public function test_reschedule_extend_from_6_to_18_months()
    {
        // Original contract: 6 months monthly
        $contract = $this->createContractWithPayments(6, 'monthly', 4); // 4 months paid

        // Reschedule: Add 12 months annually
        $result = $this->service->rescheduleContractPayments(
            $contract,
            3000,
            12,
            'annually'
        );

        $this->assertEquals(2, $result['deleted_count']); // Delete 2 unpaid months
        $this->assertCount(1, $result['new_payments']); // 1 annual payment
        $this->assertEquals(16, $result['total_months']); // 4 paid + 12 new

        $newPayment = $result['new_payments'][0];
        $this->assertEquals(3000 * 12, $newPayment->amount); // Annual
    }

    public function test_reschedule_triple_duration()
    {
        // Original contract: 12 months annually (1 payment)
        $contract = $this->createContractWithPayments(12, 'annually', 1); // Fully paid

        // Reschedule: Add 24 months quarterly
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1200,
            24,
            'quarterly'
        );

        $this->assertEquals(0, $result['deleted_count']); // No unpaid payments
        $this->assertCount(8, $result['new_payments']); // 24/3 = 8 quarterly payments
        $this->assertEquals(36, $result['total_months']);

        // Verify amounts
        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(1200 * 3, $payment->amount); // Quarterly
        }
    }

    // ============ Group 3: Frequency Change Tests ============

    public function test_reschedule_change_frequency_quarterly_to_monthly()
    {
        // Original contract: 12 months quarterly
        $contract = $this->createContractWithPayments(12, 'quarterly', 1); // 1 payment paid (3 months)

        // Reschedule: 9 months monthly
        $result = $this->service->rescheduleContractPayments(
            $contract,
            800,
            9,
            'monthly'
        );

        $this->assertEquals(3, $result['deleted_count']); // Delete 3 quarterly payments
        $this->assertCount(9, $result['new_payments']); // 9 monthly payments

        // Verify new frequency
        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(800, $payment->amount); // Monthly
        }
    }

    public function test_reschedule_change_frequency_monthly_to_annual()
    {
        // Original contract: 12 months monthly
        $contract = $this->createContractWithPayments(12, 'monthly', 6); // 6 months paid

        // Reschedule: 12 months annually
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1100,
            12,
            'annually'
        );

        $this->assertEquals(6, $result['deleted_count']); // Delete 6 unpaid monthly payments
        $this->assertCount(1, $result['new_payments']); // 1 annual payment
        $this->assertEquals(1100 * 12, $result['new_payments'][0]->amount);
    }

    // ============ Group 4: Amount Change Tests ============

    public function test_reschedule_increase_rent_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 6, 1000);

        $result = $this->service->rescheduleContractPayments(
            $contract,
            1500, // Increase from 1000 to 1500
            6,
            'monthly'
        );

        // Verify amounts
        $paidPayments = $contract->collectionPayments()->paid()->get();
        foreach ($paidPayments as $payment) {
            $this->assertEquals(1000, $payment->amount); // Old amount for paid
        }

        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(1500, $payment->amount); // New amount
        }
    }

    public function test_reschedule_decrease_rent_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3, 2000);

        $result = $this->service->rescheduleContractPayments(
            $contract,
            1200, // Decrease from 2000 to 1200
            9,
            'monthly'
        );

        // Verify amounts
        $paidPayments = $contract->collectionPayments()->paid()->get();
        foreach ($paidPayments as $payment) {
            $this->assertEquals(2000, $payment->amount); // Old amount
        }

        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(1200, $payment->amount); // New amount
        }
    }

    // ============ Group 5: Edge Case Tests ============

    public function test_reschedule_all_payments_paid()
    {
        // All payments are paid
        $contract = $this->createContractWithPayments(6, 'monthly', 6);

        $result = $this->service->rescheduleContractPayments(
            $contract,
            1800,
            3, // Add 3 new months
            'monthly'
        );

        $this->assertEquals(0, $result['deleted_count']); // Nothing to delete
        $this->assertCount(3, $result['new_payments']);
        $this->assertEquals(9, $result['total_months']); // 6 + 3

        // Verify new payments start after last paid payment
        $lastPaidDate = $this->service->getLastPaidPeriodEnd($contract);
        $firstNewPayment = $result['new_payments'][0];
        $this->assertEquals(
            $lastPaidDate->addDay()->format('Y-m-d'),
            Carbon::parse($firstNewPayment->due_date_start)->format('Y-m-d')
        );
    }

    public function test_reschedule_no_payments_paid()
    {
        // No payments are paid
        $contract = $this->createContractWithPayments(12, 'quarterly', 0);

        $result = $this->service->rescheduleContractPayments(
            $contract,
            900,
            6,
            'semi_annually'
        );

        $this->assertEquals(4, $result['deleted_count']); // Delete all old payments
        $this->assertCount(1, $result['new_payments']); // 1 semi-annual payment
        $this->assertEquals(0, $result['paid_months']);
        $this->assertEquals(6, $result['total_months']);
    }

    // ============ Group 6: Date Tests ============

    public function test_reschedule_dates_continuity()
    {
        $contract = $this->createContractWithPayments(12, 'quarterly', 1);

        $result = $this->service->rescheduleContractPayments(
            $contract,
            1000,
            9,
            'quarterly'
        );

        $allPayments = $contract->collectionPayments()->orderBy('due_date_start')->get();

        // Verify no gaps exist
        $this->assertDatesAreContinuous($allPayments);

        // Verify new date starts after last paid payment
        $lastPaidPayment = $contract->collectionPayments()->paid()->orderBy('due_date_end', 'desc')->first();
        $firstNewPayment = $result['new_payments'][0];

        $this->assertEquals(
            Carbon::parse($lastPaidPayment->due_date_end)->addDay()->format('Y-m-d'),
            Carbon::parse($firstNewPayment->due_date_start)->format('Y-m-d')
        );
    }

    public function test_reschedule_end_date_calculation()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 6);

        $result = $this->service->rescheduleContractPayments(
            $contract,
            1000,
            12, // Add one year
            'monthly'
        );

        // Verify end_date calculation
        $expectedEndDate = $this->service->getLastPaidPeriodEnd($contract->fresh())
            ->addDay() // New start
            ->addMonths(12) // Add duration
            ->subDay(); // Period end

        $this->assertEquals(
            $expectedEndDate->format('Y-m-d'),
            $result['new_end_date']->format('Y-m-d')
        );

        $contract->refresh();
        $this->assertEquals(
            $expectedEndDate->format('Y-m-d'),
            $contract->end_date->format('Y-m-d')
        );
    }

    // ============ Group 7: Validation Tests ============

    public function test_reschedule_invalid_duration_for_frequency()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Additional duration is incompatible with the selected payment frequency');

        // Attempt to add 7 months quarterly (7 is not divisible by 3)
        $this->service->rescheduleContractPayments(
            $contract,
            1000,
            7,
            'quarterly'
        );
    }

    public function test_reschedule_invalid_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        // Negative amount
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rent amount must be greater than zero');

        $this->service->rescheduleContractPayments(
            $contract,
            -500,
            9,
            'monthly'
        );
    }

    public function test_reschedule_zero_amount()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Rent amount must be greater than zero');

        $this->service->rescheduleContractPayments(
            $contract,
            0,
            9,
            'monthly'
        );
    }

    public function test_reschedule_invalid_additional_months()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Additional months must be greater than zero');

        $this->service->rescheduleContractPayments(
            $contract,
            1000,
            0, // Zero months
            'monthly'
        );
    }

    // ============ Group 8: Performance and Integration Tests ============

    public function test_reschedule_long_contract_performance()
    {
        $startTime = microtime(true);

        // Long contract: 60 months
        $contract = $this->createContractWithPayments(60, 'monthly', 12);

        $result = $this->service->rescheduleContractPayments(
            $contract,
            1500,
            24, // Add 2 years
            'monthly'
        );

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Should complete in less than 2 seconds
        $this->assertLessThan(2, $executionTime);

        // Verify results correctness
        $this->assertEquals(48, $result['deleted_count']); // 60-12=48
        $this->assertCount(24, $result['new_payments']);
        $this->assertEquals(36, $result['total_months']); // 12+24
    }

    public function test_reschedule_rollback_on_error()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        // Payment count before attempt
        $paymentCountBefore = $contract->collectionPayments()->count();

        try {
            // Simulate error with invalid data
            $this->service->rescheduleContractPayments(
                $contract,
                -1000, // Invalid amount
                6,
                'monthly'
            );
        } catch (\Exception $e) {
            // Expected
        }

        // Verify data unchanged
        $contract->refresh();
        $paymentCountAfter = $contract->collectionPayments()->count();

        $this->assertEquals($paymentCountBefore, $paymentCountAfter);
        $this->assertEquals(12, $contract->duration_months); // Unchanged
    }

    public function test_reschedule_permissions()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        // Test that canReschedule checks contract status, not permissions
        $this->assertTrue($this->contractService->canReschedule($contract));

        // Test that terminated contract cannot be rescheduled
        $expiredContract = UnitContract::create([
            'contract_number' => 'UC-EXPIRED-'.uniqid(),
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 1000,
            'duration_months' => 1,
            'start_date' => Carbon::now()->subMonths(2)->startOfMonth(),
            'end_date' => Carbon::now()->subMonths(1)->endOfMonth(),
            'payment_frequency' => 'monthly',
            'contract_status' => 'terminated',
        ]);

        $this->assertFalse($this->contractService->canReschedule($expiredContract));
    }

    // ============ Additional Special Case Tests ============

    public function test_reschedule_with_mixed_payment_frequencies()
    {
        // Contract started with quarterly payments
        $contract = $this->createContractWithPayments(12, 'quarterly', 2); // 6 months paid

        // First reschedule: Add 6 months semi-annually
        $result1 = $this->service->rescheduleContractPayments(
            $contract,
            1200,
            6,
            'semi_annually'
        );

        $this->assertEquals(2, $result1['deleted_count']);
        $this->assertCount(1, $result1['new_payments']);

        // Verify payments
        $allPayments = $contract->collectionPayments()->orderBy('due_date_start')->get();
        $this->assertCount(3, $allPayments); // 2 quarterly + 1 semi-annual

        // Simulate paying the semi-annual payment (via collection_date)
        $result1['new_payments'][0]->update([
            'collection_date' => Carbon::now(),
        ]);

        // Second reschedule: Add 3 months monthly
        $contract->refresh();
        $result2 = $this->service->rescheduleContractPayments(
            $contract,
            800,
            3,
            'monthly'
        );

        $this->assertCount(3, $result2['new_payments']);

        // Final result: Contract with 3 different frequency types
        $finalPayments = $contract->collectionPayments()->orderBy('due_date_start')->get();
        $this->assertCount(6, $finalPayments); // 2 quarterly + 1 semi-annual + 3 monthly

        // Verify date continuity
        $this->assertDatesAreContinuous($finalPayments);
    }

    public function test_reschedule_preserves_payment_numbers_sequence()
    {
        $contract = $this->createContractWithPayments(12, 'monthly', 3);

        // Count paid payments (which will remain after reschedule)
        $paidPaymentsCount = $contract->collectionPayments()->paid()->count();
        $this->assertEquals(3, $paidPaymentsCount);

        // Reschedule
        $result = $this->service->rescheduleContractPayments(
            $contract,
            1000,
            6,
            'monthly'
        );

        // After reschedule, new payments start from (remaining payments count + 1)
        // Remaining payments = paid payments = 3
        // So new payments start from 4
        foreach ($result['new_payments'] as $index => $payment) {
            $expectedNumber = $paidPaymentsCount + $index + 1;
            $this->assertStringContainsString(
                sprintf('%04d', $expectedNumber),
                $payment->payment_number
            );
        }
    }
}
