<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Property>
 */
class PropertyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => \App\Models\User::factory(),
            'type_id' => 1, // Default property type
            'status_id' => 1, // Default status
            'location_id' => 1, // Default location
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraph(),
            'address' => $this->faker->address(),
            'area_sqm' => $this->faker->numberBetween(100, 1000),
            'total_units' => $this->faker->numberBetween(1, 50),
            'floors_count' => $this->faker->numberBetween(1, 10),
            'build_year' => $this->faker->numberBetween(1990, 2025),
            'has_elevator' => $this->faker->boolean(),
            'parking_spaces' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
