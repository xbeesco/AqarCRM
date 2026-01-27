<?php

namespace Database\Factories;

use App\Models\CollectionPayment;
use App\Models\Property;
use App\Models\Unit;
use App\Models\UnitContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CollectionPayment>
 */
class CollectionPaymentFactory extends Factory
{
    protected $model = CollectionPayment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->numberBetween(2000, 10000);
        $lateFee = $this->faker->optional(0.2)->numberBetween(50, 500) ?? 0;
        $dueDate = $this->faker->dateTimeBetween('-3 months', '+3 months');

        return [
            'unit_contract_id' => UnitContract::factory(),
            'unit_id' => Unit::factory(),
            'property_id' => Property::factory(),
            'tenant_id' => User::factory(),
            'amount' => $amount,
            'late_fee' => $lateFee,
            'total_amount' => $amount + $lateFee,
            'due_date_start' => $dueDate,
            'due_date_end' => Carbon::parse($dueDate)->addDays(30),
            'month_year' => Carbon::parse($dueDate)->format('Y-m'),
        ];
    }

    /**
     * Indicate that the payment has been collected.
     */
    public function collected(): static
    {
        return $this->state(function (array $attributes) {
            $dueDate = $attributes['due_date_start'] ?? Carbon::now()->subMonth();
            $collectionDate = Carbon::parse($dueDate)->addDays($this->faker->numberBetween(0, 14));

            return [
                'collection_date' => $collectionDate,
                'paid_date' => $collectionDate,
                'collected_by' => User::factory(),
            ];
        });
    }

    /**
     * Indicate that the payment is pending (not collected).
     */
    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'collection_date' => null,
                'paid_date' => null,
                'collected_by' => null,
            ];
        });
    }

    /**
     * Indicate that the payment is overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            $overdueDate = Carbon::now()->subDays($this->faker->numberBetween(15, 60));

            return [
                'due_date_start' => $overdueDate,
                'due_date_end' => $overdueDate->copy()->addDays(30),
                'collection_date' => null,
                'month_year' => $overdueDate->format('Y-m'),
            ];
        });
    }

    /**
     * Indicate that the payment is postponed.
     */
    public function postponed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'collection_date' => null,
                'delay_duration' => $this->faker->numberBetween(7, 30),
                'delay_reason' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the payment is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(function (array $attributes) {
            $futureDate = Carbon::now()->addDays($this->faker->numberBetween(7, 60));

            return [
                'due_date_start' => $futureDate,
                'due_date_end' => $futureDate->copy()->addDays(30),
                'collection_date' => null,
                'month_year' => $futureDate->format('Y-m'),
            ];
        });
    }

    /**
     * Set a specific amount for the payment.
     */
    public function withAmount(float $amount): static
    {
        return $this->state(function (array $attributes) use ($amount) {
            $lateFee = $attributes['late_fee'] ?? 0;

            return [
                'amount' => $amount,
                'total_amount' => $amount + $lateFee,
            ];
        });
    }
}
