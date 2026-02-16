<?php

namespace Tests\Unit\Models;

use App\Enums\UserType;
use App\Models\Expense;
use App\Models\Location;
use App\Models\Owner;
use App\Models\Property;
use App\Models\PropertyStatus;
use App\Models\PropertyType;
use App\Models\Unit;
use App\Models\UnitType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    protected Owner $owner;

    protected PropertyType $propertyType;

    protected PropertyStatus $propertyStatus;

    protected Location $location;

    protected UnitType $unitType;

    protected Property $property;

    protected Unit $unit;

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

        // Create unit type
        $this->unitType = UnitType::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Apartment',
                'slug' => 'apartment',
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

        // Create unit
        $this->unit = Unit::create([
            'name' => 'Unit 101',
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
            'rent_price' => 3000,
            'floor_number' => 1,
        ]);
    }

    protected function createExpense(array $attributes = []): Expense
    {
        return Expense::create(array_merge([
            'desc' => 'Test Expense',
            'type' => 'maintenance',
            'cost' => 500.00,
            'date' => now(),
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ], $attributes));
    }

    // ==========================================
    // Basic Model Tests
    // ==========================================

    #[Test]
    public function expense_can_be_created(): void
    {
        $expense = $this->createExpense();

        $this->assertDatabaseHas('expenses', [
            'desc' => 'Test Expense',
            'type' => 'maintenance',
        ]);

        $this->assertInstanceOf(Expense::class, $expense);
    }

    #[Test]
    public function expense_has_fillable_attributes(): void
    {
        $expense = $this->createExpense([
            'desc' => 'Maintenance Work',
            'type' => 'government',
            'cost' => 1500.50,
            'date' => '2026-01-15',
        ]);

        $this->assertEquals('Maintenance Work', $expense->desc);
        $this->assertEquals('government', $expense->type);
        $this->assertEquals(1500.50, $expense->cost);
        $this->assertEquals('2026-01-15', $expense->date->format('Y-m-d'));
    }

    #[Test]
    public function expense_casts_attributes_correctly(): void
    {
        $expense = $this->createExpense([
            'cost' => '750.25',
            'date' => '2026-01-20',
            'docs' => [
                ['type' => 'invoice', 'file' => 'test.pdf'],
            ],
        ]);

        $this->assertIsString($expense->cost);
        $this->assertInstanceOf(Carbon::class, $expense->date);
        $this->assertIsArray($expense->docs);
    }

    // ==========================================
    // Polymorphic Relationship Tests
    // ==========================================

    #[Test]
    public function expense_can_belong_to_property(): void
    {
        $expense = $this->createExpense([
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->assertInstanceOf(Property::class, $expense->subject);
        $this->assertEquals($this->property->id, $expense->subject->id);
    }

    #[Test]
    public function expense_can_belong_to_unit(): void
    {
        $expense = $this->createExpense([
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $this->assertInstanceOf(Unit::class, $expense->subject);
        $this->assertEquals($this->unit->id, $expense->subject->id);
    }

    #[Test]
    public function property_has_many_expenses(): void
    {
        $this->createExpense([
            'desc' => 'Expense 1',
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->createExpense([
            'desc' => 'Expense 2',
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->property->refresh();

        $this->assertCount(2, $this->property->expenses);
    }

    #[Test]
    public function unit_has_many_expenses(): void
    {
        $this->createExpense([
            'desc' => 'Unit Expense 1',
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $this->createExpense([
            'desc' => 'Unit Expense 2',
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $this->unit->refresh();

        $this->assertCount(2, $this->unit->expenses);
    }

    // ==========================================
    // Accessor Tests
    // ==========================================

    #[Test]
    public function expense_has_type_name_accessor(): void
    {
        $expense = $this->createExpense(['type' => 'maintenance']);
        $this->assertEquals('صيانة', $expense->type_name);

        $expense2 = $this->createExpense(['type' => 'government']);
        $this->assertEquals('مصاريف حكومية', $expense2->type_name);

        $expense3 = $this->createExpense(['type' => 'other']);
        $this->assertEquals('أخرى', $expense3->type_name);
    }

    #[Test]
    public function expense_has_type_color_accessor(): void
    {
        $expense = $this->createExpense(['type' => 'maintenance']);
        $this->assertEquals('warning', $expense->type_color);

        $expense2 = $this->createExpense(['type' => 'government']);
        $this->assertEquals('danger', $expense2->type_color);

        $expense3 = $this->createExpense(['type' => 'utilities']);
        $this->assertEquals('info', $expense3->type_color);
    }

    #[Test]
    public function expense_has_docs_count_accessor(): void
    {
        $expense = $this->createExpense([
            'docs' => [
                ['type' => 'invoice', 'file' => 'doc1.pdf'],
                ['type' => 'receipt', 'file' => 'doc2.pdf'],
                ['type' => 'other', 'file' => 'doc3.pdf'],
            ],
        ]);

        $this->assertEquals(3, $expense->docs_count);
    }

    #[Test]
    public function expense_docs_count_returns_zero_when_no_docs(): void
    {
        $expense = $this->createExpense(['docs' => null]);

        $this->assertEquals(0, $expense->docs_count);
    }

    #[Test]
    public function expense_has_formatted_cost_accessor(): void
    {
        $expense = $this->createExpense(['cost' => 1500.50]);

        $this->assertEquals('1,500.50 ريال', $expense->formatted_cost);
    }

    #[Test]
    public function expense_has_subject_name_accessor_for_property(): void
    {
        $expense = $this->createExpense([
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->assertStringContainsString('العقار:', $expense->subject_name);
        $this->assertStringContainsString($this->property->name, $expense->subject_name);
    }

    #[Test]
    public function expense_has_subject_name_accessor_for_unit(): void
    {
        $expense = $this->createExpense([
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $this->assertStringContainsString('الوحدة:', $expense->subject_name);
        $this->assertStringContainsString($this->unit->name, $expense->subject_name);
    }

    #[Test]
    public function expense_subject_name_returns_not_specified_when_no_subject(): void
    {
        $expense = Expense::create([
            'desc' => 'Orphan Expense',
            'type' => 'other',
            'cost' => 100,
            'date' => now(),
            'subject_type' => null,
            'subject_id' => null,
        ]);

        $this->assertEquals('غير محدد', $expense->subject_name);
    }

    // ==========================================
    // Helper Method Tests
    // ==========================================

    #[Test]
    public function expense_has_documents_returns_true_when_docs_exist(): void
    {
        $expense = $this->createExpense([
            'docs' => [['type' => 'invoice', 'file' => 'test.pdf']],
        ]);

        $this->assertTrue($expense->hasDocuments());
    }

    #[Test]
    public function expense_has_documents_returns_false_when_no_docs(): void
    {
        $expense = $this->createExpense(['docs' => null]);

        $this->assertFalse($expense->hasDocuments());
    }

    #[Test]
    public function expense_can_get_documents_by_type(): void
    {
        $expense = $this->createExpense([
            'docs' => [
                ['type' => 'invoice', 'file' => 'invoice1.pdf'],
                ['type' => 'receipt', 'file' => 'receipt1.pdf'],
                ['type' => 'invoice', 'file' => 'invoice2.pdf'],
            ],
        ]);

        $invoices = $expense->getDocumentsByType('invoice');

        $this->assertCount(2, $invoices);
    }

    #[Test]
    public function expense_get_documents_by_type_returns_empty_when_no_docs(): void
    {
        $expense = $this->createExpense(['docs' => null]);

        $result = $expense->getDocumentsByType('invoice');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function expense_can_calculate_docs_total(): void
    {
        $expense = $this->createExpense([
            'docs' => [
                ['type' => 'invoice', 'amount' => 500.00],
                ['type' => 'invoice', 'amount' => 300.00],
                ['type' => 'labor', 'hours' => 5, 'rate' => 50],
            ],
        ]);

        // 500 + 300 + (5 * 50) = 1050
        $this->assertEquals(1050.00, $expense->calculateDocsTotal());
    }

    #[Test]
    public function expense_calculate_docs_total_returns_zero_when_no_docs(): void
    {
        $expense = $this->createExpense(['docs' => null]);

        $this->assertEquals(0, $expense->calculateDocsTotal());
    }

    // ==========================================
    // Scope Tests
    // ==========================================

    #[Test]
    public function expense_scope_of_type_filters_correctly(): void
    {
        $this->createExpense(['type' => 'maintenance']);
        $this->createExpense(['type' => 'government']);
        $this->createExpense(['type' => 'maintenance']);

        $maintenanceExpenses = Expense::ofType('maintenance')->get();

        $this->assertCount(2, $maintenanceExpenses);
    }

    #[Test]
    public function expense_scope_in_date_range_filters_correctly(): void
    {
        $this->createExpense(['date' => '2026-01-15']);
        $this->createExpense(['date' => '2026-01-20']);
        $this->createExpense(['date' => '2026-02-01']);

        $januaryExpenses = Expense::inDateRange('2026-01-01', '2026-01-31')->get();

        $this->assertCount(2, $januaryExpenses);
    }

    #[Test]
    public function expense_scope_this_month_filters_correctly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 15));

        $this->createExpense(['date' => Carbon::now()]);
        $this->createExpense(['date' => Carbon::now()->subMonth()]);

        $thisMonthExpenses = Expense::thisMonth()->get();

        $this->assertCount(1, $thisMonthExpenses);

        Carbon::setTestNow();
    }

    #[Test]
    public function expense_scope_this_year_filters_correctly(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 15));

        $this->createExpense(['date' => Carbon::create(2026, 3, 10)]);
        $this->createExpense(['date' => Carbon::create(2026, 8, 20)]);
        $this->createExpense(['date' => Carbon::create(2025, 12, 1)]);

        $thisYearExpenses = Expense::thisYear()->get();

        $this->assertCount(2, $thisYearExpenses);

        Carbon::setTestNow();
    }

    #[Test]
    public function expense_scope_for_properties_filters_correctly(): void
    {
        $this->createExpense([
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->createExpense([
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $propertyExpenses = Expense::forProperties()->get();

        $this->assertCount(1, $propertyExpenses);
    }

    #[Test]
    public function expense_scope_for_units_filters_correctly(): void
    {
        $this->createExpense([
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->createExpense([
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $unitExpenses = Expense::forUnits()->get();

        $this->assertCount(1, $unitExpenses);
    }

    #[Test]
    public function expense_scope_for_property_filters_by_specific_property(): void
    {
        $property2 = Property::create([
            'name' => 'Second Property',
            'owner_id' => $this->owner->id,
            'type_id' => $this->propertyType->id,
            'status_id' => $this->propertyStatus->id,
            'location_id' => $this->location->id,
            'address' => 'Address 2',
        ]);

        $this->createExpense([
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->createExpense([
            'subject_type' => Property::class,
            'subject_id' => $property2->id,
        ]);

        $specificPropertyExpenses = Expense::forProperty($this->property->id)->get();

        $this->assertCount(1, $specificPropertyExpenses);
    }

    #[Test]
    public function expense_scope_for_unit_filters_by_specific_unit(): void
    {
        $unit2 = Unit::create([
            'name' => 'Unit 102',
            'property_id' => $this->property->id,
            'unit_type_id' => $this->unitType->id,
            'rent_price' => 3500,
            'floor_number' => 1,
        ]);

        $this->createExpense([
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $this->createExpense([
            'subject_type' => Unit::class,
            'subject_id' => $unit2->id,
        ]);

        $specificUnitExpenses = Expense::forUnit($this->unit->id)->get();

        $this->assertCount(1, $specificUnitExpenses);
    }

    // ==========================================
    // Constants Tests
    // ==========================================

    #[Test]
    public function expense_types_constant_contains_expected_keys(): void
    {
        $expectedKeys = ['maintenance', 'government', 'purchases', 'utilities', 'other'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, Expense::TYPES);
        }
    }

    #[Test]
    public function expense_type_colors_constant_contains_expected_keys(): void
    {
        $expectedKeys = ['maintenance', 'government', 'utilities', 'purchases', 'salaries', 'commissions', 'other'];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, Expense::TYPE_COLORS);
        }
    }

    // ==========================================
    // Edge Case Tests
    // ==========================================

    #[Test]
    public function expense_handles_unknown_type_gracefully(): void
    {
        $expense = $this->createExpense(['type' => 'unknown_type']);

        // Should return the type itself if not found in TYPES
        $this->assertEquals('unknown_type', $expense->type_name);
        // Should return gray if not found in TYPE_COLORS
        $this->assertEquals('gray', $expense->type_color);
    }

    #[Test]
    public function expense_can_be_updated(): void
    {
        $expense = $this->createExpense();

        $expense->update([
            'desc' => 'Updated Description',
            'cost' => 999.99,
        ]);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'desc' => 'Updated Description',
            'cost' => 999.99,
        ]);
    }

    #[Test]
    public function expense_can_be_deleted(): void
    {
        $expense = $this->createExpense();
        $expenseId = $expense->id;

        $expense->delete();

        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
    }

    #[Test]
    public function expense_handles_empty_docs_array(): void
    {
        $expense = $this->createExpense(['docs' => []]);

        $this->assertEquals(0, $expense->docs_count);
        $this->assertFalse($expense->hasDocuments());
        $this->assertEquals(0, $expense->calculateDocsTotal());
    }

    // ==========================================
    // Total Expenses Calculation Tests
    // ==========================================

    #[Test]
    public function property_total_expenses_sums_correctly(): void
    {
        $this->createExpense([
            'cost' => 1000,
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->createExpense([
            'cost' => 500,
            'subject_type' => Property::class,
            'subject_id' => $this->property->id,
        ]);

        $this->property->refresh();

        $this->assertEquals(1500, $this->property->total_expenses);
    }

    #[Test]
    public function unit_total_expenses_sums_correctly(): void
    {
        $this->createExpense([
            'cost' => 800,
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $this->createExpense([
            'cost' => 200,
            'subject_type' => Unit::class,
            'subject_id' => $this->unit->id,
        ]);

        $this->unit->refresh();

        $this->assertEquals(1000, $this->unit->total_expenses);
    }
}
