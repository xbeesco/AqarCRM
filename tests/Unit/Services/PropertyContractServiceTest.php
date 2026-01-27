<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use App\Services\PropertyContractService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PropertyContractServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PropertyContractService $service;

    protected Owner $owner;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected PropertyStatus $managedStatus;

    protected PropertyStatus $availableStatus;

    protected Location $location;

    protected Property $property;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(PropertyContractService::class);
        $this->createRequiredLookupData();
    }

    protected function createRequiredLookupData(): void
    {
        // Create property type
        $this->propertyType = PropertyType::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'عمارة سكنية',
                'name_en' => 'Residential Building',
                'slug' => 'residential-building',
                'is_active' => true,
            ]
        );

        // Create property statuses
        $this->availableStatus = PropertyStatus::firstOrCreate(
            ['slug' => 'available'],
            [
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'slug' => 'available',
                'is_active' => true,
            ]
        );

        $this->managedStatus = PropertyStatus::firstOrCreate(
            ['slug' => 'managed'],
            [
                'name_ar' => 'مدار',
                'name_en' => 'Managed',
                'slug' => 'managed',
                'is_active' => true,
            ]
        );

        $this->propertyStatus = $this->availableStatus;

        // Create location
        $this->location = Location::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Test Location',
                'name_ar' => 'موقع اختبار',
                'name_en' => 'Test Location',
                'level' => 1,
                'is_active' => true,
            ]
        );

        // Create unit type
        UnitType::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'slug' => 'apartment',
                'is_active' => true,
            ]
        );

        // Create admin user
        $this->admin = User::factory()->create([
            'type' => UserType::SUPER_ADMIN->value,
        ]);

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
        return PropertyContract::withoutEvents(function () use ($attributes) {
            $startDate = $attributes['start_date'] ?? Carbon::now()->addYears(10);
            $durationMonths = $attributes['duration_months'] ?? 12;
            $paymentFrequency = $attributes['payment_frequency'] ?? 'monthly';

            if (! isset($attributes['end_date'])) {
                $endDate = Carbon::parse($startDate)->addMonths($durationMonths)->subDay();
            } else {
                $endDate = $attributes['end_date'];
            }

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
    // calculatePaymentsCount Static Method Tests
    // ==========================================

    #[Test]
    public function calculates_payments_count_for_monthly_frequency(): void
    {
        $this->assertEquals(12, PropertyContractService::calculatePaymentsCount(12, 'monthly'));
        $this->assertEquals(6, PropertyContractService::calculatePaymentsCount(6, 'monthly'));
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(1, 'monthly'));
    }

    #[Test]
    public function calculates_payments_count_for_quarterly_frequency(): void
    {
        $this->assertEquals(4, PropertyContractService::calculatePaymentsCount(12, 'quarterly'));
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(6, 'quarterly'));
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(3, 'quarterly'));
    }

    #[Test]
    public function calculates_payments_count_for_semi_annual_frequency(): void
    {
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(12, 'semi_annually'));
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(6, 'semi_annually'));
        $this->assertEquals(3, PropertyContractService::calculatePaymentsCount(18, 'semi_annually'));
    }

    #[Test]
    public function calculates_payments_count_for_annual_frequency(): void
    {
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(12, 'annually'));
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(24, 'annually'));
        $this->assertEquals(3, PropertyContractService::calculatePaymentsCount(36, 'annually'));
    }

    #[Test]
    public function returns_invalid_division_for_incompatible_duration(): void
    {
        $this->assertEquals('Invalid division', PropertyContractService::calculatePaymentsCount(7, 'quarterly'));
        $this->assertEquals('Invalid division', PropertyContractService::calculatePaymentsCount(5, 'semi_annually'));
        $this->assertEquals('Invalid division', PropertyContractService::calculatePaymentsCount(11, 'annually'));
    }

    #[Test]
    public function returns_zero_for_zero_duration(): void
    {
        $this->assertEquals(0, PropertyContractService::calculatePaymentsCount(0, 'monthly'));
    }

    // ==========================================
    // isValidDuration Static Method Tests
    // ==========================================

    #[Test]
    public function validates_duration_for_monthly_frequency(): void
    {
        $this->assertTrue(PropertyContractService::isValidDuration(1, 'monthly'));
        $this->assertTrue(PropertyContractService::isValidDuration(7, 'monthly'));
        $this->assertTrue(PropertyContractService::isValidDuration(13, 'monthly'));
    }

    #[Test]
    public function validates_duration_for_quarterly_frequency(): void
    {
        $this->assertTrue(PropertyContractService::isValidDuration(3, 'quarterly'));
        $this->assertTrue(PropertyContractService::isValidDuration(6, 'quarterly'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'quarterly'));

        $this->assertFalse(PropertyContractService::isValidDuration(1, 'quarterly'));
        $this->assertFalse(PropertyContractService::isValidDuration(7, 'quarterly'));
        $this->assertFalse(PropertyContractService::isValidDuration(10, 'quarterly'));
    }

    #[Test]
    public function validates_duration_for_semi_annual_frequency(): void
    {
        $this->assertTrue(PropertyContractService::isValidDuration(6, 'semi_annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'semi_annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(18, 'semi_annually'));

        $this->assertFalse(PropertyContractService::isValidDuration(3, 'semi_annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(7, 'semi_annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(11, 'semi_annually'));
    }

    #[Test]
    public function validates_duration_for_annual_frequency(): void
    {
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(24, 'annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(36, 'annually'));

        $this->assertFalse(PropertyContractService::isValidDuration(6, 'annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(11, 'annually'));
        $this->assertFalse(PropertyContractService::isValidDuration(18, 'annually'));
    }

    // ==========================================
    // getMonthsPerPayment Static Method Tests
    // ==========================================

    #[Test]
    public function returns_correct_months_per_payment(): void
    {
        $this->assertEquals(1, PropertyContractService::getMonthsPerPayment('monthly'));
        $this->assertEquals(3, PropertyContractService::getMonthsPerPayment('quarterly'));
        $this->assertEquals(6, PropertyContractService::getMonthsPerPayment('semi_annually'));
        $this->assertEquals(12, PropertyContractService::getMonthsPerPayment('annually'));
    }

    #[Test]
    public function returns_default_for_unknown_frequency(): void
    {
        $this->assertEquals(1, PropertyContractService::getMonthsPerPayment('unknown'));
        $this->assertEquals(1, PropertyContractService::getMonthsPerPayment('invalid'));
    }

    // ==========================================
    // getExpiringContracts Method Tests
    // ==========================================

    #[Test]
    public function gets_contracts_expiring_within_days(): void
    {
        // Create an expiring contract
        $expiringContract = $this->createContract([
            'contract_status' => 'active',
            'start_date' => Carbon::now()->subMonths(11)->subDays(15),
            'end_date' => Carbon::now()->addDays(15),
        ]);

        // Create a non-expiring contract
        $property2 = Property::create([
            'name' => 'Property 2',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Test Address 2',
        ]);

        PropertyContract::withoutEvents(function () use ($property2) {
            return PropertyContract::create([
                'contract_number' => 'PC-TEST-NONEXP',
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

        $expiringContracts = $this->service->getExpiringContracts(30);

        $this->assertTrue($expiringContracts->contains('id', $expiringContract->id));
        $this->assertCount(1, $expiringContracts);
    }

    #[Test]
    public function returns_empty_collection_when_no_expiring_contracts(): void
    {
        // Only create a non-expiring contract
        $this->createContract([
            'contract_status' => 'active',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addYears(2),
        ]);

        $expiringContracts = $this->service->getExpiringContracts(30);

        $this->assertCount(0, $expiringContracts);
    }

    // ==========================================
    // getOwnerPortfolio Method Tests
    // ==========================================

    #[Test]
    public function gets_owner_portfolio_with_contracts(): void
    {
        $this->createContract();

        $portfolio = $this->service->getOwnerPortfolio($this->owner->id);

        $this->assertArrayHasKey('total_contracts', $portfolio);
        $this->assertArrayHasKey('active_contracts', $portfolio);
        $this->assertArrayHasKey('total_commission_rate', $portfolio);
        $this->assertArrayHasKey('contracts', $portfolio);

        $this->assertEquals(1, $portfolio['total_contracts']);
    }

    #[Test]
    public function returns_empty_portfolio_for_owner_without_contracts(): void
    {
        $newOwner = Owner::create([
            'name' => 'New Owner',
            'phone' => '0501111111',
            'email' => 'new@test.com',
            'password' => bcrypt('password'),
            'type' => UserType::OWNER->value,
        ]);

        $portfolio = $this->service->getOwnerPortfolio($newOwner->id);

        $this->assertEquals(0, $portfolio['total_contracts']);
        $this->assertEquals(0, $portfolio['active_contracts']);
    }

    // ==========================================
    // generatePaymentSchedules Method Tests
    // ==========================================

    #[Test]
    public function generates_payment_schedules_for_contract(): void
    {
        $contract = $this->createContract();

        // Add units to property
        Unit::create([
            'name' => 'Unit 1',
            'property_id' => $this->property->id,
            'unit_type_id' => 1,
            'rent_price' => 3000,
            'floor_number' => 1,
        ]);

        Unit::create([
            'name' => 'Unit 2',
            'property_id' => $this->property->id,
            'unit_type_id' => 1,
            'rent_price' => 3500,
            'floor_number' => 1,
        ]);

        $schedules = $this->service->generatePaymentSchedules($contract->id);

        $this->assertIsArray($schedules);
        $this->assertCount(2, $schedules);

        foreach ($schedules as $schedule) {
            $this->assertArrayHasKey('unit_id', $schedule);
            $this->assertArrayHasKey('monthly_commission', $schedule);
            $this->assertArrayHasKey('payment_day', $schedule);
        }
    }

    #[Test]
    public function returns_empty_schedules_for_property_without_units(): void
    {
        $contract = $this->createContract();

        $schedules = $this->service->generatePaymentSchedules($contract->id);

        $this->assertIsArray($schedules);
        $this->assertCount(0, $schedules);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function handles_contract_with_nullable_fields(): void
    {
        $contract = $this->createContract([
            'notary_number' => null,
            'terms_and_conditions' => null,
            'notes' => null,
        ]);

        $portfolio = $this->service->getOwnerPortfolio($this->owner->id);

        $this->assertEquals(1, $portfolio['total_contracts']);
    }
}
