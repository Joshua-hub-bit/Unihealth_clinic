<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\ClinicVisit;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClinicVisit>
 */
class ClinicVisitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'visit_date' => fake()->date(),
            'visit_time' => fake()->time('H:i:s'),
            'reason' => fake()->sentence(),
            'symptoms' => fake()->optional()->sentence(),
            'treatment' => fake()->optional()->sentence(),
            'bp' => fake()->optional()->numerify('###/##'),
            'temp' => fake()->optional()->randomFloat(1, 97, 102),
            'pulse' => fake()->optional()->numberBetween(60, 120),
            'rr' => fake()->optional()->numberBetween(12, 20),
            'o2_sat' => fake()->optional()->numberBetween(95, 100),
            'weight_kg' => fake()->optional()->randomFloat(2, 45, 100),
            'nurse_id' => Staff::factory(),
            'physician_id' => Staff::factory(),
            'disposition' => fake()->optional()->word(),
            'notes' => fake()->optional()->sentence(),
            'campus_id' => Campus::factory(),
        ];
    }
}
