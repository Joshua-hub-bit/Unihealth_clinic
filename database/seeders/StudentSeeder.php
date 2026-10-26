<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentSeeder extends Seeder
{
    /**
     * Uses DB::table() to bypass the Eloquent 'hashed' cast.
     *
     * Demo credentials:
     *   delacruz+s202400001@gmail.com / password123
     */
    public function run(): void
    {
        $now = now();

        DB::table('students')->updateOrInsert(
            ['student_id' => 'S2024-00001'],
            [
                'student_id' => 'S2024-00001',
                'last_name' => 'Dela Cruz',
                'first_name' => 'Juan',
                'middle_name' => 'Santos',
                'email' => 'delacruz+s202400001@gmail.com',
                'password' => Hash::make('password123'),
                'course_year' => '2nd Year',
                'department' => 'Bachelor of Science in Nursing (BSN)',
                'section' => '2-A',
                'contact' => '09171234567',
                'address' => '123 Main St, Sagay City',
                'sex' => 'Male',
                'civil_status' => 'Single',
                'age' => 20,
                'blood_type' => 'O+',
                'role' => 'student',
                'is_active' => 1,
                'campus_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }
}
