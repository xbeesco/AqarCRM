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
        // Only use durations divisible by all frequencies (12, 24, 36)
        $durationMonths = $this->faker->randomElement([12, 24, 36]);
        $endDate = (clone $startDate)->modify("+{$durationMonths} months")->modify('-1 day');
        $paymentFrequency = $this->faker->randomElement(['monthly', 'quarterly', 'semi_annually', 'annually']);

        return [
            'contract_number' => 'PC-'.date('Y').'-'.str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
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
            'payment_frequency' => $paymentFrequency,
            'payments_count' => $this->calculatePaymentsCount($durationMonths, $paymentFrequency),
            'terms_and_conditions' => $this->faker->optional()->paragraph(),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => 1,
        ];
    }

    /**
     * Calculate payments count based on duration and frequency.
     */
    protected function calculatePaymentsCount(int $durationMonths, string $frequency): int
    {
        $monthsPerPayment = match ($frequency) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annually' => 6,
            'annually' => 12,
            default => 1,
        };

        return $durationMonths / $monthsPerPayment;
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
     * Indicate that the contract is a draft.
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
            'terminated_at' => now(),
            'terminated_reason' => 'Test termination reason',
        ]);
    }

    /**
     * Set monthly payment frequency.
     */
    public function monthly(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_frequency' => 'monthly',
            'payments_count' => $attributes['duration_months'],
        ]);
    }

    /**
     * Set quarterly payment frequency.
     */
    public function quarterly(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_frequency' => 'quarterly',
            'payments_count' => $attributes['duration_months'] / 3,
        ]);
    }

    /**
     * Set semi-annual payment frequency.
     */
    public function semiAnnually(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_frequency' => 'semi_annually',
            'payments_count' => $attributes['duration_months'] / 6,
        ]);
    }

    /**
     * Set annual payment frequency.
     */
    public function annually(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_frequency' => 'annually',
            'payments_count' => $attributes['duration_months'] / 12,
        ]);
    }
}
