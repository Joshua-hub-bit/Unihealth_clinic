<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'setting_key' => 'clinic_name',
                'setting_value' => 'SUNN Medical & Dental Services Office',
                'description' => 'Clinic name',
            ],
            [
                'setting_key' => 'clinic_address',
                'setting_value' => 'SUNN Main Campus, Sagay City, Negros Occidental',
                'description' => 'Clinic address',
            ],
            [
                'setting_key' => 'clinic_contact',
                'setting_value' => '(034) 703 8802',
                'description' => 'Clinic contact number',
            ],
            [
                'setting_key' => 'clinic_email',
                'setting_value' => 'info@sunn.edu.ph',
                'description' => 'Clinic email',
            ],
            [
                'setting_key' => 'default_physician',
                'setting_value' => [
                    'name' => 'DR. CYRILLE S. ADRANEDA',
                    'license' => '85606',
                    'title' => 'School Physician',
                ],
                'description' => 'Default physician for forms/certificates',
            ],
            [
                'setting_key' => 'default_dentist',
                'setting_value' => [
                    'name' => 'RHIZZA FAYE T. BARCENILLA, D.M.D',
                    'license' => '0006828',
                    'title' => 'School Dentist',
                ],
                'description' => 'Default dentist for forms/certificates',
            ],
            [
                'setting_key' => 'default_nurse',
                'setting_value' => [
                    'name' => 'Nurse Maria Santos',
                    'license' => 'RN-12345',
                    'title' => 'School Nurse',
                ],
                'description' => 'Default nurse for forms/certificates',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                $setting,
            );
        }
    }
}
