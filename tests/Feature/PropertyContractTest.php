<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\PropertyContract;
use App\Models\User;
use App\Models\Property;
use App\Services\PropertyContractService;
use Carbon\Carbon;

class PropertyContractTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $propertyContractService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->propertyContractService = new PropertyContractService();
        
        // Create roles
        $this->artisan('db:seed', ['--class' => 'RolesAndPermissionsSeeder']);
    }

    /** @test */
    public function test_contract_number_generation()
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $contract = PropertyContract::create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'commission_rate' => 5.00,
            'duration_months' => 12,
            'start_date' => now(),
            'end_date' => now()->addMonths(12)->subDay(),
        ]);

        $this->assertNotNull($contract->contract_number);
        $this->assertStringStartsWith('PC-', $contract->contract_number);
        $this->assertDatabaseHas('property_contracts', [
            'contract_number' => $contract->contract_number
        ]);
    }

    /** @test */
    public function test_commission_calculation()
    {
        $contract = PropertyContract::factory()->make([
            'commission_rate' => 7.5,
        ]);

        $this->assertEquals(750.00, $contract->calculateCommission(10000));
        $this->assertEquals(0, $contract->calculateCommission(0));
    }

    /** @test */
    public function test_end_date_calculation()
    {
        $startDate = '2025-01-01';
        $durationMonths = 12;

        $contract = PropertyContract::factory()->make([
            'start_date' => $startDate,
            'duration_months' => $durationMonths,
        ]);

        // The boot method should auto-calculate end_date
        $expectedEndDate = Carbon::parse($startDate)->addMonths($durationMonths)->subDay();
        $this->assertEquals($expectedEndDate->format('Y-m-d'), $contract->end_date->format('Y-m-d'));
    }

    /** @test */
    public function test_renewal_eligibility()
    {
        $contract = PropertyContract::factory()->create([
            'contract_status' => 'active',
            'end_date' => now()->addDays(15), // Within notice period
            'notice_period_days' => 30,
        ]);

        $this->assertTrue($contract->canRenew());

        $expiredContract = PropertyContract::factory()->create([
            'contract_status' => 'expired',
        ]);

        $this->assertFalse($expiredContract->canRenew());
    }

    /** @test */
    public function test_contract_activation_workflow()
    {
        $this->actingAs(User::factory()->create());

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $contract = PropertyContract::factory()->create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'contract_status' => 'draft',
        ]);

        $activatedContract = $this->propertyContractService->activateContract($contract->id);

        $this->assertEquals('active', $activatedContract->contract_status);
        $this->assertNotNull($activatedContract->approved_by);
        $this->assertNotNull($activatedContract->approved_at);
    }

    /** @test */
    public function test_contract_renewal_process()
    {
        $this->actingAs(User::factory()->create());

        $owner = User::factory()->create();
        $owner->assignRole('owner');
        
        $property = Property::factory()->create(['owner_id' => $owner->id]);

        $oldContract = PropertyContract::factory()->create([
            'owner_id' => $owner->id,
            'property_id' => $property->id,
            'contract_status' => 'active',
            'end_date' => now()->addDays(15),
            'notice_period_days' => 30,
        ]);

        $newContract = $this->propertyContractService->renewContract($oldContract->id, 12);

        $this->assertNotEquals($oldContract->id, $newContract->id);
        $this->assertEquals('active', $newContract->contract_status);
        $this->assertEquals($oldContract->end_date->addDay(), $newContract->start_date);
        
        $oldContract->refresh();
        $this->assertEquals('renewed', $oldContract->contract_status);
    }

    /** @test */
    public function test_contract_termination()
    {
        $this->actingAs(User::factory()->create());

        $contract = PropertyContract::factory()->create([
            'contract_status' => 'active',
        ]);

        $terminatedContract = $this->propertyContractService->terminateContract(
            $contract->id, 
            'Early termination by owner request'
        );

        $this->assertEquals('terminated', $terminatedContract->contract_status);
        $this->assertEquals('Early termination by owner request', $terminatedContract->terminated_reason);
        $this->assertNotNull($terminatedContract->terminated_at);
    }

    /** @test */
    public function test_active_contracts_scope()
    {
        PropertyContract::factory()->create([
            'contract_status' => 'active',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);

        PropertyContract::factory()->create([
            'contract_status' => 'draft',
        ]);

        PropertyContract::factory()->create([
            'contract_status' => 'expired',
        ]);

        $activeContracts = PropertyContract::active()->get();
        $this->assertEquals(1, $activeContracts->count());
    }

    /** @test */
    public function test_expiring_contracts_scope()
    {
        PropertyContract::factory()->create([
            'contract_status' => 'active',
            'end_date' => now()->addDays(15),
        ]);

        PropertyContract::factory()->create([
            'contract_status' => 'active',
            'end_date' => now()->addDays(45),
        ]);

        $expiringContracts = PropertyContract::expiring(30)->get();
        $this->assertEquals(1, $expiringContracts->count());
    }
}