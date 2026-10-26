<?php

namespace Database\Factories;

use App\Models\Campus;
use App\Models\Medication;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medication>
 */
class MedicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'name' => fake()->word(),
            'dosage' => fake()->word(),
            'schedule' => fake()->word(),
            'purpose' => fake()->sentence(),
            'school_kept' => fake()->boolean(),
            'consent_on_file' => fake()->boolean(),
            'active' => true,
            'prescribed_by' => Staff::factory(),
            'campus_id' => Campus::factory(),
        ];
    }
}
