<?php

namespace Tests\Feature\Financial;

use App\Enums\PaymentStatus;
use App\Enums\UserType;
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
use App\Services\CollectionPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CollectionPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected UnitType $unitType;

    protected User $tenant;

    protected User $owner;

    protected Property $property;

    protected Unit $unit;

    protected UnitContract $contract;

    protected CollectionPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time to ensure consistent behavior across tests
        Carbon::setTestNow(Carbon::now());

        // Clear cache for settings
        Cache::flush();

        // Create required reference data
        $this->createDependencies();

        // Get the service
        $this->service = app(CollectionPaymentService::class);
    }

    protected function tearDown(): void
    {
        // Reset Carbon test time
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createDependencies(): void
    {
        // Use existing lookup data seeded by TestCase::seedLookupData()
        $this->location = Location::first();
        $this->propertyType = PropertyType::first();
        $this->propertyStatus = PropertyStatus::first();
        $this->unitType = UnitType::first();

        // Set default payment settings
        Setting::set('payment_due_days', 7);
        Setting::set('allowed_delay_days', 0);

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

        // Create unit
        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
        ]);

        // Create contract with draft status to avoid auto-generating payments
        $startDate = Carbon::now()->subMonths(1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(12)->subDay();

        $this->contract = UnitContract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'contract_status' => 'draft',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_months' => 12,
        ]);
    }

    /**
     * Helper method to create a collection payment
     */
    protected function createPayment(array $overrides = []): CollectionPayment
    {
        $defaults = [
            'unit_contract_id' => $this->contract->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'tenant_id' => $this->tenant->id,
            'amount' => 5000.00,
            'late_fee' => 0.00,
            'due_date_start' => now(),
            'due_date_end' => now()->addMonth(),
        ];

        return CollectionPayment::create(array_merge($defaults, $overrides));
    }

    public function test_collection_payment_has_required_fields(): void
    {
        $payment = $this->createPayment([
            'amount' => 5000.00,
            'late_fee' => 100.00,
        ]);

        $this->assertInstanceOf(CollectionPayment::class, $payment);
        $this->assertEquals(5000.00, (float) $payment->amount);
        $this->assertEquals(5100.00, (float) $payment->total_amount);
        $this->assertNotNull($payment->payment_number);
    }

    public function test_payment_number_generation(): void
    {
        $payment = $this->createPayment();

        // Format: COL-YYYY-NNNNNN (e.g., COL-2026-000001)
        $this->assertStringStartsWith('COL-'.date('Y'), $payment->payment_number);
        // Format: COL-YYYY-NNNNNN = 15 characters
        $this->assertGreaterThanOrEqual(15, strlen($payment->payment_number));
    }

    public function test_late_fee_calculation(): void
    {
        // Set up the late fee rate
        Cache::flush();
        Setting::set('late_fee_daily_rate', 0.05);

        $payment = $this->createPayment([
            'amount' => 5000.00,
            'due_date_start' => now()->subDays(15),
            'due_date_end' => now()->subDays(10),
            'collection_date' => null,
        ]);

        // The payment status should be OVERDUE (computed dynamically)
        // Since due_date_start is 15 days ago and payment_due_days is 7
        $this->assertEquals(PaymentStatus::OVERDUE, $payment->payment_status);

        // Check using the service method
        $this->assertTrue($this->service->isOverdue($payment));
        $this->assertEquals(10, $this->service->getDaysOverdue($payment));
    }

    public function test_payment_processing(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(3),
        ]);

        // Use the service to process payment
        $result = $this->service->processPayment(
            $payment,
            null,
            now()->toDateString(),
            'REF123'
        );

        $this->assertTrue($result);

        $payment->refresh();

        // After processing, payment_status should be COLLECTED (computed dynamically)
        $this->assertEquals(PaymentStatus::COLLECTED, $payment->payment_status);
        $this->assertNotNull($payment->receipt_number);
        $this->assertEquals('REF123', $payment->payment_reference);
    }

    public function test_total_amount_calculation(): void
    {
        $payment = $this->createPayment([
            'amount' => 5000.00,
            'late_fee' => 350.00,
        ]);

        $this->assertEquals(5350.00, (float) $payment->total_amount);
    }

    public function test_payment_relationships(): void
    {
        $payment = $this->createPayment();

        $this->assertInstanceOf(Property::class, $payment->property);
        $this->assertInstanceOf(Unit::class, $payment->unit);
        $this->assertInstanceOf(User::class, $payment->tenant);
        $this->assertInstanceOf(UnitContract::class, $payment->unitContract);

        // payment_status is now a computed accessor that returns PaymentStatus enum
        $this->assertInstanceOf(PaymentStatus::class, $payment->payment_status);
    }

    public function test_payment_status_is_computed_dynamically(): void
    {
        // Test COLLECTED status
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
        ]);
        $this->assertEquals(PaymentStatus::COLLECTED, $collectedPayment->payment_status);

        // Test POSTPONED status
        $postponedPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(5),
            'delay_duration' => 7,
            'delay_reason' => 'طلب المستأجر',
        ]);
        $this->assertEquals(PaymentStatus::POSTPONED, $postponedPayment->payment_status);

        // Test OVERDUE status
        Cache::flush();
        Setting::set('payment_due_days', 7);

        $overduePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(15),
            'delay_duration' => null,
        ]);
        $this->assertEquals(PaymentStatus::OVERDUE, $overduePayment->payment_status);

        // Test DUE status
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->startOfDay(),
            'delay_duration' => null,
        ]);
        $this->assertEquals(PaymentStatus::DUE, $duePayment->payment_status);

        // Test UPCOMING status
        $upcomingPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(10),
            'delay_duration' => null,
        ]);
        $this->assertEquals(PaymentStatus::UPCOMING, $upcomingPayment->payment_status);
    }

    public function test_payment_status_label_returns_arabic_text(): void
    {
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
        ]);
        $this->assertEquals('محصل', $collectedPayment->payment_status_label);

        $postponedPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(5),
            'delay_duration' => 7,
        ]);
        $this->assertEquals('مؤجل', $postponedPayment->payment_status_label);

        $upcomingPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(10),
            'delay_duration' => null,
        ]);
        $this->assertEquals('قادم', $upcomingPayment->payment_status_label);
    }

    public function test_is_overdue_method_delegates_to_service(): void
    {
        $overduePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(10),
            'due_date_end' => now()->subDays(5),
        ]);

        // The model's isOverdue() method delegates to the service
        $this->assertTrue($overduePayment->isOverdue());

        $notOverduePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(5),
            'due_date_end' => now()->addDays(10),
        ]);

        $this->assertFalse($notOverduePayment->isOverdue());
    }

    public function test_payment_scopes_work_correctly(): void
    {
        // Create payments with different statuses
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => now()->subDays(5),
        ]);

        $upcomingPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(10),
        ]);

        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->startOfDay(),
            'delay_duration' => null,
        ]);

        // Test collected scope
        $collectedPayments = CollectionPayment::collectedPayments()->get();
        $this->assertTrue($collectedPayments->contains('id', $collectedPayment->id));
        $this->assertFalse($collectedPayments->contains('id', $upcomingPayment->id));

        // Test upcoming scope
        $upcomingPayments = CollectionPayment::upcomingPayments()->get();
        $this->assertTrue($upcomingPayments->contains('id', $upcomingPayment->id));
        $this->assertFalse($upcomingPayments->contains('id', $collectedPayment->id));

        // Test due for collection scope
        $dueForCollection = CollectionPayment::dueForCollection()->get();
        $this->assertTrue($dueForCollection->contains('id', $duePayment->id));
        $this->assertFalse($dueForCollection->contains('id', $upcomingPayment->id));
    }
}
