<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'student_id' => strtoupper(fake()->unique()->bothify('S2024-#####')),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'course_year' => fake()->randomElement(['1st Year', '2nd Year', '3rd Year', '4th Year']),
            'department' => fake()->randomElement([
                'Bachelor of Science in Nursing (BSN)',
                'Bachelor of Science in Information Technology (BSIT)',
                'Bachelor of Science in Fisheries (BSF)',
                'Bachelor of Secondary Education Major in English (BSEd-English)',
                'Bachelor of Science in Business Administration major in Marketing Management (BSBA-MM)',
            ]),
            'section' => fake()->randomElement(['1-A', '2-B', '3-C']),
            'contact' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Married']),
            'age' => fake()->numberBetween(16, 30),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'profile_picture' => null,
            'role' => 'student',
            'is_active' => true,
            'campus_id' => null,
        ];
    }
}
