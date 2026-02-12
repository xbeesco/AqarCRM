<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\UnitContracts\Pages\ReschedulePayments;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReschedulePaymentsUiTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected UnitContract $contract;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['type' => 'super_admin']);

        $property = Property::factory()->create();
        $unit = Unit::factory()->create(['property_id' => $property->id]);

        $this->contract = UnitContract::create([
            'tenant_id' => User::factory()->create(['type' => 'tenant'])->id,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'monthly_rent' => 1000,
            'duration_months' => 12,
            'start_date' => Carbon::now(),
            'payment_frequency' => 'monthly',
            'contract_status' => 'active',
        ]);

        // Generate at least one payment to allow rescheduling
        $this->contract->payments()->create([
            'payment_number' => 'PAY-1',
            'amount' => 1000,
            'total_amount' => 1000,
            'unit_id' => $unit->id,
            'property_id' => $property->id,
            'tenant_id' => $this->contract->tenant_id,
            'due_date_start' => Carbon::now(),
            'due_date_end' => Carbon::now()->addMonth(),
            'month_year' => Carbon::now()->format('m-Y'),
            'payment_status_id' => 2,
        ]);
    }

    /** @test */
    public function it_can_render_reschedule_page()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ReschedulePayments::class, [
            'record' => $this->contract,
        ])->assertSuccessful();
    }

    /** @test */
    public function it_updates_summary_when_duration_changes()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ReschedulePayments::class, [
            'record' => $this->contract,
        ])
            ->fillForm([
                'additional_months' => 6,
                'new_monthly_rent' => 1500,
            ])
            ->assertSee('إجمالي مدة العقد الجديدة: 6 شهر'); // Since 0 are paid in this test setup
    }

    /** @test */
    public function it_calculates_new_payments_count_reactively()
    {
        $this->actingAs($this->superAdmin);

        Livewire::test(ReschedulePayments::class, [
            'record' => $this->contract,
        ])
            ->fillForm([
                'additional_months' => 12,
                'new_frequency' => 'quarterly',
            ])
            ->assertFormSet(['new_payments_count' => 4]);
    }
}
