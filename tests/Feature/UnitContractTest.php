<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\UnitContract;
use App\Models\User;
use App\Models\Property;
use App\Models\Unit;
use App\Services\UnitContractService;
use Carbon\Carbon;

class UnitContractTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $unitContractService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->unitContractService = new UnitContractService();
        
        // Create roles
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /** @test */
    public function test_unit_contract_number_generation()
    {
        $tenant = User::factory()->create();
        $tenant->assignRole('tenant');
        
        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        $contract = UnitContract::create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 5000,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addMonths(12)->subDay(),
        ]);

        $this->assertNotNull($contract->contract_number);
        $this->assertStringStartsWith('UC-', $contract->contract_number);
        $this->assertDatabaseHas('unit_contracts', [
            'contract_number' => $contract->contract_number
        ]);
    }

    /** @test */
    public function test_payment_schedule_generation()
    {
        $contract = UnitContract::factory()->make([
            'monthly_rent' => 5000,
            'duration_months' => 12,
            'payment_frequency' => 'monthly',
            'start_date' => '2025-01-01',
        ]);

        $schedule = $contract->generatePaymentSchedule();

        $this->assertEquals(12, count($schedule));
        $this->assertEquals(60000, $contract->getTotalContractValue());
    }

    /** @test */
    public function test_late_fee_calculation()
    {
        $contract = UnitContract::factory()->make([
            'monthly_rent' => 5000,
            'late_fee_rate' => 2.5,
            'grace_period_days' => 5,
        ]);

        // Within grace period - no fee
        $this->assertEquals(0, $contract->calculateLateFee(5000, 3));
        $this->assertEquals(0, $contract->calculateLateFee(5000, 5));
        
        // After grace period - calculate fee
        $this->assertEquals(125.00, $contract->calculateLateFee(5000, 10)); // 5 days late after grace
    }

    /** @test */
    public function test_early_termination_calculation()
    {
        $contract = UnitContract::factory()->create([
            'monthly_rent' => 5000,
            'contract_status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
        ]);

        $penalty = $contract->calculateEarlyTerminationPenalty();
        $this->assertEquals(10000, $penalty); // 2 months rent
        $this->assertTrue($contract->canTerminateEarly());
    }

    /** @test */
    public function test_unit_contract_activation_workflow()
    {
        $this->actingAs(User::factory()->create());

        $tenant = User::factory()->create();
        $tenant->assignRole('tenant');
        
        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        $contract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'contract_status' => 'draft',
        ]);

        $activatedContract = $this->unitContractService->activateContract($contract->id);

        $this->assertEquals('active', $activatedContract->contract_status);
        $this->assertNotNull($activatedContract->approved_by);
        $this->assertNotNull($activatedContract->approved_at);
    }

    /** @test */
    public function test_unit_availability_validation()
    {
        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        // Create an active contract for the unit
        UnitContract::factory()->create([
            'unit_id' => $unit->id,
            'contract_status' => 'active',
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
        ]);

        // Try to create another contract for the same period
        $this->assertFalse(
            $this->unitContractService->checkUnitAvailability(
                $unit->id,
                now()->addMonths(6)->format('Y-m-d'),
                now()->addMonths(18)->format('Y-m-d')
            )
        );

        // But should be available after the current contract ends
        $this->assertTrue(
            $this->unitContractService->checkUnitAvailability(
                $unit->id,
                now()->addMonths(13)->format('Y-m-d'),
                now()->addMonths(25)->format('Y-m-d')
            )
        );
    }

    /** @test */
    public function test_contract_renewal_process()
    {
        $this->actingAs(User::factory()->create());

        $tenant = User::factory()->create();
        $tenant->assignRole('tenant');
        
        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        $oldContract = UnitContract::factory()->create([
            'tenant_id' => $tenant->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
            'end_date' => now()->addDays(15),
            'evacuation_notice_days' => 30,
        ]);

        $newContract = $this->unitContractService->renewContract($oldContract->id, 12);

        $this->assertNotEquals($oldContract->id, $newContract->id);
        $this->assertEquals('active', $newContract->contract_status);
        $this->assertEquals($oldContract->end_date->addDay(), $newContract->start_date);
        
        $oldContract->refresh();
        $this->assertEquals('renewed', $oldContract->contract_status);
    }

    /** @test */
    public function test_contract_termination_with_penalty()
    {
        $this->actingAs(User::factory()->create());

        $contract = UnitContract::factory()->create([
            'contract_status' => 'active',
            'monthly_rent' => 5000,
            'end_date' => now()->addMonths(6),
        ]);

        $terminatedContract = $this->unitContractService->terminateContract(
            $contract->id, 
            'Tenant requested early termination'
        );

        $this->assertEquals('terminated', $terminatedContract->contract_status);
        $this->assertEquals('Tenant requested early termination', $terminatedContract->terminated_reason);
        $this->assertNotNull($terminatedContract->terminated_at);
        $this->assertStringContains('Early termination penalty: SAR 10,000.00', $terminatedContract->notes);
    }

    /** @test */
    public function test_security_deposit_processing()
    {
        $this->actingAs(User::factory()->create());

        $contract = UnitContract::factory()->create([
            'security_deposit' => 5000,
        ]);

        // Test collection
        $result = $this->unitContractService->processSecurityDeposit($contract->id, 'collect');
        $this->assertEquals('collected', $result['action']);
        $this->assertEquals(5000, $result['amount']);

        // Test refund with deductions
        $result = $this->unitContractService->processSecurityDeposit($contract->id, 'refund', [
            'deductions' => 1000,
        ]);
        $this->assertEquals('refunded', $result['action']);
        $this->assertEquals(5000, $result['original_amount']);
        $this->assertEquals(1000, $result['deductions']);
        $this->assertEquals(4000, $result['refund_amount']);
    }

    /** @test */
    public function test_active_contracts_scope()
    {
        UnitContract::factory()->create([
            'contract_status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        UnitContract::factory()->create([
            'contract_status' => 'draft',
        ]);

        UnitContract::factory()->create([
            'contract_status' => 'expired',
        ]);

        $activeContracts = UnitContract::active()->get();
        $this->assertEquals(1, $activeContracts->count());
    }

    /** @test */
    public function test_quarterly_payment_schedule()
    {
        $contract = UnitContract::factory()->make([
            'monthly_rent' => 3000,
            'duration_months' => 12,
            'payment_frequency' => 'quarterly',
            'start_date' => '2025-01-01',
        ]);

        $schedule = $contract->generatePaymentSchedule();

        $this->assertEquals(4, count($schedule)); // 4 quarterly payments
        $this->assertEquals(9000, $schedule[0]['amount']); // 3 months rent
        $this->assertEquals(36000, $contract->getTotalContractValue());
    }
}