<?php

namespace Tests\Unit\Services;

use App\Enums\UserType;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyContract;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\SupplyPayment;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use App\Services\ExpenseValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExpenseValidationService $service;

    protected Owner $owner;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected Location $location;

    protected UnitType $unitType;

    protected Property $property;

    protected Unit $unit;

    protected PropertyContract $propertyContract;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ExpenseValidationService::class);

        Carbon::setTestNow(Carbon::create(2026, 1, 15, 12, 0, 0));

        $this->createDependencies();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    protected function createDependencies(): void
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

        // Create property status
        $this->propertyStatus = PropertyStatus::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'متاح',
                'name_en' => 'Available',
                'slug' => 'available',
                'is_active' => true,
            ]
        );

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
        $this->unitType = UnitType::firstOrCreate(
            ['id' => 1],
            [
                'name_ar' => 'شقة',
                'name_en' => 'Apartment',
                'slug' => 'apartment',
                'is_active' => true,
            ]
        );

        // Create admin
        $this->admin = User::factory()->create([
            'type' => UserType::ADMIN->value,
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

        // Create unit
        $this->unit = Unit::create([
            'name' => 'Unit 101',
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
            'rent_price' => 3000,
            'floor_number' => 1,
        ]);

        // Create property contract (draft to avoid auto-generation)
        $this->propertyContract = PropertyContract::create([
            'owner_id' => $this->owner->id,
            'property_id' => $this->property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => Carbon::now()->subMonths(6),
            'end_date' => Carbon::now()->addMonths(6),
            'contract_status' => 'draft',
            'payment_frequency' => 'monthly',
            'created_by' => $this->admin->id,
        ]);
    }

    protected function createSupplyPayment(array $overrides = []): SupplyPayment
    {
        $defaults = [
            'property_contract_id' => $this->propertyContract->id,
            'owner_id' => $this->owner->id,
            'gross_amount' => 10000.00,
            'commission_amount' => 500.00,
            'commission_rate' => 5.00,
            'net_amount' => 9500.00,
            'due_date' => Carbon::now()->endOfMonth(),
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
     * Check if we're using MySQL (required for JSON_EXTRACT).
     */
    protected function isUsingMysql(): bool
    {
        return DB::connection()->getDriverName() === 'mysql';
    }

    // ==========================================
    // validateExpenseDate Tests
    // ==========================================

    #[Test]
    public function validate_expense_date_returns_null_when_supply_payment_exists_and_unpaid(): void
    {
        if (! $this->isUsingMysql()) {
            // When using SQLite, JSON_EXTRACT doesn't work properly,
            // so the query returns null which means no error (allows expense)
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        $result = $this->service->validateExpenseDate($this->property->id, '2026-01-15');

        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_date_returns_error_when_no_supply_payment_exists(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        // No supply payment created

        $result = $this->service->validateExpenseDate($this->property->id, '2026-01-15');

        $this->assertNotNull($result);
        $this->assertStringContainsString('لا توجد دفعة مالك', $result);
    }

    #[Test]
    public function validate_expense_date_returns_error_when_supply_payment_is_already_paid(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => Carbon::now()->subDays(5),
        ]);

        $result = $this->service->validateExpenseDate($this->property->id, '2026-01-15');

        $this->assertNotNull($result);
        $this->assertStringContainsString('تم توريدها بالفعل', $result);
    }

    #[Test]
    public function validate_expense_date_returns_null_for_null_property_id(): void
    {
        $result = $this->service->validateExpenseDate(null, '2026-01-15');

        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_date_returns_null_for_null_date(): void
    {
        $result = $this->service->validateExpenseDate($this->property->id, null);

        $this->assertNull($result);
    }

    // ==========================================
    // validateExpenseDateForUnit Tests
    // ==========================================

    #[Test]
    public function validate_expense_date_for_unit_uses_property_from_unit(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        $result = $this->service->validateExpenseDateForUnit($this->unit->id, '2026-01-15');

        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_date_for_unit_returns_error_for_nonexistent_unit(): void
    {
        $result = $this->service->validateExpenseDateForUnit(99999, '2026-01-15');

        $this->assertNotNull($result);
        $this->assertStringContainsString('الوحدة المختارة غير موجودة', $result);
    }

    #[Test]
    public function validate_expense_date_for_unit_returns_null_for_null_unit_id(): void
    {
        $result = $this->service->validateExpenseDateForUnit(null, '2026-01-15');

        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_date_for_unit_returns_null_for_null_date(): void
    {
        $result = $this->service->validateExpenseDateForUnit($this->unit->id, null);

        $this->assertNull($result);
    }

    // ==========================================
    // validateExpense Tests
    // ==========================================

    #[Test]
    public function validate_expense_for_property_type(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        $result = $this->service->validateExpense('property', $this->property->id, null, '2026-01-15');

        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_for_unit_type(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        $result = $this->service->validateExpense('unit', $this->property->id, $this->unit->id, '2026-01-15');

        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_returns_error_when_unit_type_without_unit_id(): void
    {
        $result = $this->service->validateExpense('unit', $this->property->id, null, '2026-01-15');

        $this->assertNotNull($result);
        $this->assertStringContainsString('يجب اختيار وحدة', $result);
    }

    #[Test]
    public function validate_expense_returns_error_for_invalid_expense_type(): void
    {
        $result = $this->service->validateExpense('invalid_type', $this->property->id, null, '2026-01-15');

        $this->assertNotNull($result);
        $this->assertStringContainsString('نوع النفقة غير صحيح', $result);
    }

    #[Test]
    public function validate_expense_returns_null_for_null_expense_for(): void
    {
        $result = $this->service->validateExpense(null, $this->property->id, null, '2026-01-15');

        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_returns_null_for_null_date(): void
    {
        $result = $this->service->validateExpense('property', $this->property->id, null, null);

        $this->assertNull($result);
    }

    // ==========================================
    // canEditExpense Tests
    // ==========================================

    #[Test]
    public function can_edit_expense_returns_false_for_null_expense(): void
    {
        $result = $this->service->canEditExpense(null);

        $this->assertFalse($result);
    }

    #[Test]
    public function can_edit_expense_returns_true_when_supply_payment_exists_and_unpaid(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        $expense = (object) [
            'id' => 1,
            'subject_type' => 'App\Models\Property',
            'subject_id' => $this->property->id,
            'date' => '2026-01-15',
        ];

        $result = $this->service->canEditExpense($expense);

        $this->assertTrue($result);
    }

    #[Test]
    public function can_edit_expense_returns_false_when_supply_payment_is_paid(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => Carbon::now()->subDays(5),
        ]);

        $expense = (object) [
            'id' => 1,
            'subject_type' => 'App\Models\Property',
            'subject_id' => $this->property->id,
            'date' => '2026-01-15',
        ];

        $result = $this->service->canEditExpense($expense);

        $this->assertFalse($result);
    }

    #[Test]
    public function can_edit_expense_works_for_unit_expenses(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        $expense = (object) [
            'id' => 1,
            'subject_type' => 'App\Models\Unit',
            'subject_id' => $this->unit->id,
            'date' => '2026-01-15',
        ];

        $result = $this->service->canEditExpense($expense);

        $this->assertTrue($result);
    }

    #[Test]
    public function can_edit_expense_returns_true_for_unknown_subject_type(): void
    {
        $expense = (object) [
            'id' => 1,
            'subject_type' => 'App\Models\Unknown',
            'subject_id' => 1,
            'date' => '2026-01-15',
        ];

        $result = $this->service->canEditExpense($expense);

        $this->assertTrue($result);
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function validate_expense_date_works_with_boundary_dates(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        // Test start of period
        $result = $this->service->validateExpenseDate($this->property->id, '2026-01-01');
        $this->assertNull($result);

        // Test end of period
        $result = $this->service->validateExpenseDate($this->property->id, '2026-01-31');
        $this->assertNull($result);
    }

    #[Test]
    public function validate_expense_date_returns_error_for_date_outside_period(): void
    {
        if (! $this->isUsingMysql()) {
            $this->markTestSkipped('This test requires MySQL for JSON_EXTRACT support.');
        }

        $this->createSupplyPayment([
            'invoice_details' => [
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
            ],
            'paid_date' => null,
        ]);

        // Test date before period
        $result = $this->service->validateExpenseDate($this->property->id, '2025-12-31');
        $this->assertNotNull($result);

        // Test date after period
        $result = $this->service->validateExpenseDate($this->property->id, '2026-02-01');
        $this->assertNotNull($result);
    }
}
