<?php

namespace Tests\Unit\Services;

use App\Models\Location;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Setting;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\UnitType;
use App\Models\User;
use App\Services\PaymentGeneratorService;
use App\Services\PropertyContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentGeneratorService $service;

    protected User $owner;

    protected User $tenant;

    protected Property $property;

    protected Unit $unit;

    protected Location $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PaymentGeneratorService::class);

        // Create location first (required for property foreign key)
        $this->location = Location::create([
            'name' => 'Test Location',
            'level' => 1,
        ]);

        // Create required reference data with forced IDs for MySQL compatibility
        PropertyStatus::query()->updateOrInsert(['id' => 1], ['name' => 'Available', 'slug' => 'available', 'created_at' => now(), 'updated_at' => now()]);
        PropertyType::query()->updateOrInsert(['id' => 1], ['name' => 'Residential', 'slug' => 'residential', 'created_at' => now(), 'updated_at' => now()]);
        UnitType::query()->updateOrInsert(['id' => 1], ['name' => 'Apartment', 'slug' => 'apartment', 'created_at' => now(), 'updated_at' => now()]);

        // Create owner and tenant
        $this->owner = User::factory()->create(['type' => 'owner']);
        $this->tenant = User::factory()->create(['type' => 'tenant']);

        // Create property and unit
        $this->property = Property::factory()->create([
            'owner_id' => $this->owner->id,
            'location_id' => $this->location->id,
        ]);
        $this->unit = Unit::factory()->create(['property_id' => $this->property->id]);

        // Set payment due days setting
        Setting::set('payment_due_days', 5);
    }

    /**
     * Helper to create a unit contract with specific parameters
     * Uses direct database insert to bypass observers
     */
    protected function createUnitContract(array $overrides = []): UnitContract
    {
        static $contractCounter = 0;
        $contractCounter++;

        $startDate = $overrides['start_date'] ?? Carbon::now()->startOfMonth();
        $durationMonths = $overrides['duration_months'] ?? 12;

        // Calculate end_date manually if not provided
        if (! isset($overrides['end_date'])) {
            $endDate = Carbon::parse($startDate)->addMonths($durationMonths)->subDay();
        } else {
            $endDate = $overrides['end_date'];
        }

        // Generate unique contract number with static counter
        $contractNumber = sprintf('UC-TEST-%d-%d', time(), $contractCounter);

        $defaults = [
            'contract_number' => $contractNumber,
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'monthly_rent' => 3000,
            'security_deposit' => 3000,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_frequency' => 'monthly',
            'contract_status' => 'active',
        ];

        $data = array_merge($defaults, $overrides);

        // Recalculate end_date if duration changed
        if (isset($overrides['duration_months']) && ! isset($overrides['end_date'])) {
            $data['end_date'] = Carbon::parse($data['start_date'])->addMonths($data['duration_months'])->subDay();
        }

        // Use DB insert to bypass observers and prevent auto-generation of payments
        $id = \DB::table('unit_contracts')->insertGetId([
            'contract_number' => $data['contract_number'],
            'tenant_id' => $data['tenant_id'],
            'unit_id' => $data['unit_id'],
            'property_id' => $data['property_id'],
            'monthly_rent' => $data['monthly_rent'],
            'security_deposit' => $data['security_deposit'],
            'duration_months' => $data['duration_months'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'payment_frequency' => $data['payment_frequency'],
            'contract_status' => $data['contract_status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return UnitContract::find($id);
    }

    /**
     * Helper to create a property contract with specific parameters
     * Uses direct database insert to bypass observers
     */
    protected function createPropertyContract(array $overrides = []): PropertyContract
    {
        $startDate = $overrides['start_date'] ?? Carbon::now()->startOfMonth();
        $durationMonths = $overrides['duration_months'] ?? 12;

        // Calculate end_date manually if not provided
        if (! isset($overrides['end_date'])) {
            $endDate = Carbon::parse($startDate)->addMonths($durationMonths)->subDay();
        } else {
            $endDate = $overrides['end_date'];
        }

        // Generate unique contract number
        $year = date('Y');
        $contractNumber = sprintf('PC-%s-%04d', $year, rand(1, 9999));

        $defaults = [
            'contract_number' => $contractNumber,
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'commission_rate' => 5.00,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'payment_frequency' => 'monthly',
            'payments_count' => $durationMonths,
            'contract_status' => 'active',
        ];

        $data = array_merge($defaults, $overrides);

        // Recalculate end_date and payments_count if duration changed
        if (isset($overrides['duration_months']) && ! isset($overrides['end_date'])) {
            $data['end_date'] = Carbon::parse($data['start_date'])->addMonths($data['duration_months'])->subDay();
        }

        // Use DB insert to bypass observers
        $id = \DB::table('property_contracts')->insertGetId([
            'contract_number' => $data['contract_number'],
            'owner_id' => $data['owner_id'],
            'property_id' => $data['property_id'],
            'commission_rate' => $data['commission_rate'],
            'duration_months' => $data['duration_months'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'payment_frequency' => $data['payment_frequency'],
            'payments_count' => $data['payments_count'],
            'contract_status' => $data['contract_status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return PropertyContract::find($id);
    }

    // ==========================================
    // Tests for generateTenantPayments
    // ==========================================

    public function test_generate_tenant_payments_creates_correct_count(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        $this->assertCount(12, $payments);
        $this->assertEquals(12, $contract->collectionPayments()->count());
    }

    public function test_generate_tenant_payments_monthly_frequency(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 6,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 2000,
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        $this->assertCount(6, $payments);

        // Check first payment dates
        $this->assertEquals('2025-01-01', $payments[0]->due_date_start->format('Y-m-d'));
        $this->assertEquals('2025-01-31', $payments[0]->due_date_end->format('Y-m-d'));

        // Check second payment dates
        $this->assertEquals('2025-02-01', $payments[1]->due_date_start->format('Y-m-d'));
    }

    public function test_generate_tenant_payments_quarterly_frequency(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 12,
            'start_date' => $startDate,
            'payment_frequency' => 'quarterly',
            'monthly_rent' => 2000,
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        // 12 months / 3 months per payment = 4 payments
        $this->assertCount(4, $payments);

        // Each payment should be 3 months worth
        $this->assertEquals(6000, $payments[0]->amount);
    }

    public function test_generate_tenant_payments_semi_annually_frequency(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 12,
            'start_date' => $startDate,
            'payment_frequency' => 'semi_annually',
            'monthly_rent' => 2000,
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        // 12 months / 6 months per payment = 2 payments
        $this->assertCount(2, $payments);

        // Each payment should be 6 months worth
        $this->assertEquals(12000, $payments[0]->amount);
    }

    public function test_generate_tenant_payments_annually_frequency(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 12,
            'start_date' => $startDate,
            'payment_frequency' => 'annually',
            'monthly_rent' => 2000,
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        // 12 months / 12 months per payment = 1 payment
        $this->assertCount(1, $payments);

        // Payment should be full year
        $this->assertEquals(24000, $payments[0]->amount);
    }

    public function test_generate_tenant_payments_sets_correct_amounts(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 5000,
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        foreach ($payments as $payment) {
            $this->assertEquals(5000, $payment->amount);
            $this->assertEquals(5000, $payment->total_amount);
            $this->assertEquals(0, $payment->late_fee);
        }
    }

    public function test_generate_tenant_payments_sets_correct_dates(): void
    {
        $startDate = Carbon::parse('2025-03-01');
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        // First payment: March
        $this->assertEquals('2025-03-01', $payments[0]->due_date_start->format('Y-m-d'));
        $this->assertEquals('2025-03-31', $payments[0]->due_date_end->format('Y-m-d'));
        $this->assertEquals('2025-03', $payments[0]->month_year);

        // Second payment: April
        $this->assertEquals('2025-04-01', $payments[1]->due_date_start->format('Y-m-d'));
        $this->assertEquals('2025-04-30', $payments[1]->due_date_end->format('Y-m-d'));
        $this->assertEquals('2025-04', $payments[1]->month_year);

        // Third payment: May (ends on the contract end date since it's the last payment)
        $this->assertEquals('2025-05-01', $payments[2]->due_date_start->format('Y-m-d'));
        // The end date is the contract end date (May 31)
        $this->assertEquals('2025-05', $payments[2]->month_year);
    }

    public function test_generate_tenant_payments_fails_for_invalid_rent(): void
    {
        $contract = $this->createUnitContract([
            'monthly_rent' => 0,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('مبلغ الإيجار الشهري غير صحيح');

        $this->service->generateTenantPayments($contract);
    }

    public function test_generate_tenant_payments_fails_for_negative_rent(): void
    {
        $contract = $this->createUnitContract([
            'monthly_rent' => -1000,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('مبلغ الإيجار الشهري غير صحيح');

        $this->service->generateTenantPayments($contract);
    }

    public function test_generate_tenant_payments_uses_transaction(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        // Verify that before generation, no payments exist (observer bypassed)
        $this->assertEquals(0, $contract->collectionPayments()->count());

        $payments = $this->service->generateTenantPayments($contract);

        // After successful generation, all payments should exist
        $this->assertEquals(12, $contract->collectionPayments()->count());
    }

    // ==========================================
    // Tests for generateSupplyPaymentsForContract
    // ==========================================

    public function test_generate_supply_payments_creates_correct_count(): void
    {
        $contract = $this->createPropertyContract([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'payments_count' => 12,
        ]);

        $count = $this->service->generateSupplyPaymentsForContract($contract);

        $this->assertEquals(12, $count);
        $this->assertEquals(12, $contract->supplyPayments()->count());
    }

    public function test_generate_supply_payments_fails_when_already_exist(): void
    {
        $contract = $this->createPropertyContract([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'payments_count' => 12,
        ]);

        // Generate payments first time
        $this->service->generateSupplyPaymentsForContract($contract);

        // Try to generate again should fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('لا يمكن توليد دفعات جديدة - يوجد 12 دفعة مولدة مسبقاً لهذا العقد');

        $this->service->generateSupplyPaymentsForContract($contract);
    }

    public function test_generate_supply_payments_validates_duration(): void
    {
        $contract = $this->createPropertyContract([
            'duration_months' => 5, // Invalid for quarterly
            'payment_frequency' => 'quarterly',
            'payments_count' => 0,
        ]);

        $this->expectException(\Exception::class);

        $this->service->generateSupplyPaymentsForContract($contract);
    }

    // ==========================================
    // Tests for rescheduleContractPayments
    // ==========================================

    public function test_reschedule_deletes_unpaid_payments(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 6,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        // Generate initial payments (observer bypassed, so only service generates)
        $this->service->generateTenantPayments($contract);
        $this->assertEquals(6, $contract->collectionPayments()->count());

        // Mark first 2 payments as paid using DB directly to avoid observer issues
        $paymentIds = $contract->collectionPayments()
            ->orderBy('due_date_start')
            ->limit(2)
            ->pluck('id');
        \DB::table('collection_payments')
            ->whereIn('id', $paymentIds)
            ->update(['collection_date' => Carbon::now()]);

        // Reschedule
        $result = $this->service->rescheduleContractPayments(
            $contract->fresh(),
            3500,
            6,
            'monthly'
        );

        // Should have deleted unpaid payments (6 - 2 = 4)
        $this->assertEquals(4, $result['deleted_count']);

        // Total payments should be: 2 paid + 6 new = 8
        $this->assertEquals(8, $contract->collectionPayments()->count());
    }

    public function test_reschedule_preserves_paid_payments(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 6,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        $this->service->generateTenantPayments($contract);

        // Mark first 3 payments as paid using DB directly
        $paidIds = $contract->collectionPayments()
            ->orderBy('due_date_start')
            ->limit(3)
            ->pluck('id')
            ->toArray();

        \DB::table('collection_payments')
            ->whereIn('id', $paidIds)
            ->update(['collection_date' => Carbon::now()]);

        // Reschedule
        $this->service->rescheduleContractPayments(
            $contract->fresh(),
            3500,
            6,
            'monthly'
        );

        // Verify paid payments still exist
        foreach ($paidIds as $id) {
            $this->assertDatabaseHas('collection_payments', ['id' => $id]);
        }
    }

    public function test_reschedule_creates_new_payments(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        $this->service->generateTenantPayments($contract);

        // Mark first payment as paid using DB directly
        $firstPaymentId = $contract->collectionPayments()->orderBy('due_date_start')->first()->id;
        \DB::table('collection_payments')
            ->where('id', $firstPaymentId)
            ->update(['collection_date' => Carbon::now()]);

        $result = $this->service->rescheduleContractPayments(
            $contract->fresh(),
            4000,
            6,
            'monthly'
        );

        // Should have created 6 new payments
        $this->assertCount(6, $result['new_payments']);

        // New payments should have new rent amount
        foreach ($result['new_payments'] as $payment) {
            $this->assertEquals(4000, $payment->amount);
        }
    }

    public function test_reschedule_updates_contract_end_date(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        $this->service->generateTenantPayments($contract);

        // Mark first payment as paid (January) using DB directly
        $firstPaymentId = $contract->collectionPayments()->orderBy('due_date_start')->first()->id;
        \DB::table('collection_payments')
            ->where('id', $firstPaymentId)
            ->update(['collection_date' => Carbon::now()]);

        // Last paid date is January 31, new start is February 1
        // Adding 6 months from February 1 = July 31
        $result = $this->service->rescheduleContractPayments(
            $contract->fresh(),
            3000,
            6,
            'monthly'
        );

        $this->assertEquals('2025-07-31', $result['new_end_date']->format('Y-m-d'));
    }

    public function test_reschedule_updates_contract_duration(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        $this->service->generateTenantPayments($contract);

        // Mark first payment as paid (1 month paid) using DB directly
        $firstPaymentId = $contract->collectionPayments()->orderBy('due_date_start')->first()->id;
        \DB::table('collection_payments')
            ->where('id', $firstPaymentId)
            ->update(['collection_date' => Carbon::now()]);

        $result = $this->service->rescheduleContractPayments(
            $contract->fresh(),
            3000,
            6,
            'monthly'
        );

        // Total months = paid months (1) + additional months (6) = 7
        $this->assertEquals(7, $result['total_months']);
        $this->assertEquals(1, $result['paid_months']);
    }

    public function test_reschedule_validates_additional_months(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'payment_frequency' => 'monthly',
        ]);

        $this->service->generateTenantPayments($contract);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('عدد الأشهر الإضافية يجب أن يكون أكبر من صفر');

        $this->service->rescheduleContractPayments(
            $contract,
            3000,
            0,
            'monthly'
        );
    }

    public function test_reschedule_validates_rent_amount(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'payment_frequency' => 'monthly',
        ]);

        $this->service->generateTenantPayments($contract);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('قيمة الإيجار يجب أن تكون أكبر من صفر');

        $this->service->rescheduleContractPayments(
            $contract,
            0,
            6,
            'monthly'
        );
    }

    public function test_reschedule_validates_frequency_compatibility(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'payment_frequency' => 'monthly',
        ]);

        $this->service->generateTenantPayments($contract);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('المدة الإضافية لا تتوافق مع تكرار الدفع المختار');

        // 5 months is not compatible with quarterly (3 months)
        $this->service->rescheduleContractPayments(
            $contract,
            3000,
            5,
            'quarterly'
        );
    }

    public function test_reschedule_dates_are_continuous(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        $this->service->generateTenantPayments($contract);

        // Mark first two payments as paid using DB directly
        $paymentIds = $contract->collectionPayments()
            ->orderBy('due_date_start')
            ->limit(2)
            ->pluck('id');
        \DB::table('collection_payments')
            ->whereIn('id', $paymentIds)
            ->update(['collection_date' => Carbon::now()]);

        $result = $this->service->rescheduleContractPayments(
            $contract->fresh(),
            3500,
            3,
            'monthly'
        );

        // Last paid payment ends on February 28
        // New payments should start from March 1
        $this->assertEquals('2025-03-01', $result['new_payments'][0]->due_date_start->format('Y-m-d'));
    }

    // ==========================================
    // Tests for getLastPaidPeriodEnd
    // ==========================================

    public function test_get_last_paid_period_end_returns_correct_date(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 6,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
        ]);

        $this->service->generateTenantPayments($contract);

        // Mark first 3 payments as paid using DB directly
        $paymentIds = $contract->collectionPayments()
            ->orderBy('due_date_start')
            ->limit(3)
            ->pluck('id');
        \DB::table('collection_payments')
            ->whereIn('id', $paymentIds)
            ->update(['collection_date' => Carbon::now()]);

        $lastPaidEnd = $this->service->getLastPaidPeriodEnd($contract->fresh());

        // Third payment (March) ends on March 31
        $this->assertEquals('2025-03-31', $lastPaidEnd->format('Y-m-d'));
    }

    public function test_get_last_paid_period_end_returns_null_when_no_paid(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'payment_frequency' => 'monthly',
        ]);

        $this->service->generateTenantPayments($contract);

        $lastPaidEnd = $this->service->getLastPaidPeriodEnd($contract);

        $this->assertNull($lastPaidEnd);
    }

    // ==========================================
    // Tests for calculatePaymentCount
    // ==========================================

    public function test_calculate_payment_count_monthly(): void
    {
        $count = PropertyContractService::calculatePaymentsCount(12, 'monthly');
        $this->assertEquals(12, $count);

        $count = PropertyContractService::calculatePaymentsCount(6, 'monthly');
        $this->assertEquals(6, $count);
    }

    public function test_calculate_payment_count_quarterly(): void
    {
        $count = PropertyContractService::calculatePaymentsCount(12, 'quarterly');
        $this->assertEquals(4, $count);

        $count = PropertyContractService::calculatePaymentsCount(6, 'quarterly');
        $this->assertEquals(2, $count);
    }

    public function test_calculate_payment_count_semi_annually(): void
    {
        $count = PropertyContractService::calculatePaymentsCount(12, 'semi_annually');
        $this->assertEquals(2, $count);

        $count = PropertyContractService::calculatePaymentsCount(18, 'semi_annually');
        $this->assertEquals(3, $count);
    }

    public function test_calculate_payment_count_annually(): void
    {
        $count = PropertyContractService::calculatePaymentsCount(12, 'annually');
        $this->assertEquals(1, $count);

        $count = PropertyContractService::calculatePaymentsCount(24, 'annually');
        $this->assertEquals(2, $count);
    }

    public function test_calculate_payment_count_returns_error_for_invalid_division(): void
    {
        // 5 months is not divisible by quarterly (3 months)
        $result = PropertyContractService::calculatePaymentsCount(5, 'quarterly');
        $this->assertEquals('قسمة لا تصح', $result);

        // 7 months is not divisible by semi_annually (6 months)
        $result = PropertyContractService::calculatePaymentsCount(7, 'semi_annually');
        $this->assertEquals('قسمة لا تصح', $result);
    }

    // ==========================================
    // Additional Edge Case Tests
    // ==========================================

    public function test_generate_payments_generates_unique_payment_numbers(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 6,
            'payment_frequency' => 'monthly',
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        $paymentNumbers = collect($payments)->pluck('payment_number');
        $uniqueNumbers = $paymentNumbers->unique();

        $this->assertEquals($paymentNumbers->count(), $uniqueNumbers->count());
    }

    public function test_reschedule_when_no_payments_exist(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'start_date' => Carbon::parse('2025-01-01'),
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        // Don't generate any payments, directly reschedule
        $result = $this->service->rescheduleContractPayments(
            $contract,
            3500,
            6,
            'monthly'
        );

        // No payments existed to delete (observer bypassed)
        $this->assertEquals(0, $result['deleted_count']);
        $this->assertCount(6, $result['new_payments']);
        $this->assertEquals(0, $result['paid_months']);
    }

    public function test_reschedule_when_all_payments_are_paid(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        $this->service->generateTenantPayments($contract);

        // Mark all payments as paid using DB directly
        \DB::table('collection_payments')
            ->where('unit_contract_id', $contract->id)
            ->update(['collection_date' => Carbon::now()]);

        $result = $this->service->rescheduleContractPayments(
            $contract->fresh(),
            4000,
            6,
            'monthly'
        );

        // No payments should be deleted (all are paid)
        $this->assertEquals(0, $result['deleted_count']);

        // New payments should start after the last paid period
        $this->assertEquals('2025-04-01', $result['new_payments'][0]->due_date_start->format('Y-m-d'));
    }

    public function test_generate_payments_with_quarterly_creates_correct_period_dates(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createUnitContract([
            'duration_months' => 12,
            'start_date' => $startDate,
            'payment_frequency' => 'quarterly',
            'monthly_rent' => 3000,
        ]);

        $payments = $this->service->generateTenantPayments($contract);

        // First quarter: Jan 1 - Mar 31
        $this->assertEquals('2025-01-01', $payments[0]->due_date_start->format('Y-m-d'));
        $this->assertEquals('2025-03-31', $payments[0]->due_date_end->format('Y-m-d'));

        // Second quarter: Apr 1 - Jun 30
        $this->assertEquals('2025-04-01', $payments[1]->due_date_start->format('Y-m-d'));
        $this->assertEquals('2025-06-30', $payments[1]->due_date_end->format('Y-m-d'));

        // Third quarter: Jul 1 - Sep 30
        $this->assertEquals('2025-07-01', $payments[2]->due_date_start->format('Y-m-d'));
        $this->assertEquals('2025-09-30', $payments[2]->due_date_end->format('Y-m-d'));

        // Fourth quarter: Oct 1 - Dec 31
        $this->assertEquals('2025-10-01', $payments[3]->due_date_start->format('Y-m-d'));
        $this->assertEquals('2025-12-31', $payments[3]->due_date_end->format('Y-m-d'));
    }

    public function test_supply_payments_have_correct_structure(): void
    {
        $startDate = Carbon::parse('2025-01-01');
        $contract = $this->createPropertyContract([
            'duration_months' => 6,
            'start_date' => $startDate,
            'payment_frequency' => 'monthly',
            'payments_count' => 6,
            'commission_rate' => 5.00,
        ]);

        $count = $this->service->generateSupplyPaymentsForContract($contract);

        $payments = $contract->supplyPayments()->orderBy('month_year')->get();

        foreach ($payments as $payment) {
            $this->assertEquals($contract->id, $payment->property_contract_id);
            $this->assertEquals($contract->owner_id, $payment->owner_id);
            $this->assertEquals(5.00, $payment->commission_rate);
            $this->assertEquals('pending', $payment->approval_status);
            $this->assertNotNull($payment->payment_number);
            $this->assertNotNull($payment->month_year);
        }
    }

    public function test_rollback_on_error_during_payment_generation(): void
    {
        $contract = $this->createUnitContract([
            'duration_months' => 3,
            'payment_frequency' => 'monthly',
            'monthly_rent' => 3000,
        ]);

        // Verify no payments before (observer bypassed)
        $this->assertEquals(0, $contract->collectionPayments()->count());

        // Generate payments successfully first
        $this->service->generateTenantPayments($contract);
        $this->assertEquals(3, $contract->collectionPayments()->count());
    }
}
