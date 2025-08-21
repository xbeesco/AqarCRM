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
            'name' => 'عقار ' . $this->faker->words(2, true),
            'owner_id' => \App\Models\User::role('owner')->inRandomOrder()->first()->id ?? \App\Models\User::factory(),
            'status' => $this->faker->randomElement(['1', '2', '3']), // Use enum values
            'type' => $this->faker->randomElement(['1', '2', '3', '4']), // Use enum values
            'location_id' => 1, // Will be overridden by seeder
            'address' => $this->faker->address(),
            'postal_code' => $this->faker->postcode(),
            'parking_spots' => $this->faker->numberBetween(1, 20),
            'elevators' => $this->faker->numberBetween(0, 3),
            'area_sqm' => $this->faker->numberBetween(100, 1000),
            'build_year' => $this->faker->numberBetween(1990, 2025),
            'floors_count' => $this->faker->numberBetween(1, 10),
            'notes' => $this->faker->paragraph(),
        ];
    }
}
