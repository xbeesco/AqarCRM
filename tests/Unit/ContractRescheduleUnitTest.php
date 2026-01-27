<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PropertyContractService;
use App\Models\UnitContract;
use App\Models\PropertyContract;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContractRescheduleUnitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    //  التاكد من صحه حساب عددالشهور علي عدد الدفعات
    public function test_calculates_correct_payment_counts()
    {
        // Monthly
        $this->assertEquals(12, PropertyContractService::calculatePaymentsCount(12, 'monthly'));

        // Quarterly
        $this->assertEquals(4, PropertyContractService::calculatePaymentsCount(12, 'quarterly'));

        // Semi-annually
        $this->assertEquals(2, PropertyContractService::calculatePaymentsCount(12, 'semi_annually'));

        // Annually
        $this->assertEquals(1, PropertyContractService::calculatePaymentsCount(12, 'annually'));

        // Invalid division
        $this->assertEquals('قسمة لا تصح', PropertyContractService::calculatePaymentsCount(7, 'quarterly'));
    }

    /** test 
     * التاكد من صحة مدة العقد مع تكرار التوريد
     */
    public function test_validates_duration_and_frequency()
    {
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'monthly'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'quarterly'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'semi_annually'));
        $this->assertTrue(PropertyContractService::isValidDuration(12, 'annually'));

        $this->assertFalse(PropertyContractService::isValidDuration(7, 'quarterly'));
        $this->assertFalse(PropertyContractService::isValidDuration(5, 'semi_annually'));
    }

    /** test 
     * التاكد من عدد الشهور المتبقيه
     */
    public function test_property_contract_calculates_remaining_months_correctly()
    {
        $contract = new PropertyContract([
            'duration_months' => 12,
            'start_date' => '2025-01-01',
            'monthly_rent' => 1000
        ]);

        // Mocking behavior without hitting DB for simple calculation if possible, 
        // but since it depends on payments relation, we'll use a factory in Feature if needed.
        // For Unit, we test the math if simplified. 
        // In current model, it uses getPaidMonthsCount() which hits DB.

        $this->assertEquals(12, $contract->getRemainingMonths());
    }

    /** test 
     * التاكد من تاريخ انتهاء العقد
     */
    public function test_property_contract_end_date_calculation_on_boot()
    {
        $contract = PropertyContract::factory()->make([
            'start_date' => '2025-01-01',
            'duration_months' => 12
        ]);

        // Trigger boot logic by saving or manually
        $contract->save();

        $this->assertEquals('2025-12-31', $contract->end_date->format('Y-m-d'));
    }
}
