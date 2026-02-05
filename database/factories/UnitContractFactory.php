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
            'contract_number' => 'UC-' . $this->faker->unique()->randomNumber(8),
            'tenant_id' => \App\Models\User::factory(),
            'unit_id' => \App\Models\Unit::factory(),
            'property_id' => \App\Models\Property::factory(),
            'monthly_rent' => $monthlyRent,
            'security_deposit' => $monthlyRent,
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_status' => $this->faker->randomElement(['draft', 'active', 'expired', 'terminated', 'renewed']),
            'payment_frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'semi_annually', 'annually']),
            'notes' => $this->faker->optional()->sentence(),
            'file' => null,
        ];
    }
}
