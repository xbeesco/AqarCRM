<?php

namespace Tests\Unit\Models;

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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectionPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Location $location;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected UnitType $unitType;

    protected User $tenant;

    protected Property $property;

    protected Unit $unit;

    protected UnitContract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        // Freeze time to ensure consistent behavior across tests
        Carbon::setTestNow(Carbon::now());

        // Clear cache for settings
        \Illuminate\Support\Facades\Cache::flush();

        // Create required reference data
        $this->location = Location::create([
            'name' => 'Test Location',
            'code' => 'TEST',
            'level' => 1,
            'is_active' => true,
        ]);

        $this->propertyType = PropertyType::create([
            'name_ar' => 'شقة',
            'name_en' => 'Apartment',
            'slug' => 'apartment',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->propertyStatus = PropertyStatus::create([
            'name_ar' => 'متاح',
            'name_en' => 'Available',
            'slug' => 'available',
            'color' => 'green',
            'is_available' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->unitType = UnitType::create([
            'name_ar' => 'شقة سكنية',
            'name_en' => 'Residential Apartment',
            'slug' => 'residential-apartment',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Set default payment_due_days setting
        Setting::set('payment_due_days', 7);

        // Create a reusable tenant, property, unit, and contract for all tests
        $this->tenant = $this->createTenant();
        $this->property = $this->createPropertyWithOwner();
        $this->unit = Unit::factory()->create([
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
        ]);
        // Create with draft status to avoid auto-generating payments
        $this->contract = $this->createDraftContract();
    }

    protected function tearDown(): void
    {
        // Reset Carbon test time
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * Helper method to create a tenant user
     */
    protected function createTenant(): User
    {
        return User::factory()->create([
            'type' => UserType::TENANT->value,
        ]);
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
     * Helper method to create a property with owner
     */
    protected function createPropertyWithOwner(): Property
    {
        $owner = $this->createOwner();

        return Property::create([
            'name' => 'Test Property '.uniqid(),
            'owner_id' => $owner->id,
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
     * Helper method to create a unit with property
     */
    protected function createUnitWithProperty(): Unit
    {
        $property = $this->createPropertyWithOwner();

        return Unit::factory()->create([
            'property_id' => $property->id,
            'unit_type_id' => $this->unitType->id,
        ]);
    }

    /**
     * Helper method to create a draft contract (no auto-generated payments)
     */
    protected function createDraftContract(): UnitContract
    {
        $startDate = Carbon::now()->subMonths(1)->startOfDay();
        $endDate = $startDate->copy()->addMonths(12)->subDay();

        return UnitContract::factory()->create([
            'tenant_id' => $this->tenant->id,
            'unit_id' => $this->unit->id,
            'property_id' => $this->property->id,
            'contract_status' => 'draft', // Draft status - no auto payments
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_months' => 12,
        ]);
    }

    /**
     * Helper method to create a collection payment using the shared contract
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

    // ==========================================
    // Payment Status Accessor Tests - Dynamic Status
    // ==========================================

    public function test_payment_status_returns_collected_when_collection_date_exists(): void
    {
        $payment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => now()->subDays(5),
        ]);

        $this->assertEquals(PaymentStatus::COLLECTED, $payment->payment_status);
    }

    public function test_payment_status_returns_postponed_when_postponed_fields_set(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(5),
            'delay_duration' => 7,
            'delay_reason' => 'طلب المستأجر',
        ]);

        $this->assertEquals(PaymentStatus::POSTPONED, $payment->payment_status);
    }

    public function test_payment_status_returns_overdue_when_due_date_passed_and_not_paid(): void
    {
        // Clear cache to ensure fresh setting value
        \Illuminate\Support\Facades\Cache::flush();
        Setting::set('payment_due_days', 7);

        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(15), // More than 7 days in the past
            'delay_duration' => null,
        ]);

        $this->assertEquals(PaymentStatus::OVERDUE, $payment->payment_status);
    }

    public function test_payment_status_returns_due_when_due_date_today(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->startOfDay(),
            'delay_duration' => null,
        ]);

        $this->assertEquals(PaymentStatus::DUE, $payment->payment_status);
    }

    public function test_payment_status_returns_upcoming_when_due_date_in_future(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(10),
            'delay_duration' => null,
        ]);

        $this->assertEquals(PaymentStatus::UPCOMING, $payment->payment_status);
    }

    // ==========================================
    // Payment Status Label Accessor Tests
    // ==========================================

    public function test_payment_status_label_returns_arabic_text_for_collected(): void
    {
        $payment = $this->createPayment([
            'collection_date' => now(),
        ]);

        $this->assertEquals('محصل', $payment->payment_status_label);
    }

    public function test_payment_status_label_returns_arabic_text_for_overdue(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        Setting::set('payment_due_days', 7);

        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(15),
            'delay_duration' => null,
        ]);

        $this->assertEquals('متأخر', $payment->payment_status_label);
    }

    public function test_payment_status_label_returns_arabic_text_for_due(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->startOfDay(),
            'delay_duration' => null,
        ]);

        $this->assertEquals('مستحق', $payment->payment_status_label);
    }

    public function test_payment_status_label_returns_arabic_text_for_postponed(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(5),
            'delay_duration' => 7,
        ]);

        $this->assertEquals('مؤجل', $payment->payment_status_label);
    }

    public function test_payment_status_label_returns_arabic_text_for_upcoming(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(10),
            'delay_duration' => null,
        ]);

        $this->assertEquals('قادم', $payment->payment_status_label);
    }

    // ==========================================
    // Payment Status Enum Accessor Tests
    // ==========================================

    public function test_payment_status_enum_returns_correct_enum_value(): void
    {
        // Test COLLECTED
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
        ]);
        $this->assertInstanceOf(PaymentStatus::class, $collectedPayment->payment_status);
        $this->assertEquals('collected', $collectedPayment->payment_status->value);
    }

    public function test_payment_status_enum_returns_correct_enum_value_for_upcoming(): void
    {
        // Test UPCOMING
        $upcomingPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(10),
            'delay_duration' => null,
        ]);
        $this->assertInstanceOf(PaymentStatus::class, $upcomingPayment->payment_status);
        $this->assertEquals('upcoming', $upcomingPayment->payment_status->value);
    }

    // ==========================================
    // Scopes Tests
    // ==========================================

    public function test_due_for_collection_scope_excludes_collected_payments(): void
    {
        // Use startOfDay to ensure consistent date comparisons
        $today = now()->startOfDay();

        // Collected payment (should be excluded)
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => $today->copy()->subDays(1),
        ]);

        // Due payment (should be included) - due_date_start <= today
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today,
            'delay_duration' => null,
        ]);

        $dueForCollection = CollectionPayment::dueForCollection()->get();

        // duePayment should be included, collectedPayment should be excluded
        $this->assertTrue($dueForCollection->contains('id', $duePayment->id));
        $this->assertFalse($dueForCollection->contains('id', $collectedPayment->id));
    }

    public function test_due_for_collection_scope_excludes_upcoming_payments(): void
    {
        // Use startOfDay to ensure consistent date comparisons
        $today = now()->startOfDay();

        // Upcoming payment (should be excluded) - due_date_start > today
        $upcomingPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today->copy()->addDays(10),
        ]);

        // Due payment (should be included) - due_date_start <= today
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today,
            'delay_duration' => null,
        ]);

        $dueForCollection = CollectionPayment::dueForCollection()->get();

        // duePayment should be included, upcomingPayment should be excluded
        $this->assertTrue($dueForCollection->contains('id', $duePayment->id));
        $this->assertFalse($dueForCollection->contains('id', $upcomingPayment->id));
    }

    public function test_due_for_collection_scope_excludes_postponed_payments(): void
    {
        // Use startOfDay to ensure consistent date comparisons
        $today = now()->startOfDay();

        // Postponed payment (should be excluded due to delay_duration)
        $postponedPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today->copy()->subDays(1),
            'delay_duration' => 7,
        ]);

        // Due payment (should be included)
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today,
            'delay_duration' => null,
        ]);

        $dueForCollection = CollectionPayment::dueForCollection()->get();

        // duePayment should be included, postponedPayment should be excluded
        $this->assertTrue($dueForCollection->contains('id', $duePayment->id));
        $this->assertFalse($dueForCollection->contains('id', $postponedPayment->id));
    }

    public function test_postponed_payments_scope_returns_only_postponed(): void
    {
        // Postponed payment (should be included)
        $postponedPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->subDays(1),
            'delay_duration' => 7,
            'delay_reason' => 'طلب المستأجر',
        ]);

        // Due payment (should be excluded)
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now(),
            'delay_duration' => null,
        ]);

        // Collected payment (should be excluded)
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => now()->subDays(5),
        ]);

        $postponedPayments = CollectionPayment::postponedPayments()->get();

        $this->assertTrue($postponedPayments->contains('id', $postponedPayment->id));
        $this->assertFalse($postponedPayments->contains('id', $duePayment->id));
        $this->assertFalse($postponedPayments->contains('id', $collectedPayment->id));
    }

    public function test_overdue_payments_scope_returns_only_overdue(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        Setting::set('payment_due_days', 7);

        // Use startOfDay for consistent comparisons
        $today = now()->startOfDay();

        // Overdue payment (should be included) - due_date_start < today - 7 days
        $overduePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today->copy()->subDays(15),
            'delay_duration' => null,
        ]);

        // Due payment within grace period (should be excluded)
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today->copy()->subDays(3),
            'delay_duration' => null,
        ]);

        // Collected payment (should be excluded)
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => $today->copy()->subDays(20),
        ]);

        $overduePayments = CollectionPayment::overduePayments()->get();

        $this->assertTrue($overduePayments->contains('id', $overduePayment->id));
        $this->assertFalse($overduePayments->contains('id', $duePayment->id));
        $this->assertFalse($overduePayments->contains('id', $collectedPayment->id));
    }

    public function test_collected_payments_scope_returns_only_collected(): void
    {
        // Collected payment (should be included)
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => now()->subDays(5),
        ]);

        // Uncollected payment (should be excluded)
        $uncollectedPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now(),
        ]);

        $collectedPayments = CollectionPayment::collectedPayments()->get();

        $this->assertTrue($collectedPayments->contains('id', $collectedPayment->id));
        $this->assertFalse($collectedPayments->contains('id', $uncollectedPayment->id));
    }

    public function test_upcoming_payments_scope_returns_only_upcoming(): void
    {
        // Use startOfDay for consistent comparisons
        $today = now()->startOfDay();

        // Upcoming payment (should be included) - due_date_start > today
        $upcomingPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today->copy()->addDays(10),
        ]);

        // Due payment (should be excluded) - due_date_start <= today
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today, // exactly today, not > today
        ]);

        // Collected with future due date (still excluded because collected)
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => $today->copy()->addDays(5),
        ]);

        $upcomingPayments = CollectionPayment::upcomingPayments()->get();

        $this->assertTrue($upcomingPayments->contains('id', $upcomingPayment->id));
        $this->assertFalse($upcomingPayments->contains('id', $duePayment->id));
        $this->assertFalse($upcomingPayments->contains('id', $collectedPayment->id));
    }

    public function test_by_status_scope_filters_correctly(): void
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

        // Test filtering by COLLECTED status
        $collectedResult = CollectionPayment::byStatus(PaymentStatus::COLLECTED)->get();
        $this->assertTrue($collectedResult->contains('id', $collectedPayment->id));
        $this->assertFalse($collectedResult->contains('id', $upcomingPayment->id));

        // Test filtering by UPCOMING status
        $upcomingResult = CollectionPayment::byStatus(PaymentStatus::UPCOMING)->get();
        $this->assertTrue($upcomingResult->contains('id', $upcomingPayment->id));
        $this->assertFalse($upcomingResult->contains('id', $collectedPayment->id));
    }

    public function test_by_statuses_scope_filters_multiple_statuses(): void
    {
        // Use startOfDay for consistent comparisons
        $today = now()->startOfDay();

        // Create payments with different statuses
        $collectedPayment = $this->createPayment([
            'collection_date' => now(),
            'due_date_start' => $today->copy()->subDays(5),
        ]);

        $upcomingPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today->copy()->addDays(10),
        ]);

        // Due payment: due_date_start = today (not in COLLECTED or UPCOMING)
        $duePayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => $today,
            'delay_duration' => null,
        ]);

        // Filter by COLLECTED and UPCOMING statuses
        $result = CollectionPayment::byStatuses([PaymentStatus::COLLECTED, PaymentStatus::UPCOMING])->get();

        $this->assertTrue($result->pluck('id')->contains($collectedPayment->id));
        $this->assertTrue($result->pluck('id')->contains($upcomingPayment->id));
        $this->assertFalse($result->pluck('id')->contains($duePayment->id));
    }

    public function test_paid_scope_returns_payments_with_collection_date(): void
    {
        // Paid payment (should be included)
        $paidPayment = $this->createPayment([
            'collection_date' => now(),
        ]);

        // Unpaid payment (should be excluded)
        $unpaidPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(5),
        ]);

        $paidPayments = CollectionPayment::paid()->get();

        $this->assertTrue($paidPayments->contains('id', $paidPayment->id));
        $this->assertFalse($paidPayments->contains('id', $unpaidPayment->id));
    }

    public function test_unpaid_scope_returns_payments_without_collection_date(): void
    {
        // Unpaid payment (should be included)
        $unpaidPayment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now()->addDays(5),
        ]);

        // Paid payment (should be excluded)
        $paidPayment = $this->createPayment([
            'collection_date' => now(),
        ]);

        $unpaidPayments = CollectionPayment::unpaid()->get();

        $this->assertTrue($unpaidPayments->contains('id', $unpaidPayment->id));
        $this->assertFalse($unpaidPayments->contains('id', $paidPayment->id));
    }

    // ==========================================
    // Deletion Prevention Tests
    // ==========================================

    public function test_deleting_payment_throws_exception(): void
    {
        $payment = $this->createPayment();

        // The model should prevent deletion by returning false in the deleting event
        $result = $payment->delete();

        // The delete should return false (not execute)
        $this->assertFalse($result);

        // The payment should still exist in the database
        $this->assertDatabaseHas('collection_payments', [
            'id' => $payment->id,
        ]);
    }

    public function test_cannot_delete_collected_payment(): void
    {
        $payment = $this->createPayment([
            'collection_date' => now(),
        ]);

        $result = $payment->delete();

        $this->assertFalse($result);
        $this->assertDatabaseHas('collection_payments', [
            'id' => $payment->id,
        ]);
    }

    public function test_cannot_delete_any_payment_via_model(): void
    {
        // Create multiple payments with different statuses using the shared contract
        $payments = [
            $this->createPayment([
                'collection_date' => null,
                'due_date_start' => now()->addDays(10),
            ]),
            $this->createPayment([
                'collection_date' => now(),
            ]),
            $this->createPayment([
                'delay_duration' => 7,
                'due_date_start' => now()->subDays(1),
            ]),
        ];

        foreach ($payments as $payment) {
            $result = $payment->delete();
            $this->assertFalse($result);
            $this->assertDatabaseHas('collection_payments', [
                'id' => $payment->id,
            ]);
        }
    }

    // ==========================================
    // Relationships Tests
    // ==========================================

    public function test_belongs_to_tenant_relationship(): void
    {
        $payment = $this->createPayment();

        $this->assertInstanceOf(User::class, $payment->tenant);
        $this->assertEquals($this->tenant->id, $payment->tenant->id);
    }

    public function test_belongs_to_unit_relationship(): void
    {
        $payment = $this->createPayment();

        $this->assertInstanceOf(Unit::class, $payment->unit);
        $this->assertEquals($this->unit->id, $payment->unit->id);
    }

    public function test_belongs_to_property_relationship(): void
    {
        $payment = $this->createPayment();

        $this->assertInstanceOf(Property::class, $payment->property);
        $this->assertEquals($this->property->id, $payment->property->id);
    }

    public function test_belongs_to_unit_contract_relationship(): void
    {
        $payment = $this->createPayment();

        $this->assertInstanceOf(UnitContract::class, $payment->unitContract);
        $this->assertEquals($this->contract->id, $payment->unitContract->id);
    }

    // ==========================================
    // Calculations Tests
    // ==========================================

    public function test_total_amount_includes_late_fee(): void
    {
        $payment = $this->createPayment([
            'amount' => 5000.00,
            'late_fee' => 150.00,
        ]);

        // The model automatically calculates total_amount on create
        $this->assertEquals(5150.00, (float) $payment->total_amount);
    }

    public function test_total_amount_updates_on_save(): void
    {
        $payment = $this->createPayment([
            'amount' => 5000.00,
            'late_fee' => 0.00,
        ]);

        $this->assertEquals(5000.00, (float) $payment->total_amount);

        // Update late_fee
        $payment->update([
            'late_fee' => 250.00,
        ]);

        $payment->refresh();
        $this->assertEquals(5250.00, (float) $payment->total_amount);
    }

    // ==========================================
    // Additional Tests for Edge Cases
    // ==========================================

    public function test_payment_with_zero_delay_duration_is_not_postponed(): void
    {
        $payment = $this->createPayment([
            'collection_date' => null,
            'due_date_start' => now(),
            'delay_duration' => 0, // Zero is not postponed
        ]);

        $this->assertNotEquals(PaymentStatus::POSTPONED, $payment->payment_status);
    }

    public function test_payment_number_is_auto_generated(): void
    {
        $payment = $this->createPayment();

        $this->assertNotNull($payment->payment_number);
        // Format: COL-YYYY-NNNNNN (e.g., COL-2026-000001)
        $this->assertStringStartsWith('COL-', $payment->payment_number);
    }

    public function test_month_year_is_auto_generated(): void
    {
        // Use a future date to avoid any date validation issues
        $dueDate = now()->addMonths(2)->startOfMonth()->addDays(14);
        $expectedMonthYear = $dueDate->format('Y-m');

        $payment = $this->createPayment([
            'due_date_start' => $dueDate,
        ]);

        $this->assertEquals($expectedMonthYear, $payment->month_year);
    }
}
