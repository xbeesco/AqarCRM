<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UnitContract>
 */
class UnitContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+1 month');
        $durationMonths = $this->faker->randomElement([6, 12, 18, 24]);
        $endDate = (clone $startDate)->modify("+{$durationMonths} months")->modify('-1 day');
        $monthlyRent = $this->faker->numberBetween(2000, 8000);

        return [
            'tenant_id' => \App\Models\User::factory(),
            'unit_id' => \App\Models\Unit::factory(),
            'property_id' => \App\Models\Property::factory(),
            'monthly_rent' => $monthlyRent,
            'security_deposit' => $monthlyRent, // Usually one month rent
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_status' => $this->faker->randomElement(['draft', 'active', 'expired', 'terminated', 'renewed']),
            'payment_frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'semi_annually', 'annually']),
            'payment_method' => $this->faker->randomElement(['bank_transfer', 'cash', 'check', 'online']),
            'grace_period_days' => $this->faker->randomElement([3, 5, 7, 10]),
            'late_fee_rate' => $this->faker->randomFloat(2, 0, 5),
            'utilities_included' => $this->faker->boolean(),
            'furnished' => $this->faker->boolean(),
            'evacuation_notice_days' => $this->faker->randomElement([30, 60, 90]),
            'terms_and_conditions' => $this->faker->optional()->paragraph(),
            'special_conditions' => $this->faker->optional()->sentence(),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
        ];
    }
}
