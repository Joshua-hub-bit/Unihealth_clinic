<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StaffSeeder extends Seeder
{
    /**
     * Uses DB::table() to bypass the Eloquent 'hashed' cast.
     * Hash::make() is called here so the plain-text password is
     * hashed exactly once before being written to the database.
     *
     * Demo credentials:
     *   admin@uni.edu      / admin123
     *   nurse@uni.edu      / nurse123
     *   doctor@uni.edu     / physician123
     *   dentist@uni.edu    / dentist123
     *   nursehead@uni.edu  / nursehead123
     */
    public function run(): void
    {
        $now = now();

        $members = [
            [
                'employee_id' => 'ADM-001',
                'last_name' => 'Admin',
                'first_name' => 'Director',
                'middle_name' => '',
                'email' => 'admin@uni.edu',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'license_no' => '',
                'contact' => '09171234567',
                'specialization' => 'System Administration',
                'is_active' => 1,
                'campus_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'employee_id' => 'NUR-001',
                'last_name' => 'Santos',
                'first_name' => 'Maria',
                'middle_name' => 'Cruz',
                'email' => 'nurse@uni.edu',
                'password' => Hash::make('nurse123'),
                'role' => 'nurse',
                'license_no' => 'RN-12345',
                'contact' => '09181234567',
                'specialization' => 'General Nursing',
                'is_active' => 1,
                'campus_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'employee_id' => 'PHY-001',
                'last_name' => 'Adraneda',
                'first_name' => 'Cyrille',
                'middle_name' => 'S.',
                'email' => 'doctor@uni.edu',
                'password' => Hash::make('physician123'),
                'role' => 'physician',
                'license_no' => 'MD-85606',
                'contact' => '09191234567',
                'specialization' => 'General Medicine',
                'is_active' => 1,
                'campus_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'employee_id' => 'DEN-001',
                'last_name' => 'Barcenilla',
                'first_name' => 'Rhizza Faye',
                'middle_name' => 'T.',
                'email' => 'dentist@uni.edu',
                'password' => Hash::make('dentist123'),
                'role' => 'dentist',
                'license_no' => 'DMD-0006828',
                'contact' => '09201234567',
                'specialization' => 'General Dentistry',
                'is_active' => 1,
                'campus_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'employee_id' => 'NHN-001',
                'last_name' => 'Reyes',
                'first_name' => 'Liza',
                'middle_name' => 'M.',
                'email' => 'nursehead@uni.edu',
                'password' => Hash::make('nursehead123'),
                'role' => 'nurse_head',
                'license_no' => 'RN-67890',
                'contact' => '09211234567',
                'specialization' => 'Head Nurse — Clinic Operations',
                'is_active' => 1,
                'campus_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($members as $member) {
            DB::table('staff')->updateOrInsert(
                ['employee_id' => $member['employee_id']],
                $member,
            );
        }
    }
}
