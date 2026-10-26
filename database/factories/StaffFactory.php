<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'employee_id' => strtoupper(fake()->unique()->bothify('EMP-#####')),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => fake()->randomElement(['nurse', 'nurse_head', 'physician', 'dentist', 'admin']),
            'license_no' => strtoupper(fake()->bothify('??-#####')),
            'contact' => fake()->phoneNumber(),
            'specialization' => fake()->jobTitle(),
            'is_active' => true,
            'campus_id' => null,
        ];
    }
}
