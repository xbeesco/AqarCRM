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
            'owner_id' => \App\Models\User::owners()->inRandomOrder()->first()->id ?? \App\Models\User::factory(),
            'status_id' => $this->faker->randomElement([1, 2, 3]), // Use numeric IDs
            'type_id' => $this->faker->randomElement([1, 2, 3, 4]), // Use numeric IDs
            'location_id' => null, // Will be overridden by seeder or test
            'address' => $this->faker->address(),
            'postal_code' => $this->faker->postcode(),
            'parking_spots' => $this->faker->numberBetween(1, 20),
            'elevators' => $this->faker->numberBetween(0, 3),
            'build_year' => $this->faker->numberBetween(1990, 2025),
            'floors_count' => $this->faker->numberBetween(1, 10),
            'notes' => $this->faker->paragraph(),
        ];
    }
}
