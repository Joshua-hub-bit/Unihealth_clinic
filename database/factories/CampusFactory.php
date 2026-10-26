<?php

namespace Database\Factories;

use App\Models\Campus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campus>
 */
class CampusFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Campus',
            'code' => strtoupper(fake()->unique()->bothify('???')),
            'address' => fake()->address(),
            'contact' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }
}
