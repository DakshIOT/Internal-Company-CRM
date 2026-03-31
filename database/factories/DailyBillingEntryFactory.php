<?php

namespace Database\Factories;

use App\Models\DailyBillingEntry;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyBillingEntryFactory extends Factory
{
    protected $model = DailyBillingEntry::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->employeeA(),
            'venue_id' => Venue::factory(),
            'entry_date' => fake()->date(),
            'name' => fake()->words(3, true),
            'amount_minor' => fake()->numberBetween(1000, 250000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
