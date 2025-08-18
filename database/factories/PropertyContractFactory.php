<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyContract>
 */
class PropertyContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+1 month');
        $durationMonths = $this->faker->randomElement([6, 12, 18, 24, 36]);
        $endDate = (clone $startDate)->modify("+{$durationMonths} months")->modify('-1 day');

        return [
            'owner_id' => \App\Models\User::factory(),
            'property_id' => \App\Models\Property::factory(),
            'commission_rate' => $this->faker->randomFloat(2, 3, 10),
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'contract_status' => $this->faker->randomElement(['draft', 'active', 'suspended', 'expired', 'terminated']),
            'notary_number' => $this->faker->optional()->numerify('NOT-######'),
            'payment_day' => $this->faker->numberBetween(1, 28),
            'auto_renew' => $this->faker->boolean(),
            'notice_period_days' => $this->faker->randomElement([30, 60, 90]),
            'terms_and_conditions' => $this->faker->optional()->paragraph(),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
        ];
    }
}
