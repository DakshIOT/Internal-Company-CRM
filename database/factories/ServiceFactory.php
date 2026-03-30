<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('SRV###')),
            'standard_rate_minor' => fake()->numberBetween(10000, 500000),
            'notes' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
