<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VenueVendor>
 */
class VenueVendorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'venue_id' => Venue::factory(),
            'slot_number' => fake()->numberBetween(1, 4),
            'name' => fake()->company(),
        ];
    }
}
