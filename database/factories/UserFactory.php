<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'google_sub' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'username' => Str::lower(str_replace('.', '_', fake()->unique()->userName())),
            'status' => 'active',
        ];
    }
}
