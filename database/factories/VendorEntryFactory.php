<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VendorEntry;
use App\Models\Venue;
use App\Models\VenueVendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorEntryFactory extends Factory
{
    protected $model = VendorEntry::class;

    public function definition()
    {
        $venue = Venue::factory();
        $vendor = VenueVendor::factory()->state(fn () => ['venue_id' => $venue]);

        return [
            'user_id' => User::factory()->employeeB(),
            'venue_id' => $venue,
            'venue_vendor_id' => $vendor,
            'vendor_name_snapshot' => fake()->company(),
            'entry_date' => fake()->date(),
            'name' => fake()->words(3, true),
            'amount_minor' => fake()->numberBetween(1000, 250000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
