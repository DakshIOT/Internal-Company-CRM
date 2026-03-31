<?php

namespace Database\Factories;

use App\Models\AdminIncomeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminIncomeEntryFactory extends Factory
{
    protected $model = AdminIncomeEntry::class;

    public function definition()
    {
        return [
            'user_id' => User::factory()->admin(),
            'entry_date' => fake()->date(),
            'name' => fake()->words(3, true),
            'amount_minor' => fake()->numberBetween(1000, 250000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
