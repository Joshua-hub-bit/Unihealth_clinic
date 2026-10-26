<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Campus;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'appt_date' => fake()->date(),
            'appt_time' => fake()->time('H:i:s'),
            'service_type' => fake()->randomElement(['Consultation', 'Dental Checkup', 'Vaccination', 'Laboratory']),
            'reason' => fake()->sentence(),
            'status' => fake()->randomElement(['pending', 'confirmed', 'completed', 'cancelled']),
            'notes' => fake()->optional()->sentence(),
            'created_by' => Staff::factory(),
            'campus_id' => Campus::factory(),
        ];
    }
}
