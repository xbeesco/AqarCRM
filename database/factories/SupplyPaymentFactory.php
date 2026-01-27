<?php

namespace Database\Factories;

use App\Models\PropertyContract;
use App\Models\SupplyPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyPaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SupplyPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $dueDate = Carbon::now()->addDays(rand(1, 30));
        $grossAmount = $this->faker->randomFloat(2, 1000, 10000);
        $commissionRate = 2.5;
        $commissionAmount = ($grossAmount * $commissionRate) / 100;
        $netAmount = $grossAmount - $commissionAmount;

        return [
            'property_contract_id' => PropertyContract::factory(),
            'owner_id' => User::factory()->state(['type' => 'owner']),
            'gross_amount' => $grossAmount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'maintenance_deduction' => 0,
            'other_deductions' => 0,
            'net_amount' => $netAmount,
            'due_date' => $dueDate,
            'paid_date' => null,
            'approval_status' => 'pending',
            'month_year' => $dueDate->format('Y-m'),
            'notes' => $this->faker->sentence,
        ];
    }

    /**
     * Indicate that the payment is paid (collected).
     */
    public function paid()
    {
        return $this->state(function (array $attributes) {
            return [
                'paid_date' => Carbon::now(),
                'collected_by' => User::factory()->state(['type' => 'employee']),
            ];
        });
    }

    /**
     * Indicate that the payment is pending.
     */
    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'paid_date' => null,
            ];
        });
    }
}
