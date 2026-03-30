<?php

namespace Database\Factories;

use App\Support\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'role' => Role::EMPLOYEE_C,
            'is_active' => true,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin()
    {
        return $this->state(fn () => [
            'role' => Role::ADMIN,
        ]);
    }

    public function employeeA()
    {
        return $this->state(fn () => [
            'role' => Role::EMPLOYEE_A,
        ]);
    }

    public function employeeB()
    {
        return $this->state(fn () => [
            'role' => Role::EMPLOYEE_B,
        ]);
    }

    public function employeeC()
    {
        return $this->state(fn () => [
            'role' => Role::EMPLOYEE_C,
        ]);
    }

    public function inactive()
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
