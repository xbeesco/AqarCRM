<?php

namespace Database\Factories;

use App\Models\PropertyContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PropertyContract>
 */
class PropertyContractFactory extends Factory
{
    protected $model = PropertyContract::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 year', '+1 month');
        $durationMonths = $this->faker->randomElement([6, 12, 18, 24, 36]);

        return [
            'owner_id' => \App\Models\User::owners()->inRandomOrder()->first()->id ?? \App\Models\User::factory(),
            'property_id' => \App\Models\Property::factory(),
            'commission_rate' => $this->faker->randomFloat(2, 3, 10),
            'duration_months' => $durationMonths,
            'start_date' => $startDate,
            'payment_frequency' => $this->faker->randomElement(['monthly', 'quarterly', 'semi_annually', 'annually']),
            'contract_status' => 'active',
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the contract is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_status' => 'active',
        ]);
    }

    /**
     * Indicate that the contract is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_status' => 'draft',
        ]);
    }

    /**
     * Indicate that the contract is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_status' => 'expired',
        ]);
    }

    /**
     * Indicate that the contract is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'contract_status' => 'terminated',
        ]);
    }
}
