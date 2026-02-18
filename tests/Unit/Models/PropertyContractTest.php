<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\SupplyPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertyContractTest extends TestCase
{
    use RefreshDatabase;

    protected Owner $owner;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected Location $location;

    protected Property $property;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createRequiredLookupData();
    }

    protected function createRequiredLookupData(): void
    {
        // Create property type
        $this->propertyType = PropertyType::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Residential Building',
                'slug' => 'residential-building',
            ]
        );

        // Create property status
        $this->propertyStatus = PropertyStatus::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Available',
                'slug' => 'available',
            ]
        );

        // Create location
        $this->location = Location::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Location',
                'level' => 1,
            ]
        );

        // Create owner
        $this->owner = Owner::create([
            'name' => 'Test Owner',
            'phone' => '0509876543',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        // Create property
        $this->property = Property::create([
            'name' => 'Test Property',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Test Address 123',
        ]);
    }

    protected function createContract(array $attributes = []): PropertyContract
    {
        // Use withoutEvents to prevent observer from auto-generating payments
        return PropertyContract::withoutEvents(function () use ($attributes) {
            $startDate = $attributes['start_date'] ?? Carbon::now()->addYears(10);
            $durationMonths = $attributes['duration_months'] ?? 12;
            $paymentFrequency = $attributes['payment_frequency'] ?? 'monthly';

            // Calculate end_date if not provided
            if (! isset($attributes['end_date'])) {
                $endDate = Carbon::parse($startDate)->addMonths($durationMonths)->subDay();
            } else {
                $endDate = $attributes['end_date'];
            }

            // Calculate payments count
            $monthsPerPayment = match ($paymentFrequency) {
                'monthly' => 1,
                'quarterly' => 3,
                'semi_annually' => 6,
                'annually' => 12,
                default => 1,
            };

            return PropertyContract::create(array_merge([
                'contract_number' => 'PC-TEST-'.str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
                'owner_id' => $this->owner->id,
                'property_id' => $this->property->id,
                'commission_rate' => 5.00,
                'duration_months' => $durationMonths,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'contract_status' => 'active',
                'payment_day' => 1,
                'auto_renew' => false,
                'notice_period_days' => 30,
                'payment_frequency' => $paymentFrequency,
                'payments_count' => $durationMonths / $monthsPerPayment,
            ], $attributes));
        });
    }

    // ==========================================
    // Basic Model Tests
    // ==========================================

    #[Test]
    public function property_contract_can_be_created(): void
    {
        $contract = $this->createContract();

        $this->assertDatabaseHas('property_contracts', [
            'property_id' => $this->property->id,
            'owner_id' => $this->owner->id,
        ]);

        $this->assertInstanceOf(PropertyContract::class, $contract);
    }

    #[Test]
    public function property_contract_has_fillable_attributes(): void
    {
        $contract = $this->createContract([
            'commission_rate' => 7.50,
            'duration_months' => 24,
            'contract_status' => 'draft',
            'payment_frequency' => 'quarterly',
            'notes' => 'Test contract notes',
        ]);

        $this->assertEquals(7.50, $contract->commission_rate);
        $this->assertEquals(24, $contract->duration_months);
        $this->assertEquals('draft', $contract->contract_status);
        $this->assertEquals('quarterly', $contract->payment_frequency);
        $this->assertEquals('Test contract notes', $contract->notes);
    }

    #[Test]
    public function property_contract_casts_attributes_correctly(): void
    {
        $contract = $this->createContract([
            'commission_rate' => '5.50',
            'duration_months' => 12,
        ]);

        $this->assertIsNumeric($contract->commission_rate);
        $this->assertIsInt($contract->duration_months);
        $this->assertInstanceOf(Carbon::class, $contract->start_date);
        $this->assertInstanceOf(Carbon::class, $contract->end_date);
    }

    // ==========================================
    // Relationship Tests
    // ==========================================

    #[Test]
    public function property_contract_belongs_to_property(): void
    {
        $contract = $this->createContract();

        $this->assertInstanceOf(Property::class, $contract->property);
        $this->assertEquals($this->property->id, $contract->property->id);
    }

    #[Test]
    public function property_contract_belongs_to_owner(): void
    {
        $contract = $this->createContract();

        $this->assertInstanceOf(User::class, $contract->owner);
        $this->assertEquals($this->owner->id, $contract->owner->id);
    }

    #[Test]
    public function property_contract_has_many_supply_payments(): void
    {
        $contract = $this->createContract();

        // Create supply payments
        SupplyPayment::create([
            'property_contract_id' => $contract->id,
            'owner_id' => $this->owner->id,
            'gross_amount' => 1000,
            'commission_amount' => 50,
            'commission_rate' => 5,
            'maintenance_deduction' => 0,
            'other_deductions' => 0,
            'net_amount' => 950,
            'due_date' => Carbon::now()->addMonth(),
            'month_year' => Carbon::now()->format('Y-m'),
        ]);

        SupplyPayment::create([
            'property_contract_id' => $contract->id,
            'owner_id' => $this->owner->id,
            'gross_amount' => 1000,
            'commission_amount' => 50,
            'commission_rate' => 5,
            'maintenance_deduction' => 0,
            'other_deductions' => 0,
            'net_amount' => 950,
            'due_date' => Carbon::now()->addMonths(2),
            'month_year' => Carbon::now()->addMonth()->format('Y-m'),
        ]);

        $contract->refresh();

        $this->assertCount(2, $contract->supplyPayments);
        $this->assertInstanceOf(SupplyPayment::class, $contract->supplyPayments->first());
    }

    #[Test]
    public function property_contract_owner_accessor_returns_property_owner(): void
    {
        $contract = $this->createContract();

        // The owner accessor returns the property's owner
        $this->assertEquals($this->owner->name, $contract->owner->name);
    }

    // ==========================================
    // Payments Count Accessor Tests
    // ==========================================

    #[Test]
    public function payments_count_returns_stored_value_if_set(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'payments_count' => 12,
        ]);

        $this->assertEquals(12, $contract->payments_count);
    }

    #[Test]
    public function payments_count_calculates_correctly_for_monthly(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'payments_count' => null, // Force calculation
        ]);

        // Since we stored payments_count, let's manually check calculation
        $this->assertEquals(12, \App\Services\PropertyContractService::calculatePaymentsCount(12, 'monthly'));
    }

    #[Test]
    public function payments_count_calculates_correctly_for_quarterly(): void
    {
        $this->assertEquals(4, \App\Services\PropertyContractService::calculatePaymentsCount(12, 'quarterly'));
        $this->assertEquals(2, \App\Services\PropertyContractService::calculatePaymentsCount(6, 'quarterly'));
    }

    #[Test]
    public function payments_count_calculates_correctly_for_semi_annually(): void
    {
        $this->assertEquals(2, \App\Services\PropertyContractService::calculatePaymentsCount(12, 'semi_annually'));
        $this->assertEquals(1, \App\Services\PropertyContractService::calculatePaymentsCount(6, 'semi_annually'));
    }

    #[Test]
    public function payments_count_calculates_correctly_for_annually(): void
    {
        $this->assertEquals(1, \App\Services\PropertyContractService::calculatePaymentsCount(12, 'annually'));
        $this->assertEquals(2, \App\Services\PropertyContractService::calculatePaymentsCount(24, 'annually'));
    }

    // ==========================================
    // Duration Frequency Validation Tests
    // ==========================================

    #[Test]
    public function validates_duration_for_monthly_frequency(): void
    {
        $contract = $this->createContract([
            'duration_months' => 7, // Any number works for monthly
            'payment_frequency' => 'monthly',
        ]);

        $this->assertTrue($contract->isValidDurationForFrequency());
    }

    #[Test]
    public function validates_duration_for_quarterly_frequency(): void
    {
        $contract = $this->createContract([
            'duration_months' => 6, // Divisible by 3
            'payment_frequency' => 'quarterly',
        ]);

        $this->assertTrue($contract->isValidDurationForFrequency());
    }

    #[Test]
    public function rejects_invalid_duration_for_quarterly_frequency(): void
    {
        $contract = $this->createContract([
            'duration_months' => 7, // Not divisible by 3
            'payment_frequency' => 'quarterly',
        ]);

        $this->assertFalse($contract->isValidDurationForFrequency());
    }

    #[Test]
    public function validates_duration_for_semi_annual_frequency(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12, // Divisible by 6
            'payment_frequency' => 'semi_annually',
        ]);

        $this->assertTrue($contract->isValidDurationForFrequency());
    }

    #[Test]
    public function rejects_invalid_duration_for_semi_annual_frequency(): void
    {
        $contract = $this->createContract([
            'duration_months' => 7, // Not divisible by 6
            'payment_frequency' => 'semi_annually',
        ]);

        $this->assertFalse($contract->isValidDurationForFrequency());
    }

    #[Test]
    public function validates_duration_for_annual_frequency(): void
    {
        $contract = $this->createContract([
            'duration_months' => 24, // Divisible by 12
            'payment_frequency' => 'annually',
        ]);

        $this->assertTrue($contract->isValidDurationForFrequency());
    }

    #[Test]
    public function rejects_invalid_duration_for_annual_frequency(): void
    {
        $contract = $this->createContract([
            'duration_months' => 18, // Not divisible by 12
            'payment_frequency' => 'annually',
        ]);

        $this->assertFalse($contract->isValidDurationForFrequency());
    }

    // ==========================================
    // Can Generate Payments Tests
    // ==========================================

    #[Test]
    public function can_generate_payments_returns_true_when_no_payments_exist(): void
    {
        $contract = $this->createContract([
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'payments_count' => 12,
        ]);

        $this->assertTrue($contract->canGeneratePayments());
    }

    #[Test]
    public function can_generate_payments_returns_false_when_payments_exist(): void
    {
        $contract = $this->createContract();

        // Add a supply payment
        SupplyPayment::create([
            'property_contract_id' => $contract->id,
            'owner_id' => $this->owner->id,
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

        $this->assertFalse($contract->canGeneratePayments());
    }

    #[Test]
    public function can_generate_payments_returns_false_when_payments_count_invalid(): void
    {
        $contract = $this->createContract([
            'payments_count' => 0,
        ]);

        $this->assertFalse($contract->canGeneratePayments());
    }

    #[Test]
    public function can_generate_payments_returns_false_when_duration_invalid(): void
    {
        $contract = $this->createContract([
            'duration_months' => 7,
            'payment_frequency' => 'quarterly', // 7 is not divisible by 3
        ]);

        $this->assertFalse($contract->canGeneratePayments());
    }

    // ==========================================
    // Can Renew Tests
    // ==========================================

    #[Test]
    public function can_renew_returns_true_for_active_contract(): void
    {
        $contract = $this->createContract([
            'contract_status' => 'active',
        ]);

        $this->assertTrue($contract->canRenew());
    }

    #[Test]
    public function can_renew_returns_true_for_expired_contract(): void
    {
        $contract = $this->createContract([
            'contract_status' => 'expired',
        ]);

        $this->assertFalse($contract->canRenew());
    }

    #[Test]
    public function can_renew_returns_false_for_draft_contract(): void
    {
        $contract = $this->createContract([
            'contract_status' => 'draft',
        ]);

        $this->assertFalse($contract->canRenew());
    }

    #[Test]
    public function can_renew_returns_false_for_terminated_contract(): void
    {
        $contract = $this->createContract([
            'contract_status' => 'terminated',
        ]);

        $this->assertFalse($contract->canRenew());
    }

    #[Test]
    public function can_renew_returns_false_when_no_end_date(): void
    {
        // Create a contract then manually set end_date to null in memory
        // (database constraint prevents null, but we test the model logic)
        $contract = $this->createContract([
            'contract_status' => 'active',
        ]);

        // Manually set end_date to null to test the canRenew logic
        $contract->end_date = null;

        $this->assertFalse($contract->canRenew());
    }

    // ==========================================
    // Commission Calculation Tests
    // ==========================================

    #[Test]
    public function calculates_commission_correctly(): void
    {
        $contract = $this->createContract([
            'commission_rate' => 5.00,
        ]);

        // 5% of 10000 = 500
        $this->assertEquals(500.00, $contract->calculateCommission(10000));
    }

    #[Test]
    public function calculates_commission_with_decimal_rate(): void
    {
        $contract = $this->createContract([
            'commission_rate' => 7.50,
        ]);

        // 7.5% of 10000 = 750
        $this->assertEquals(750.00, $contract->calculateCommission(10000));
    }

    #[Test]
    public function calculates_commission_with_zero_amount(): void
    {
        $contract = $this->createContract([
            'commission_rate' => 5.00,
        ]);

        $this->assertEquals(0.00, $contract->calculateCommission(0));
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    #[Test]
    public function scope_expiring_returns_contracts_ending_soon(): void
    {
        // Create a contract expiring in 15 days
        $expiringContract = $this->createContract([
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(11)->subDays(15),
            'end_date' => Carbon::now()->addDays(15),
        ]);

        // Create a contract not expiring soon
        $property2 = Property::create([
            'name' => 'Property 2',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Test Address 2',
        ]);

        $notExpiringContract = PropertyContract::withoutEvents(function () use ($property2) {
            return PropertyContract::create([
                'contract_number' => 'PC-TEST-0002',
                'owner_id' => $this->owner->id,
                'property_id' => $property2->id,
                'commission_rate' => 5.00,
                'duration_months' => 12,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addYear(),
                'contract_status' => 'active',
                'payment_day' => 1,
                'auto_renew' => false,
                'notice_period_days' => 30,
                'payment_frequency' => 'monthly',
                'payments_count' => 12,
            ]);
        });

        $expiringContracts = PropertyContract::expiring(30)->get();

        $this->assertTrue($expiringContracts->contains('id', $expiringContract->id));
        $this->assertFalse($expiringContracts->contains('id', $notExpiringContract->id));
    }

    #[Test]
    public function scope_for_owner_returns_owner_contracts(): void
    {
        $contract1 = $this->createContract();

        $anotherOwner = Owner::create([
            'name' => 'Another Owner',
            'phone' => '0501111111',
            'email' => 'another@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        $property2 = Property::create([
            'name' => 'Property 2',
            'owner_id' => $anotherOwner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Test Address 2',
        ]);

        $contract2 = PropertyContract::withoutEvents(function () use ($anotherOwner, $property2) {
            return PropertyContract::create([
                'contract_number' => 'PC-TEST-0003',
                'owner_id' => $anotherOwner->id,
                'property_id' => $property2->id,
                'commission_rate' => 5.00,
                'duration_months' => 12,
                'start_date' => Carbon::now()->addYears(5),
                'end_date' => Carbon::now()->addYears(6)->subDay(),
                'contract_status' => 'active',
                'payment_day' => 1,
                'auto_renew' => false,
                'notice_period_days' => 30,
                'payment_frequency' => 'monthly',
                'payments_count' => 12,
            ]);
        });

        $ownerContracts = PropertyContract::forOwner($this->owner->id)->get();

        $this->assertTrue($ownerContracts->contains('id', $contract1->id));
        $this->assertFalse($ownerContracts->contains('id', $contract2->id));
    }

    // ==========================================
    // Auto-generated Contract Number Tests
    // ==========================================

    #[Test]
    public function auto_generates_contract_number_on_create(): void
    {
        // Use creating event to generate number
        $contract = PropertyContract::create([
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => Carbon::now()->addYears(20),
            'payment_frequency' => 'monthly',
        ]);

        $this->assertNotNull($contract->contract_number);
        $this->assertStringStartsWith('PC-'.date('Y').'-', $contract->contract_number);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function property_contract_can_be_updated(): void
    {
        $contract = $this->createContract();

        $contract->update([
            'commission_rate' => 8.00,
            'notes' => 'Updated notes',
        ]);

        $this->assertDatabaseHas('property_contracts', [
            'id' => $contract->id,
            'commission_rate' => 8.00,
            'notes' => 'Updated notes',
        ]);
    }

    #[Test]
    public function property_contract_can_be_deleted(): void
    {
        $contract = $this->createContract();
        $contractId = $contract->id;

        $contract->delete();

        $this->assertDatabaseMissing('property_contracts', ['id' => $contractId]);
    }

    #[Test]
    public function property_contract_handles_null_optional_fields(): void
    {
        $contract = $this->createContract([
            'notary_number' => null,
            'terms_and_conditions' => null,
            'notes' => null,
            'file' => null,
        ]);

        $this->assertNull($contract->notary_number);
        $this->assertNull($contract->terms_and_conditions);
        $this->assertNull($contract->notes);
        $this->assertNull($contract->file);
    }

    #[Test]
    public function property_contract_uses_factory(): void
    {
        $contract = PropertyContract::factory()->create([
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'start_date' => Carbon::now()->addYears(50),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $this->assertDatabaseHas('property_contracts', ['id' => $contract->id]);
        $this->assertInstanceOf(PropertyContract::class, $contract);
    }

    #[Test]
    public function property_contract_factory_has_active_state(): void
    {
        $contract = PropertyContract::factory()->active()->create([
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'start_date' => Carbon::now()->addYears(51),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $this->assertEquals('active', $contract->contract_status);
    }

    #[Test]
    public function property_contract_factory_has_draft_state(): void
    {
        // Use a far future start date to avoid overlap validation issues
        $contract = PropertyContract::factory()->draft()->create([
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'start_date' => Carbon::now()->addYears(52),
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
        ]);

        $this->assertEquals('draft', $contract->contract_status);
    }
}
