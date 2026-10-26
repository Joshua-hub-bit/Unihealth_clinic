<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\HealthRecord;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthRecord>
 */
class HealthRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'record_date' => fake()->date(),
            'last_name' => fake()->lastName(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->lastName(),
            'contact' => fake()->phoneNumber(),
            'course_year' => '2nd Year',
            'age' => fake()->numberBetween(16, 30),
            'date_of_birth' => fake()->date(),
            'blood_type' => fake()->randomElement(['A+', 'B+', 'O+', 'AB+']),
            'height_cm' => fake()->randomFloat(2, 150, 200),
            'weight_kg' => fake()->randomFloat(2, 45, 100),
            'ec_name' => fake()->name(),
            'ec_relationship' => fake()->word(),
            'ec_address' => fake()->address(),
            'ec_contact' => fake()->phoneNumber(),
            'allergy_detail' => fake()->optional()->sentence(),
            'bp' => fake()->optional()->numerify('###/##'),
            'pr' => fake()->optional()->numerify('###'),
            'temp' => fake()->optional()->randomFloat(1, 97, 102),
            'status' => 'submitted',
            'campus_id' => Campus::factory(),
        ];
    }
}
