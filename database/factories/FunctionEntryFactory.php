<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;

class FunctionEntryFactory extends Factory
{
    public function definition()
    {
        return [
            'venue_id' => Venue::factory(),
            'user_id' => User::factory()->employeeA(),
            'entry_date' => fake()->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d'),
            'name' => fake()->words(3, true),
            'notes' => fake()->sentence(),
            'package_total_minor' => 0,
            'extra_charge_total_minor' => 0,
            'discount_total_minor' => 0,
            'function_total_minor' => 0,
            'paid_total_minor' => 0,
            'pending_total_minor' => 0,
            'frozen_fund_minor' => 0,
            'net_total_after_frozen_fund_minor' => 0,
        ];
    }
}
