<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PackageFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'code' => strtoupper(fake()->unique()->bothify('PKG###')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
