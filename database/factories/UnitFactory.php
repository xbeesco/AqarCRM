<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Unit ' . $this->faker->unique()->numberBetween(101, 999),
            'property_id' => \App\Models\Property::factory(),
            'floor_number' => $this->faker->numberBetween(1, 10),
            'area_sqm' => $this->faker->numberBetween(50, 300),
            'rooms_count' => $this->faker->numberBetween(1, 5),
            'bathrooms_count' => $this->faker->numberBetween(1, 3),
            'rent_price' => $this->faker->numberBetween(1500, 8000),
            'notes' => $this->faker->optional()->paragraph(),
        ];
    }
}
