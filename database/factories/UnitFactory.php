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
            'property_id' => \App\Models\Property::factory(),
            'unit_number' => $this->faker->unique()->numberBetween(101, 999),
            'floor_number' => $this->faker->numberBetween(1, 10),
            'area_sqm' => $this->faker->numberBetween(50, 300),
            'rooms_count' => $this->faker->numberBetween(1, 5),
            'bathrooms_count' => $this->faker->numberBetween(1, 3),
            'rent_price' => $this->faker->numberBetween(1500, 8000),
            'unit_type' => $this->faker->randomElement(['studio', 'apartment', 'duplex', 'penthouse', 'office', 'shop', 'warehouse']),
            'unit_ranking' => $this->faker->randomElement(['economy', 'standard', 'premium', 'luxury']),
            'direction' => $this->faker->randomElement(['north', 'south', 'east', 'west', 'northeast', 'northwest', 'southeast', 'southwest']),
            'view_type' => $this->faker->randomElement(['street', 'garden', 'sea', 'city', 'mountain', 'courtyard']),
            'status_id' => 1, // Default available status
            'current_tenant_id' => null,
            'furnished' => $this->faker->boolean(),
            'has_balcony' => $this->faker->boolean(),
            'has_parking' => $this->faker->boolean(),
            'has_storage' => $this->faker->boolean(),
            'has_maid_room' => $this->faker->boolean(),
            'notes' => $this->faker->optional()->paragraph(),
            'available_from' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'is_active' => true,
        ];
    }
}
