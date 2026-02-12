<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{3}'),
            'parent_id' => null,
            'level' => 1,
            'path' => null,
            'coordinates' => $this->faker->latitude(24, 32) . ',' . $this->faker->longitude(34, 52),
            'postal_code' => $this->faker->postcode(),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the location is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a child location.
     */
    public function child(?Location $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $parent?->id ?? Location::factory(),
            'level' => ($parent?->level ?? 0) + 1,
        ]);
    }

    /**
     * Create a level 1 location (country/region).
     */
    public function level1(): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 1,
            'parent_id' => null,
        ]);
    }

    /**
     * Create a level 2 location (city).
     */
    public function level2(?Location $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 2,
            'parent_id' => $parent?->id ?? Location::factory()->level1(),
        ]);
    }

    /**
     * Create a level 3 location (district).
     */
    public function level3(?Location $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 3,
            'parent_id' => $parent?->id ?? Location::factory()->level2(),
        ]);
    }

    /**
     * Create a level 4 location (neighborhood).
     */
    public function level4(?Location $parent = null): static
    {
        return $this->state(fn (array $attributes) => [
            'level' => 4,
            'parent_id' => $parent?->id ?? Location::factory()->level3(),
        ]);
    }
}
