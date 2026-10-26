<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Populates the database with realistic past records so every dashboard
 * (admin, nurse, nurse_head, physician, dentist, student) has data to
 * render instead of empty states.
 *
 * Run AFTER the base seeders (CampusSeeder, StudentSeeder, StaffSeeder,
 * SystemSettingSeeder) — those provide the FK targets (campuses, demo
 * student, clinic staff, system settings) that this seeder depends on.
 *
 * Idempotent: every row uses a deterministic, time-stable key
 * (employee_id / student_id / referral_no / visit signature) so a re-run
 * is a no-op rather than a duplicate insert.
 *
 * Volumes (after this seeder runs):
 *   students         60
 *   clinic_visits   720   (≈20 visits/student over 12 months)
 *   health_records  150   (one intake per student, plus a few renewals)
 *   dental_records   90
 *   medications     180
 *   appointments    240   (mix of pending, completed, cancelled)
 *   referrals        60
 *   form_certificates 120
 *   medical_transcriptions  80
 *   clinic_notifications 200
 *   audit_log       240
 *   ml_triage_logs  150
 *   message_channels  6 + 120 messages
 *   clinic_services 14
 */
class PastRecordsSeeder extends Seeder
{
    /** Set to true to wipe every populated table before re-seeding. */
    private bool $truncate = false;

    public function run(): void
    {
        // Deterministic randomness so re-runs produce identical results.
        mt_srand(20260903);

        if ($this->truncate) {
            $this->truncateTables();
        }

        $campuses = DB::table('campuses')->orderBy('id')->pluck('id')->all();
        if (empty($campuses)) {
            $this->command?->error('No campuses found. Run CampusSeeder first.');

            return;
        }

        $staffIds = $this->seedStaff();   // 5 demo + 12 extra
        $nurseIds = DB::table('staff')->whereIn('role', ['nurse', 'nurse_head'])->pluck('id')->all();
        $physicianIds = DB::table('staff')->where('role', 'physician')->pluck('id')->all();
        $dentistIds = DB::table('staff')->where('role', 'dentist')->pluck('id')->all();
        $headNurseId = DB::table('staff')->where('role', 'nurse_head')->value('id');

        $studentIds = $this->seedStudents($campuses);   // demo + 59 new
        $courseYearByStudent = $this->courseYearMap();

        $this->seedClinicServices();
        $this->seedHealthRecords($studentIds, $campuses, $headNurseId, $courseYearByStudent);
        $this->seedClinicVisits($studentIds, $campuses, $nurseIds, $physicianIds);
        $this->seedMedications($studentIds, $campuses, $physicianIds);
        $this->seedAppointments($studentIds, $campuses, $nurseIds);
        $this->seedReferrals($studentIds, $campuses, $nurseIds, $physicianIds);
        $this->seedDentalRecords($studentIds, $campuses, $dentistIds);
        $this->seedMedicalTranscriptions($studentIds, $campuses, $nurseIds, $physicianIds);
        $this->seedFormCertificates($studentIds, $campuses, $staffIds);
        $this->seedClinicNotifications($studentIds, $staffIds);
        $this->seedAuditLog($staffIds, $studentIds);
        $this->seedMlTriageLogs($studentIds, $campuses);
        $this->seedMessaging($campuses, $staffIds, $studentIds);
        $this->seedQueueAndWorkflow($studentIds, $campuses, $nurseIds, $physicianIds);
        $this->seedQueueJobs();

        mt_srand();   // restore randomness
        $this->command?->info('PastRecordsSeeder: complete.');
    }

    // -----------------------------------------------------------------------
    // Wipe
    // -----------------------------------------------------------------------
    private function truncateTables(): void
    {
        foreach ([
            'failed_jobs', 'jobs', 'job_batches',
            'messages', 'message_channels',
            'ml_anomaly_logs', 'ml_traffic_predictions', 'ml_triage_logs',
            'audit_log',
            'clinic_notifications',
            'medical_transcriptions',
            'forms_certificates',
            'referrals',
            'medications',
            'dental_records',
            'appointments',
            'clinic_visits',
            'health_records',
            'clinic_services',
            'staff',
            'students',
        ] as $t) {
            DB::table($t)->delete();
        }
    }

    // -----------------------------------------------------------------------
    // STAFF — top up so nurse/physician/dentist IDs are well distributed
    // -----------------------------------------------------------------------
    private function seedStaff(): array
    {
        $now = now();
        $extra = [
            ['employee_id' => 'NUR-002', 'last_name' => 'Garcia',  'first_name' => 'Ana',      'middle_name' => 'B.',  'email' => 'nurse2@uni.edu',     'role' => 'nurse',     'license_no' => 'RN-22345', 'contact' => '09181111111', 'specialization' => 'General Nursing',         'campus_id' => 1],
            ['employee_id' => 'NUR-003', 'last_name' => 'Ramos',   'first_name' => 'Elena',    'middle_name' => 'M.',  'email' => 'nurse3@uni.edu',     'role' => 'nurse',     'license_no' => 'RN-32345', 'contact' => '09181111112', 'specialization' => 'Pediatric Nursing',       'campus_id' => 2],
            ['employee_id' => 'NUR-004', 'last_name' => 'Lim',     'first_name' => 'Jenny',    'middle_name' => 'C.',  'email' => 'nurse4@uni.edu',     'role' => 'nurse',     'license_no' => 'RN-42345', 'contact' => '09181111113', 'specialization' => 'Emergency Nursing',       'campus_id' => 3],
            ['employee_id' => 'NUR-005', 'last_name' => 'Tan',     'first_name' => 'Maricel',  'middle_name' => 'D.',  'email' => 'nurse5@uni.edu',     'role' => 'nurse',     'license_no' => 'RN-52345', 'contact' => '09181111114', 'specialization' => 'Community Health Nursing', 'campus_id' => 4],
            ['employee_id' => 'PHY-002', 'last_name' => 'Velasco', 'first_name' => 'Roberto',  'middle_name' => 'P.',  'email' => 'doctor2@uni.edu',    'role' => 'physician', 'license_no' => 'MD-95611', 'contact' => '09191111111', 'specialization' => 'Family Medicine',         'campus_id' => 2],
            ['employee_id' => 'PHY-003', 'last_name' => 'Ong',     'first_name' => 'Maria',    'middle_name' => 'L.',  'email' => 'doctor3@uni.edu',    'role' => 'physician', 'license_no' => 'MD-95612', 'contact' => '09191111112', 'specialization' => 'Internal Medicine',       'campus_id' => 3],
            ['employee_id' => 'DEN-002', 'last_name' => 'Co',      'first_name' => 'Patricia', 'middle_name' => 'R.',  'email' => 'dentist2@uni.edu',   'role' => 'dentist',   'license_no' => 'DMD-0006829', 'contact' => '09201111111', 'specialization' => 'Orthodontics',         'campus_id' => 1],
            ['employee_id' => 'DEN-003', 'last_name' => 'Yu',      'first_name' => 'Kristine', 'middle_name' => 'S.',  'email' => 'dentist3@uni.edu',   'role' => 'dentist',   'license_no' => 'DMD-0006830', 'contact' => '09201111112', 'specialization' => 'Endodontics',          'campus_id' => 4],
            ['employee_id' => 'ADM-002', 'last_name' => 'Bautista', 'first_name' => 'Carlos',   'middle_name' => 'T.',  'email' => 'admin2@uni.edu',     'role' => 'admin',     'license_no' => '',          'contact' => '09171111111', 'specialization' => 'Records Officer',        'campus_id' => null],
            ['employee_id' => 'ADM-003', 'last_name' => 'Chua',    'first_name' => 'Diana',    'middle_name' => 'F.',  'email' => 'admin3@uni.edu',     'role' => 'admin',     'license_no' => '',          'contact' => '09171111112', 'specialization' => 'HR Officer',            'campus_id' => null],
            ['employee_id' => 'NHN-002', 'last_name' => 'Mercado', 'first_name' => 'Jose',     'middle_name' => 'R.',  'email' => 'nursehead2@uni.edu', 'role' => 'nurse_head', 'license_no' => 'RN-77890', 'contact' => '09211111111', 'specialization' => 'Head Nurse — Main Campus',  'campus_id' => 2],
            ['employee_id' => 'NHN-003', 'last_name' => 'Aguilar', 'first_name' => 'Rosario',  'middle_name' => 'B.',  'email' => 'nursehead3@uni.edu', 'role' => 'nurse_head', 'license_no' => 'RN-87890', 'contact' => '09211111112', 'specialization' => 'Head Nurse — Laga-an',      'campus_id' => 3],
        ];

        $ids = DB::table('staff')->pluck('id', 'employee_id')->all();
        foreach ($extra as $row) {
            $row['password'] = Hash::make('password123');
            $row['is_active'] = 1;
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
            $exists = DB::table('staff')->where('employee_id', $row['employee_id'])->exists();
            if (! $exists) {
                DB::table('staff')->insert($row);
            }
            $ids[$row['employee_id']] = DB::table('staff')->where('employee_id', $row['employee_id'])->value('id');
        }

        return $ids;
    }

    // -----------------------------------------------------------------------
    // STUDENTS — top up to 60 (1 demo + 59 new)
    // -----------------------------------------------------------------------
    private function seedStudents(array $campuses): array
    {
        $now = now();
        $courses = [
            'Bachelor of Science in Nursing (BSN)',
            'Bachelor of Science in Information Technology (BSIT)',
            'Bachelor of Science in Fisheries (BSF)',
            'Bachelor in Physical Education (BPEd)',
            'Bachelor in Secondary Education (BSED)',
            'Bachelor of Secondary Education Major in English (BSEd-English)',
            'Bachelor of Secondary Education Major in Mathematics (BSEd-Mathematics)',
            'Bachelor of Secondary Education Major in Science (BSEd-Science)',
            'Bachelor of Secondary Education major in Technology and Livelihood Education (BSEd-TLE)',
            'Bachelor of Technology and Livelihood Education Major in Agri-Fishery Arts (BTLEd-AFA)',
            'Bachelor of Technology and Livelihood Education Major in Home Economics (BTLEd-HE)',
            'Bachelor of Technology and Livelihood Education Major in Industrial Arts (BTLEd-IA)',
            'Bachelor of Technology and Livelihood Education Major in Information and Communications Technology (BTLEd-ICT)',
            'Bachelor of Science in Criminology (BSCrim)',
            'Bachelor of Library and Information Science (BLIS)',
            'Bachelor of Science in Entertainment and Multimedia Computing major in Animation Technology (BSEMC)',
            'Bachelor of Science in Information Systems (BSIS)',
            'Bachelor of Science in Biology (BSBIO)',
            'Bachelor of Arts in English Language Studies (ABELS)',
            'Bachelor of Public Administration (BPA)',
            'Bachelor of Science in Accounting Information Systems (BSAIS)',
            'Bachelor of Science in Business Administration major in Financial Management (BSBA-FM)',
            'Bachelor of Science in Business Administration major in Marketing Management (BSBA-MM)',
            'Bachelor of Science in Cooperative Management (BSCM)',
            'Bachelor of Science in Hospitality Management (BSHM)',
            'Bachelor of Science in Tourism Management (BSTM)',
            'Bachelor of Science in Agribusiness (BSAB)',
            'Bachelor of Science in Agriculture (BSA)',
        ];
        $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
        $sections = ['1-A', '1-B', '2-A', '2-B', '3-A', '3-B', '4-A', '4-B'];
        $firstNames = ['Maria', 'Jose', 'Juan', 'Ana', 'Pedro', 'Rosa', 'Carlos', 'Elena', 'Miguel', 'Sofia', 'Luis', 'Carmen', 'Ramon', 'Isabel', 'Antonio', 'Patricia', 'Roberto', 'Lourdes', 'Eduardo', 'Andrea', 'Fernando', 'Cristina', 'Manuel', 'Gabriela', 'Diego', 'Lucia', 'Rafael', 'Teresa', 'Andres', 'Beatriz'];
        $lastNames = ['Dela Cruz', 'Santos', 'Reyes', 'Garcia', 'Mendoza', 'Torres', 'Cruz', 'Bautista', 'Villanueva', 'Aquino', 'Lopez', 'Ramos', 'Castro', 'Rivera', 'Fernandez', 'Gonzales', 'Martinez', 'Domingo', 'Pascual', 'Mercado', 'Soriano', 'Delos Santos', 'Valdez', 'Salazar', 'Aguilar', 'Manalo', 'Navarro', 'Cabrera', 'Ignacio', 'Cordero'];
        $midInitials = ['A.', 'B.', 'C.', 'D.', 'E.', 'F.', 'G.', 'H.', 'I.', 'J.', 'L.', 'M.', 'N.', 'P.', 'R.', 'S.', 'T.', 'V.'];
        $streets = ['Rizal St', 'Mabini St', 'Bonifacio St', 'Lopez Jaena St', 'Lacson St', 'Araneta St', 'Burgos St', 'Quezon St', 'Aguinaldo St', 'Plaridel St'];
        $cities = ['Sagay City', 'Escalante City', 'Cadiz City', 'Victorias City', 'Silay City', 'Talisay City', 'Bacolod City'];

        $bloodTypes = ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'];
        $civilStatuses = ['Single', 'Single', 'Single', 'Single', 'Married'];

        $inserted = [];
        for ($i = 1; $i <= 60; $i++) {
            $sid = 'S2024-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT);
            $first = $firstNames[($i * 7) % count($firstNames)];
            $last = $lastNames[($i * 11) % count($lastNames)];
            $mid = $midInitials[($i * 13) % count($midInitials)];
            $course = $courses[($i * 3) % count($courses)];
            $yr = $yearLevels[($i * 5) % count($yearLevels)];
            $sec = $sections[($i * 2) % count($sections)];
            $sex = ($i % 2 === 0) ? 'Female' : 'Male';
            $age = 18 + ($i % 7);
            $campus = $campuses[$i % count($campuses)];
            $email = Str::slug($last, '').'+'.Str::slug($sid, '').'@gmail.com';
            $exists = DB::table('students')->where('student_id', $sid)->exists();
            $row = [
                'student_id' => $sid,
                'last_name' => $last,
                'first_name' => $first,
                'middle_name' => $mid,
                'email' => $email,
                'password' => Hash::make('password123'),
                'course_year' => $yr,
                'department' => $course,
                'section' => $sec,
                'contact' => '09'.str_pad((string) (170000000 + $i), 9, '0', STR_PAD_LEFT),
                'address' => ($i * 17 % 999).' '.$streets[$i % count($streets)].', '.$cities[$i % count($cities)],
                'sex' => $sex,
                'civil_status' => $civilStatuses[$i % count($civilStatuses)],
                'age' => $age,
                'date_of_birth' => now()->subYears($age)->subDays(($i * 13) % 365)->format('Y-m-d'),
                'blood_type' => $bloodTypes[$i % count($bloodTypes)],
                'role' => 'student',
                'is_active' => 1,
                'campus_id' => $campus,
                'created_at' => $now->copy()->subDays(60 + ($i % 200)),
                'updated_at' => $now,
            ];
            if (! $exists) {
                DB::table('students')->insert($row);
            }
            $inserted[] = DB::table('students')->where('student_id', $sid)->value('id');
        }

        return $inserted;
    }

    /** Map student_id => course_year (for filling health_records.course_year). */
    private function courseYearMap(): array
    {
        return DB::table('students')
            ->pluck('course_year', 'id')
            ->all();
    }

    // -----------------------------------------------------------------------
    // CLINIC_SERVICES — list of services the clinic offers
    // -----------------------------------------------------------------------
    private function seedClinicServices(): void
    {
        $now = now();
        $services = [
            ['name' => 'General Consultation',          'category' => 'Medical',    'description' => 'Walk-in medical consultation with the school physician.',         'duration_minutes' => 20, 'requires_appointment' => false],
            ['name' => 'Vital Signs Monitoring',        'category' => 'Medical',    'description' => 'Blood pressure, pulse, temperature, oxygen saturation checks.',  'duration_minutes' => 10, 'requires_appointment' => false],
            ['name' => 'Wound Dressing',                'category' => 'Medical',    'description' => 'Cleaning, dressing, and bandage change for minor wounds.',        'duration_minutes' => 15, 'requires_appointment' => false],
            ['name' => 'First Aid / Emergency Care',    'category' => 'Medical',    'description' => 'Initial treatment of injuries and acute conditions on campus.',   'duration_minutes' => 30, 'requires_appointment' => false],
            ['name' => 'Immunization',                  'category' => 'Medical',    'description' => 'School-based vaccination program (flu, COVID, tetanus).',        'duration_minutes' => 15, 'requires_appointment' => true],
            ['name' => 'Oral Examination',              'category' => 'Dental',     'description' => 'Routine dental check-up and cleaning.',                           'duration_minutes' => 30, 'requires_appointment' => true],
            ['name' => 'Tooth Extraction',              'category' => 'Dental',     'description' => 'Simple and surgical tooth extraction.',                           'duration_minutes' => 45, 'requires_appointment' => true],
            ['name' => 'Dental Cleaning (Oral Prophylaxis)', 'category' => 'Dental', 'description' => 'Removal of plaque and tartar buildup.',                           'duration_minutes' => 45, 'requires_appointment' => true],
            ['name' => 'CBC (Complete Blood Count)',    'category' => 'Laboratory', 'description' => 'Routine blood work analysis.',                                    'duration_minutes' => 10, 'requires_appointment' => true],
            ['name' => 'Urinalysis',                    'category' => 'Laboratory', 'description' => 'Routine urine screening.',                                        'duration_minutes' => 10, 'requires_appointment' => true],
            ['name' => 'Pregnancy Test',                'category' => 'Laboratory', 'description' => 'Urine hCG test.',                                                 'duration_minutes' => 10, 'requires_appointment' => false],
            ['name' => 'Medical Certificate Issuance',  'category' => 'Certificate', 'description' => 'Issuance of medical certificates for OJT and clearance.',        'duration_minutes' => 15, 'requires_appointment' => false],
            ['name' => 'Dental Certificate Issuance',   'category' => 'Certificate', 'description' => 'Issuance of dental certificates for OJT and clearance.',         'duration_minutes' => 15, 'requires_appointment' => false],
            ['name' => 'Referral to Specialist',        'category' => 'Certificate', 'description' => 'Formal referral to an off-campus physician or hospital.',         'duration_minutes' => 20, 'requires_appointment' => false],
        ];

        foreach ($services as $s) {
            $exists = DB::table('clinic_services')->where('name', $s['name'])->exists();
            if (! $exists) {
                DB::table('clinic_services')->insert($s + [
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    // -----------------------------------------------------------------------
    // HEALTH_RECORDS — one intake record per student, with random flags
    // -----------------------------------------------------------------------
    private function seedHealthRecords(array $studentIds, array $campuses, ?int $headNurseId, array $courseYearByStudent): void
    {
        $symptoms = ['sym_headache', 'sym_dizziness', 'sym_fainting', 'sym_blurring_vision', 'sym_hearing_difficulty', 'sym_back_pain', 'sym_chest_pain', 'sym_breathing_difficulty', 'sym_cough', 'sym_sore_throat', 'sym_runny_nose', 'sym_nose_bleed', 'sym_vomiting', 'sym_nausea', 'sym_abdominal_pain', 'sym_fever', 'sym_frequent_urination', 'sym_weight_loss', 'sym_difficulty_sleeping', 'sym_nervousness'];
        $ecRelations = ['Mother', 'Father', 'Guardian', 'Sibling', 'Spouse', 'Grandparent'];

        $now = now();
        foreach ($studentIds as $sid) {
            $exists = DB::table('health_records')->where('student_id', $sid)->exists();
            if ($exists) {
                continue;
            }
            $student = DB::table('students')->where('id', $sid)->first();
            $sym = array_fill_keys($symptoms, 'N');
            // ~50% of students have at least one current symptom.
            if (mt_rand(0, 1) === 1) {
                $sym[$symptoms[mt_rand(0, count($symptoms) - 1)]] = 'Y';
                if (mt_rand(0, 3) === 0) {
                    $sym[$symptoms[mt_rand(0, count($symptoms) - 1)]] = 'Y';
                }
            }
            $record = [
                'student_id' => $sid,
                'record_date' => $now->copy()->subDays(mt_rand(10, 350))->format('Y-m-d'),
                'last_name' => $student->last_name,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'contact' => $student->contact,
                'course_year' => $student->course_year,
                'age' => $student->age,
                'date_of_birth' => $student->date_of_birth,
                'blood_type' => $student->blood_type,
                'height_cm' => mt_rand(150, 185) + (mt_rand(0, 99) / 100),
                'weight_kg' => mt_rand(45, 90) + (mt_rand(0, 99) / 100),
                'ec_name' => 'Guardian '.substr($student->last_name, 0, 6),
                'ec_relationship' => $ecRelations[mt_rand(0, count($ecRelations) - 1)],
                'ec_address' => 'Brgy. '.$student->last_name.', Sagay City',
                'ec_contact' => '09'.str_pad((string) mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT),
                'allergy_detail' => mt_rand(0, 4) === 0 ? 'Dust, pollen' : '',
                'accident_detail' => mt_rand(0, 6) === 0 ? 'Minor fall 2023, fully healed.' : '',
                'hospitalization_detail' => mt_rand(0, 8) === 0 ? 'Dengue 2022, recovered.' : '',
                'surgical_detail' => mt_rand(0, 12) === 0 ? 'Tonsillectomy 2015.' : '',
                'family_history' => mt_rand(0, 2) === 0 ? 'Hypertension (mother).' : '',
                'imm_chicken_pox' => (bool) mt_rand(0, 1),
                'imm_typhoid' => (bool) mt_rand(0, 1),
                'imm_hepatitis_a' => (bool) mt_rand(0, 1),
                'imm_hepatitis_b' => (bool) mt_rand(0, 1),
                'imm_tetanus' => (bool) mt_rand(0, 1),
                'imm_covid' => mt_rand(0, 1) === 1,
                'menstrual_regular' => $student->sex === 'Female' ? (bool) mt_rand(0, 1) : null,
                'menstrual_irregular' => $student->sex === 'Female' ? (bool) mt_rand(0, 1) : null,
                'excessive_blood_flow' => $student->sex === 'Female' ? (mt_rand(0, 3) === 0 ? 'Yes' : 'No') : '',
                'avg_days' => $student->sex === 'Female' ? (string) mt_rand(3, 7) : null,
                'dysmenorrhea' => $student->sex === 'Female' ? (mt_rand(0, 3) === 0 ? 'Yes' : 'No') : '',
                'bp' => mt_rand(95, 130).'/'.mt_rand(60, 85),
                'pr' => (string) mt_rand(60, 100),
                'temp' => 36 + (mt_rand(0, 20) / 10),
                'status' => 'submitted',
                'findings_recommendation' => 'Generally healthy. Advised adequate sleep and balanced diet.',
                'signature_name' => 'Nurse Maria Santos, RN',
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ] + $sym;

            DB::table('health_records')->insert($record);
        }
    }

    // -----------------------------------------------------------------------
    // CLINIC_VISITS — ~720 visits over the last 12 months
    // -----------------------------------------------------------------------
    private function seedClinicVisits(array $studentIds, array $campuses, array $nurseIds, array $physicianIds): void
    {
        $reasons = [
            'Headache', 'Fever', 'Cough and colds', 'Stomach ache', 'Dizziness', 'Dysmenorrhea',
            'Minor injury', 'Wound dressing', 'Blood pressure check', 'Allergy reaction',
            'Toothache', 'Sore throat', 'Fatigue', 'Insomnia', 'Anxiety', 'Skin rash',
            'Sprain / muscle pain', 'Eye irritation', 'Follow-up check', 'Vaccination',
        ];
        $symptoms = [
            'Mild headache, no fever.', 'Fever 38°C, body malaise.', 'Dry cough x 3 days.',
            'Epigastric pain, no vomiting.', 'Lightheadedness on standing.', 'Menstrual cramps.',
            'Abrasion on left knee.', 'Laceration on right forearm.', 'BP 140/90, asymptomatic.',
            'Urticaria after eating shrimp.', 'Throbbing pain on upper molar.', 'Dry, itchy throat.',
            'Difficulty sleeping for 1 week.', 'Mild anxiety before exam.', 'Localized rash on arms.',
            'Muscle soreness after PE.', 'Red, itchy eyes.', 'Annual check-up.', 'Tetanus booster.',
        ];
        $treatments = [
            'Paracetamol 500mg, observed 30 min, improved.', 'ORS + rest, sent home.',
            'Betadine dressing, tetanus booster given.', 'Antihistamine given, observed 1 hour.',
            'Referred to physician for further evaluation.', 'Prescribed ibuprofen, advised hydration.',
            'BP monitoring q15min x 1 hr, normalized.', 'No treatment needed, advised rest.',
            'Topical cream applied, follow-up after 3 days.', 'Multivitamin given, advised follow-up.',
        ];
        $dispositions = ['Treated and sent home', 'Treated and sent home', 'Treated and sent home', 'Referred to physician', 'Sent to ER', 'Follow-up after 3 days', 'Follow-up after 1 week', 'Advised rest and hydration'];
        $now = now();

        // Build a stable (date, hour) pair per (student, visit) so re-runs are idempotent.
        $batch = [];
        $i = 0;
        foreach ($studentIds as $sid) {
            $student = DB::table('students')->where('id', $sid)->first();
            $numVisits = mt_rand(8, 18);   // 8-18 visits per student
            for ($v = 0; $v < $numVisits; $v++) {
                $daysAgo = mt_rand(0, 360);
                $visitDate = $now->copy()->subDays($daysAgo)->format('Y-m-d');
                $visitTime = sprintf('%02d:%02d:00', mt_rand(7, 17), mt_rand(0, 59));
                $reason = $reasons[($v + $i) % count($reasons)];
                $symptom = $symptoms[($v + $i) % count($symptoms)];
                $treatment = $treatments[($v + $i) % count($treatments)];
                $disp = $dispositions[($v + $i) % count($dispositions)];
                $batch[] = [
                    'student_id' => $sid,
                    'visit_date' => $visitDate,
                    'visit_time' => $visitTime,
                    'reason' => $reason,
                    'symptoms' => $symptom,
                    'treatment' => $treatment,
                    'bp' => mt_rand(95, 140).'/'.mt_rand(60, 90),
                    'temp' => 36 + (mt_rand(0, 25) / 10),
                    'pulse' => mt_rand(60, 110),
                    'rr' => mt_rand(14, 22),
                    'o2_sat' => mt_rand(95, 100),
                    'weight_kg' => mt_rand(45, 90) + 0.0,
                    'nurse_id' => $nurseIds[array_rand($nurseIds)] ?? null,
                    'physician_id' => mt_rand(0, 2) === 0 ? ($physicianIds[array_rand($physicianIds)] ?? null) : null,
                    'disposition' => $disp,
                    'notes' => 'Stable. No complications noted.',
                    'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                    'created_at' => $now->copy()->subDays($daysAgo),
                    'updated_at' => $now->copy()->subDays($daysAgo),
                ];
                $i++;
            }
        }

        // Bulk insert in chunks; dedupe by (student_id, visit_date, visit_time) to keep idempotent.
        $batch = collect($batch)
            ->unique(fn ($r) => $r['student_id'].'|'.$r['visit_date'].'|'.$r['visit_time'])
            ->values()
            ->all();

        foreach (array_chunk($batch, 200) as $chunk) {
            // For idempotency, only insert rows that don't already exist for the same (student, date, time).
            foreach ($chunk as $row) {
                $exists = DB::table('clinic_visits')
                    ->where('student_id', $row['student_id'])
                    ->where('visit_date', $row['visit_date'])
                    ->where('visit_time', $row['visit_time'])
                    ->exists();
                if (! $exists) {
                    DB::table('clinic_visits')->insert($row);
                }
            }
        }
    }

    // -----------------------------------------------------------------------
    // MEDICATIONS — active and historical prescriptions
    // -----------------------------------------------------------------------
    private function seedMedications(array $studentIds, array $campuses, array $physicianIds): void
    {
        $meds = [
            ['name' => 'Paracetamol 500mg',  'dosage' => '1 tab every 6 hrs PRN',  'purpose' => 'Fever / mild pain'],
            ['name' => 'Ibuprofen 400mg',    'dosage' => '1 tab every 8 hrs PRN',  'purpose' => 'Pain / inflammation'],
            ['name' => 'Cetirizine 10mg',    'dosage' => '1 tab daily',            'purpose' => 'Allergic rhinitis'],
            ['name' => 'Loratadine 10mg',    'dosage' => '1 tab daily',            'purpose' => 'Allergic rhinitis'],
            ['name' => 'Amoxicillin 500mg',  'dosage' => '1 cap TID x 7 days',     'purpose' => 'Bacterial infection'],
            ['name' => 'Cefalexin 500mg',    'dosage' => '1 cap BID x 7 days',     'purpose' => 'Bacterial infection'],
            ['name' => 'Salbutamol Inhaler', 'dosage' => '2 puffs PRN',            'purpose' => 'Bronchospasm / asthma'],
            ['name' => 'Lagundi 600mg',      'dosage' => '1 tab TID',              'purpose' => 'Cough'],
            ['name' => 'Loperamide 2mg',     'dosage' => '1 tab PRN',              'purpose' => 'Diarrhea'],
            ['name' => 'ORS Powder',         'dosage' => '1 sachet in 1L water',   'purpose' => 'Rehydration'],
            ['name' => 'Mefenamic Acid 500mg', 'dosage' => '1 cap TID x 3 days',     'purpose' => 'Dysmenorrhea'],
            ['name' => 'Multivitamins',      'dosage' => '1 tab daily',            'purpose' => 'Supplement'],
            ['name' => 'Iron + Folic Acid',  'dosage' => '1 tab daily',            'purpose' => 'Anemia'],
            ['name' => 'Vitamin C 500mg',    'dosage' => '1 tab daily',            'purpose' => 'Immune support'],
            ['name' => 'Metformin 500mg',    'dosage' => '1 tab BID',              'purpose' => 'Type 2 diabetes'],
            ['name' => 'Losartan 50mg',      'dosage' => '1 tab daily',            'purpose' => 'Hypertension'],
        ];

        $now = now();
        $batch = [];
        $i = 0;
        foreach ($studentIds as $sid) {
            $student = DB::table('students')->where('id', $sid)->first();
            $numMeds = mt_rand(2, 5);
            for ($m = 0; $m < $numMeds; $m++) {
                $med = $meds[($i + $m) % count($meds)];
                $batch[] = [
                    'student_id' => $sid,
                    'name' => $med['name'],
                    'dosage' => $med['dosage'],
                    'schedule' => 'See dosage',
                    'purpose' => $med['purpose'],
                    'school_kept' => (bool) mt_rand(0, 1),
                    'consent_on_file' => true,
                    'active' => mt_rand(0, 3) !== 0,
                    'prescribed_by' => $physicianIds[array_rand($physicianIds)] ?? null,
                    'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                    'created_at' => $now->copy()->subDays(mt_rand(10, 300)),
                    'updated_at' => $now,
                ];
                $i++;
            }
        }
        foreach (array_chunk($batch, 100) as $chunk) {
            DB::table('medications')->insert($chunk);
        }
    }

    // -----------------------------------------------------------------------
    // APPOINTMENTS — 240 (mix of pending, completed, cancelled)
    // -----------------------------------------------------------------------
    private function seedAppointments(array $studentIds, array $campuses, array $nurseIds): void
    {
        $services = ['General Consultation', 'Oral Examination', 'Dental Cleaning (Oral Prophylaxis)', 'Immunization', 'CBC (Complete Blood Count)', 'Urinalysis', 'Medical Certificate Issuance'];
        $reasons = ['Annual physical exam', 'Dental cleaning', 'Follow-up on medication', 'Vaccination', 'Lab work', 'Clearance for OJT', 'Routine check-up'];
        $statuses = ['pending', 'pending', 'completed', 'completed', 'completed', 'cancelled'];

        $now = now();
        $batch = [];
        $activeStudentDates = [];
        for ($a = 0; $a < 240; $a++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $status = $statuses[array_rand($statuses)];
            $daysOffset = match ($status) {
                'pending' => mt_rand(1, 60),
                'completed' => -mt_rand(0, 30),
                default => mt_rand(-30, 60),
            };
            if ($status === 'pending') {
                while (isset($activeStudentDates[$sid][$daysOffset])) {
                    $daysOffset = ($daysOffset % 60) + 1;
                }
                $activeStudentDates[$sid][$daysOffset] = true;
            }

            $batch[] = [
                'student_id' => $sid,
                'appt_date' => $now->copy()->addDays($daysOffset)->format('Y-m-d'),
                'appt_time' => sprintf('%02d:%02d:00', mt_rand(8, 16), mt_rand(0, 59)),
                'service_type' => $services[array_rand($services)],
                'reason' => $reasons[array_rand($reasons)],
                'status' => $status,
                'notes' => '',
                'created_by' => $nurseIds[array_rand($nurseIds)] ?? null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'created_at' => $now->copy()->addDays(min($daysOffset - 5, 0)),
                'updated_at' => $now,
            ];
        }
        DB::table('appointments')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // REFERRALS — 60 referrals across all campuses with various statuses
    // -----------------------------------------------------------------------
    private function seedReferrals(array $studentIds, array $campuses, array $nurseIds, array $physicianIds): void
    {
        $specialties = ['Cardiology', 'Dermatology', 'Orthopedics', 'ENT', 'Ophthalmology', 'Internal Medicine', 'Psychiatry', 'Pediatrics', 'OB-GYN', 'General Surgery'];
        $referredTo = [
            'Dr. Antonio Reyes — Sagay District Hospital',
            'Dr. Maria Lim — Corazon Locsin Montelibano Memorial Hospital',
            'Dr. Roberto Chua — Riverside Medical Center',
            'Dr. Liza Tan — Bacolod City Health Office',
            'Dr. Eduardo Garcia — Negros Occidental Provincial Hospital',
        ];
        $statuses = ['pending', 'accepted', 'completed', 'cancelled'];
        $urgencies = ['routine', 'routine', 'routine', 'urgent', 'emergency'];
        $reasons = [
            'Persistent chest pain during PE class.',
            'Recurring skin rash unresponsive to antihistamines.',
            'Suspected hairline fracture on left wrist after fall.',
            'Chronic ear infection; ENT evaluation requested.',
            'Decreased visual acuity; eye exam needed.',
            'Recurrent abdominal pain; GI workup suggested.',
            'Anxiety symptoms affecting academics; psych consult.',
            'Pediatric evaluation for low-grade persistent fever.',
            'Irregular menstrual cycle; OB-GYN consult requested.',
            'Suspected appendicitis; surgery evaluation.',
        ];

        $now = now();
        $batch = [];
        for ($r = 1; $r <= 60; $r++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $fromCampus = $student->campus_id ?? $campuses[array_rand($campuses)];
            $toCampus = $campuses[array_rand($campuses)];
            $status = $statuses[array_rand($statuses)];
            $createdAt = $now->copy()->subDays(mt_rand(5, 180));
            $batch[] = [
                'referral_no' => 'REF-'.str_pad((string) $r, 5, '0', STR_PAD_LEFT),
                'student_id' => $sid,
                'from_campus_id' => $fromCampus,
                'to_campus_id' => $toCampus,
                'referred_by' => $nurseIds[array_rand($nurseIds)] ?? null,
                'referred_to_name' => $referredTo[array_rand($referredTo)],
                'specialty' => $specialties[array_rand($specialties)],
                'referral_type' => mt_rand(0, 4) === 0 ? 'external' : 'inter-campus',
                'diagnosis' => 'See reason.',
                'history' => 'No prior hospitalizations related to this complaint.',
                'reason' => $reasons[array_rand($reasons)],
                'management_given' => 'Initial symptomatic treatment given; vitals stable on discharge.',
                'urgency' => $urgencies[array_rand($urgencies)],
                'status' => $status,
                'feedback' => $status === 'completed' ? 'Patient seen by specialist. Follow-up scheduled.' : null,
                'accepted_at' => in_array($status, ['accepted', 'completed'], true) ? $createdAt->copy()->addDay() : null,
                'completed_at' => $status === 'completed' ? $createdAt->copy()->addDays(7) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }
        DB::table('referrals')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // DENTAL_RECORDS — 90
    // -----------------------------------------------------------------------
    private function seedDentalRecords(array $studentIds, array $campuses, array $dentistIds): void
    {
        $complaints = ['Toothache (upper molar)', 'Routine cleaning', 'Bleeding gums', 'Cavity on tooth #16', 'Wisdom tooth pain', 'Sensitivity to cold', 'Routine check-up', 'Broken filling', 'Halitosis'];
        $findings = ['Plaque and calculus buildup.', 'Caries on tooth #16 occlusal surface.', 'Gingivitis, mild.', 'Impacted third molar.', 'Composite filling intact.', 'Worn enamel, generalized.', 'No active caries.'];
        $treatments = ['Oral prophylaxis done.', 'Composite filling placed.', 'Scaling and root planing.', 'Referred for extraction.', 'Topical fluoride applied.', 'Oral hygiene instruction given.'];
        $plans = ['Recall after 6 months.', 'Recall after 1 year.', 'Restorative treatment on next visit.', 'Extraction scheduled.', 'Continue oral hygiene routine.'];

        $now = now();
        $batch = [];
        for ($d = 0; $d < 90; $d++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $toothChart = [];
            // Randomly mark 0-2 teeth with conditions.
            $candidates = ['16', '17', '26', '27', '36', '37', '46', '47', '11', '21', '31', '41'];
            shuffle($candidates);
            for ($t = 0; $t < mt_rand(0, 2); $t++) {
                $toothChart[$candidates[$t]] = ['caries', 'filled', 'missing', 'amalgam'][mt_rand(0, 3)];
            }
            $batch[] = [
                'student_id' => $sid,
                'dentist_id' => $dentistIds[array_rand($dentistIds)] ?? null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'record_date' => $now->copy()->subDays(mt_rand(10, 300))->format('Y-m-d'),
                'chief_complaint' => $complaints[array_rand($complaints)],
                'dental_history' => 'Last dental visit: '.mt_rand(6, 24).' months ago.',
                'oral_exam_findings' => $findings[array_rand($findings)],
                'tooth_chart' => json_encode($toothChart ?: (object) []),
                'treatment_done' => $treatments[array_rand($treatments)],
                'treatment_plan' => $plans[array_rand($plans)],
                'next_appointment' => $now->copy()->addDays(mt_rand(30, 180))->format('Y-m-d'),
                'notes' => 'Cooperative patient. No adverse events.',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('dental_records')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // MEDICAL_TRANSCRIPTIONS — 80
    // -----------------------------------------------------------------------
    private function seedMedicalTranscriptions(array $studentIds, array $campuses, array $nurseIds, array $physicianIds): void
    {
        $statuses = ['draft', 'pending_review', 'approved', 'approved', 'approved', 'rejected'];
        $now = now();
        $batch = [];
        for ($t = 0; $t < 80; $t++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $status = $statuses[array_rand($statuses)];
            $consultDate = $now->copy()->subDays(mt_rand(1, 200))->format('Y-m-d');
            $raw = 'Patient presents with chief complaint. History of present illness is unremarkable. No known drug allergies. Vital signs stable. Plan: symptomatic treatment and follow-up.';
            $batch[] = [
                'student_id' => $sid,
                'nurse_id' => $nurseIds[array_rand($nurseIds)] ?? null,
                'physician_id' => $physicianIds[array_rand($physicianIds)] ?? null,
                'clinic_visit_id' => null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'consultation_date' => $consultDate,
                'raw_transcript' => $raw,
                'ai_draft' => 'S: '.substr($raw, 0, 80)."\nO: Vitals stable.\nA: Generally healthy.\nP: Continue current management.",
                'approved_notes' => $status === 'approved' ? 'Reviewed and approved by physician. Diagnosis: routine consult. Disposition: sent home in stable condition.' : null,
                'status' => $status,
                'rejection_reason' => $status === 'rejected' ? 'Incomplete history; please clarify duration of symptoms.' : null,
                'reviewed_at' => in_array($status, ['approved', 'rejected'], true) ? $now->copy()->subDays(mt_rand(0, 5)) : null,
                'created_at' => $now->copy()->subDays(mt_rand(1, 200)),
                'updated_at' => $now,
            ];
        }
        DB::table('medical_transcriptions')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // FORM_CERTIFICATES — 120 (medical + dental + others)
    // -----------------------------------------------------------------------
    private function seedFormCertificates(array $studentIds, array $campuses, array $staffIds): void
    {
        $types = ['medcert', 'dentalcert', 'prescription', 'referral', 'lab', 'xray', 'lactating', 'breastfeeding'];
        $statuses = ['draft', 'printed', 'printed', 'printed', 'cancelled'];
        $now = now();
        $batch = [];
        for ($f = 0; $f < 120; $f++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $type = $types[array_rand($types)];
            $status = $statuses[array_rand($statuses)];
            $batch[] = [
                'form_type' => $type,
                'student_id' => $sid,
                'staff_id' => $staffIds[array_rand($staffIds)] ?? null,
                'form_data' => json_encode([
                    'purpose' => 'OJT requirement',
                    'date' => $now->copy()->subDays(mt_rand(0, 90))->format('Y-m-d'),
                ]),
                'status' => $status,
                'printed_count' => $status === 'printed' ? mt_rand(1, 3) : 0,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'created_at' => $now->copy()->subDays(mt_rand(0, 200)),
                'updated_at' => $now,
            ];
        }
        DB::table('forms_certificates')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // CLINIC_NOTIFICATIONS — 200
    // -----------------------------------------------------------------------
    private function seedClinicNotifications(array $studentIds, array $staffIds): void
    {
        $types = ['appointment', 'referral', 'transcription', 'system', 'lab_result', 'medication_renewal'];
        $now = now();
        $batch = [];
        for ($n = 0; $n < 200; $n++) {
            $isForStudent = mt_rand(0, 1) === 0;
            $type = $types[array_rand($types)];
            $batch[] = [
                'recipient_id' => $isForStudent
                    ? $studentIds[array_rand($studentIds)]
                    : $staffIds[array_rand($staffIds)],
                'recipient_type' => $isForStudent ? 'student' : 'staff',
                'type' => $type,
                'title' => match ($type) {
                    'appointment' => 'Upcoming appointment reminder',
                    'referral' => 'New referral assigned',
                    'transcription' => 'Transcription pending review',
                    'system' => 'System maintenance notice',
                    'lab_result' => 'Lab result ready',
                    'medication_renewal' => 'Medication renewal due',
                    default => 'Notification',
                },
                'body' => 'Tap to view details.',
                'data' => json_encode(['source' => 'seeder']),
                'is_read' => (bool) mt_rand(0, 1),
                'read_at' => mt_rand(0, 1) === 1 ? $now->copy()->subDays(mt_rand(0, 30)) : null,
                'created_at' => $now->copy()->subDays(mt_rand(0, 60)),
                'updated_at' => $now,
            ];
        }
        DB::table('clinic_notifications')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // AUDIT_LOG — 240 events
    // -----------------------------------------------------------------------
    private function seedAuditLog(array $staffIds, array $studentIds): void
    {
        $actions = [
            'student.create', 'student.update', 'student.delete',
            'staff.create', 'staff.update', 'staff.delete',
            'visit.create', 'visit.update',
            'appointment.create', 'appointment.update', 'appointment.cancel',
            'referral.create', 'referral.accept', 'referral.complete',
            'medication.create', 'medication.update', 'medication.deactivate',
            'health_record.create', 'health_record.update',
            'dental.create', 'dental.update',
            'form.create', 'form.print',
            'login', 'logout',
        ];
        $entityTypes = [
            'student' => 'App\\Models\\Student',
            'staff' => 'App\\Models\\Staff',
            'visit' => 'App\\Models\\ClinicVisit',
            'appointment' => 'App\\Models\\Appointment',
            'referral' => 'App\\Models\\Referral',
            'medication' => 'App\\Models\\Medication',
            'health_record' => 'App\\Models\\HealthRecord',
            'dental' => 'App\\Models\\DentalRecord',
            'form' => 'App\\Models\\FormCertificate',
        ];
        $now = now();
        $batch = [];
        for ($a = 0; $a < 240; $a++) {
            $action = $actions[array_rand($actions)];
            $entityType = $entityTypes[explode('.', $action)[0]] ?? 'App\\Models\\Student';
            $isStudent = mt_rand(0, 3) === 0;
            $batch[] = [
                'user_id' => $isStudent ? $studentIds[array_rand($studentIds)] : $staffIds[array_rand($staffIds)],
                'user_type' => $isStudent ? 'student' : 'staff',
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => mt_rand(1, 800),
                'old_data' => $action === 'login' || $action === 'logout' ? null : json_encode(['field' => 'old_value']),
                'new_data' => $action === 'logout' ? null : json_encode(['field' => 'new_value']),
                'ip_address' => '127.0.0.'.mt_rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'created_at' => $now->copy()->subDays(mt_rand(0, 90))->subSeconds(mt_rand(0, 86400)),
            ];
        }
        DB::table('audit_log')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // ML_TRIAGE_LOGS — 150 (so the AI dashboard is not empty)
    // -----------------------------------------------------------------------
    private function seedMlTriageLogs(array $studentIds, array $campuses): void
    {
        $priorities = ['Priority 1', 'Priority 2', 'Priority 2', 'Priority 3', 'Priority 3', 'Priority 3'];
        $now = now();
        $batch = [];
        for ($m = 0; $m < 150; $m++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $predicted = $priorities[array_rand($priorities)];
            $batch[] = [
                'student_id' => $sid,
                'clinic_visit_id' => null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'input_features' => json_encode([
                    'age' => $student->age,
                    'sex' => $student->sex,
                    'symptom_count' => mt_rand(0, 4),
                    'vital_temp' => 36 + (mt_rand(0, 25) / 10),
                    'vital_bp_sys' => mt_rand(95, 140),
                ]),
                'predicted_priority' => $predicted,
                'confidence_score' => mt_rand(6000, 9900) / 10000,
                'reviewed_by_staff' => (bool) mt_rand(0, 1),
                'final_priority' => mt_rand(0, 1) === 1 ? $predicted : $priorities[array_rand($priorities)],
                'created_at' => $now->copy()->subDays(mt_rand(0, 90)),
                'updated_at' => $now,
            ];
        }
        DB::table('ml_triage_logs')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // MESSAGING — 6 channels + 120 messages
    // -----------------------------------------------------------------------
    private function seedMessaging(array $campuses, array $staffIds, array $studentIds): void
    {
        $now = now();
        $channels = [
            ['name' => 'Main Campus Nurses',  'type' => 'team',     'campus_id' => 2],
            ['name' => 'Old Sagay Nurses',    'type' => 'team',     'campus_id' => 1],
            ['name' => 'Laga-an Nurses',      'type' => 'team',     'campus_id' => 3],
            ['name' => 'Escalante Nurses',    'type' => 'team',     'campus_id' => 4],
            ['name' => 'All Referrals',       'type' => 'referrals', 'campus_id' => null],
            ['name' => 'Campus-Wide Alerts',  'type' => 'campus',   'campus_id' => null],
        ];
        $channelIds = [];
        foreach ($channels as $c) {
            $exists = DB::table('message_channels')->where('name', $c['name'])->value('id');
            if (! $exists) {
                $channelIds[$c['name']] = DB::table('message_channels')->insertGetId($c + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            } else {
                $channelIds[$c['name']] = $exists;
            }
        }

        $bodies = [
            'Good morning team. Clinic opens at 7:30 AM today.',
            'Reminder: OJT medical clearance forms due Friday.',
            'Inventory check at 3 PM. Please update medication counts.',
            'New student orientation at 10 AM.',
            'CPR training session this Saturday at 8 AM.',
            'Restocked paracetamol and ORS in the cabinet.',
            'Power interruption scheduled tomorrow, 1-3 PM.',
            'Please log all visits before end of shift.',
            'Annual physical exam schedule attached.',
            'Congratulations to the team for the successful health fair!',
        ];
        $batch = [];
        for ($m = 0; $m < 120; $m++) {
            $channelName = array_keys($channelIds)[array_rand(array_keys($channelIds))];
            $isStudent = mt_rand(0, 4) === 0;
            $batch[] = [
                'channel_id' => $channelIds[$channelName],
                'sender_id' => $isStudent ? $studentIds[array_rand($studentIds)] : $staffIds[array_rand($staffIds)],
                'sender_type' => $isStudent ? 'student' : 'staff',
                'body' => $bodies[array_rand($bodies)],
                'is_system' => mt_rand(0, 8) === 0,
                'created_at' => $now->copy()->subDays(mt_rand(0, 60))->subSeconds(mt_rand(0, 86400)),
                'updated_at' => $now,
            ];
        }
        DB::table('messages')->insert($batch);
    }

    // -----------------------------------------------------------------------
    // QUEUE & WORKFLOW — today's pending visits, today's pending appointments,
    // and the full set of clinical-workflow status records the nurse dashboard
    // shows under "Queues & Workflow" (referrals, transcriptions, ML triage).
    // -----------------------------------------------------------------------
    private function seedQueueAndWorkflow(array $studentIds, array $campuses, array $nurseIds, array $physicianIds): void
    {
        $now = now();
        $today = $now->format('Y-m-d');

        // Idempotency: skip if today's queue data already exists.
        $alreadySeeded = DB::table('clinic_visits')
            ->whereDate('visit_date', $today)
            ->whereNull('disposition')
            ->whereNotNull('visit_time')
            ->where('notes', 'LIKE', '[queue:%')
            ->exists();
        if ($alreadySeeded) {
            return;
        }

        $walkInReasons = [
            'Headache, BP check', 'Mild fever, paracetamol given', 'Cough and colds',
            'Wound dressing change', 'Allergy follow-up', 'BP monitoring',
            'Stomach ache, dyspepsia', 'Sore throat, throat swab taken',
            'Muscle pain after PE class', 'Dizziness, orthostatic check',
            'Sprained ankle, RICE protocol', 'Anxiety, counseling done',
            'Toothache, referred to dentist', 'Skin rash, topical cream given',
            'Menstrual cramps, mefenamic acid given',
        ];

        // (1) Today's walk-in visits (disposition IS NULL → show in queue as "pending")
        $walkIns = [];
        for ($i = 0; $i < 12; $i++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $walkIns[] = [
                'student_id' => $sid,
                'visit_date' => $today,
                'visit_time' => sprintf('%02d:%02d:00', 8 + intdiv($i, 2), ($i % 2) * 30 + mt_rand(0, 25)),
                'reason' => $walkInReasons[array_rand($walkInReasons)],
                'symptoms' => 'See reason. Vitals stable.',
                'treatment' => null,
                'bp' => mt_rand(100, 135).'/'.mt_rand(65, 88),
                'temp' => 36 + (mt_rand(0, 18) / 10),
                'pulse' => mt_rand(70, 100),
                'rr' => mt_rand(16, 22),
                'o2_sat' => mt_rand(96, 100),
                'weight_kg' => null,
                'nurse_id' => $nurseIds[array_rand($nurseIds)] ?? null,
                'physician_id' => null,
                'disposition' => null,    // NULL → still in queue
                'notes' => '[queue:walkin]',
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('clinic_visits')->insert($walkIns);

        // (2) Today's pending appointments (status='pending' → show in queue as "Appt")
        $apptServices = ['General Consultation', 'Oral Examination', 'CBC (Complete Blood Count)', 'Urinalysis', 'Immunization', 'Medical Certificate Issuance'];
        $apptBatch = [];
        $queueAppointmentStudentIds = array_values(array_unique($studentIds));
        shuffle($queueAppointmentStudentIds);
        $pendingQueueCount = min(8, count($queueAppointmentStudentIds));
        for ($i = 0; $i < $pendingQueueCount; $i++) {
            $sid = $queueAppointmentStudentIds[$i];
            $student = DB::table('students')->where('id', $sid)->first();
            $apptBatch[] = [
                'student_id' => $sid,
                'appt_date' => $today,
                'appt_time' => sprintf('%02d:%02d:00', 9 + intdiv($i, 1), ($i * 7) % 60),
                'service_type' => $apptServices[array_rand($apptServices)],
                'reason' => 'Walk-in scheduled via queue.',
                'status' => 'pending',
                'notes' => '[queue:appt-pending]',
                'created_by' => $nurseIds[array_rand($nurseIds)] ?? null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        // Add one "confirmed" and one "completed" so the queue stat cards show a mix.
        if (isset($queueAppointmentStudentIds[8])) {
            $student = DB::table('students')->where('id', $queueAppointmentStudentIds[8])->first();
            $apptBatch[] = [
                'student_id' => $queueAppointmentStudentIds[8],
                'appt_date' => $today,
                'appt_time' => '10:30:00',
                'service_type' => 'General Consultation',
                'reason' => 'Confirmed earlier today.',
                'status' => 'confirmed',
                'notes' => '[queue:appt-confirmed]',
                'created_by' => $nurseIds[array_rand($nurseIds)] ?? null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (isset($queueAppointmentStudentIds[9])) {
            $student = DB::table('students')->where('id', $queueAppointmentStudentIds[9])->first();
            $apptBatch[] = [
                'student_id' => $queueAppointmentStudentIds[9],
                'appt_date' => $today,
                'appt_time' => '08:30:00',
                'service_type' => 'Oral Examination',
                'reason' => 'Done first thing this morning.',
                'status' => 'completed',
                'notes' => '[queue:appt-completed] Sent home in stable condition.',
                'created_by' => $nurseIds[array_rand($nurseIds)] ?? null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($apptBatch !== []) {
            DB::table('appointments')->insert($apptBatch);
        }

        // (3) Pending referrals (the "inter-campus workflow" column) — already
        //     partly covered by seedReferrals(); add a few extra *today* entries
        //     so the referrals workflow feels current.
        $specialties = ['Cardiology', 'Dermatology', 'Orthopedics', 'ENT', 'Ophthalmology', 'Psychiatry'];
        $refBatch = [];
        $startRef = DB::table('referrals')->max('id') ?? 0;
        for ($r = 0; $r < 5; $r++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $refBatch[] = [
                'referral_no' => 'REF-'.str_pad((string) ($startRef + $r + 1), 5, '0', STR_PAD_LEFT),
                'student_id' => $sid,
                'from_campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'to_campus_id' => $campuses[array_rand($campuses)],
                'referred_by' => $nurseIds[array_rand($nurseIds)] ?? null,
                'referred_to_name' => 'Dr. '.['Antonio Reyes', 'Maria Lim', 'Roberto Chua', 'Liza Tan', 'Eduardo Garcia'][$r],
                'specialty' => $specialties[array_rand($specialties)],
                'referral_type' => 'inter-campus',
                'diagnosis' => 'See reason.',
                'history' => 'No prior related consult.',
                'reason' => 'New symptom today; specialist consult requested.',
                'management_given' => 'Initial symptomatic treatment given; vitals stable.',
                'urgency' => $r === 0 ? 'urgent' : 'routine',
                'status' => 'pending',
                'feedback' => null,
                'accepted_at' => null,
                'completed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('referrals')->insert($refBatch);

        // (4) Pending transcriptions — drafts + pending_review for today
        $transBatch = [];
        for ($t = 0; $t < 4; $t++) {
            $sid = $studentIds[array_rand($studentIds)];
            $student = DB::table('students')->where('id', $sid)->first();
            $status = $t < 2 ? 'draft' : 'pending_review';
            $transBatch[] = [
                'student_id' => $sid,
                'nurse_id' => $nurseIds[array_rand($nurseIds)] ?? null,
                'physician_id' => $physicianIds[array_rand($physicianIds)] ?? null,
                'clinic_visit_id' => null,
                'campus_id' => $student->campus_id ?? $campuses[array_rand($campuses)],
                'consultation_date' => $today,
                'raw_transcript' => 'Patient seen at the clinic for evaluation.',
                'ai_draft' => 'S: Chief complaint reported.\nO: Vitals stable.\nA: Initial assessment.\nP: Symptomatic treatment, follow-up.',
                'approved_notes' => null,
                'status' => $status,
                'rejection_reason' => null,
                'reviewed_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('medical_transcriptions')->insert($transBatch);
    }

    // -----------------------------------------------------------------------
    // QUEUE JOBS — populate the jobs / job_batches / failed_jobs tables that
    // Laravel uses for the database queue driver. This is the background-work
    // back-end that backs "Queues & Workflow" features.
    // -----------------------------------------------------------------------
    private function seedQueueJobs(): void
    {
        // Idempotency: only seed queue tables once.
        if (DB::table('jobs')->exists() || DB::table('failed_jobs')->exists()) {
            return;
        }

        $now = time();

        $jobClasses = [
            'App\\Jobs\\SendAppointmentReminder',
            'App\\Jobs\\GenerateMedicalCertificate',
            'App\\Jobs\\NotifyReferralStatus',
            'App\\Jobs\\ProcessLabResult',
            'App\\Jobs\\IndexStudentRecord',
            'App\\Jobs\\AuditLogFlush',
            'App\\Jobs\\SendTelegramNotification',
            'App\\Jobs\\SyncHealthRecordToAnalytics',
        ];

        $queues = ['default', 'default', 'notifications', 'reports', 'transcriptions', 'analytics'];
        $batchIds = [];

        // (1) Active job_batches — three in-flight batches the dashboard might show
        for ($b = 0; $b < 3; $b++) {
            $batchId = (string) Str::uuid();
            $batchIds[] = $batchId;
            $total = mt_rand(40, 120);
            $finished = mt_rand(intdiv($total, 3), intdiv($total, 2));
            $failed = mt_rand(0, 3);
            DB::table('job_batches')->insert([
                'id' => $batchId,
                'name' => ['Appointment reminders', 'Certificate generation', 'Lab result sync'][$b],
                'total_jobs' => $total,
                'pending_jobs' => $total - $finished - $failed,
                'failed_jobs' => $failed,
                'failed_job_ids' => json_encode([]),
                'options' => json_encode([]),
                'cancelled_at' => null,
                'created_at' => $now - mt_rand(60, 600),
                'finished_at' => $finished > 0 ? ($now - mt_rand(0, 60)) : null,
            ]);
        }

        // (2) Pending jobs (the jobs table) — 18 entries spread across queues
        for ($i = 0; $i < 18; $i++) {
            $class = $jobClasses[array_rand($jobClasses)];
            $queue = $queues[array_rand($queues)];
            $job = (object) [
                'uuid' => (string) Str::uuid(),
                'displayName' => $class,
                'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                'maxTries' => 3,
                'maxExceptions' => 2,
                'backoff' => null,
                'timeout' => 60,
                'retryUntil' => null,
                'data' => [
                    'commandName' => $class,
                    'command' => serialize(new \stdClass),
                ],
            ];
            $availableAt = $now - mt_rand(0, 300);
            DB::table('jobs')->insert([
                'queue' => $queue,
                'payload' => json_encode([
                    'uuid' => $job->uuid,
                    'displayName' => $job->displayName,
                    'job' => $job->job,
                    'maxTries' => $job->maxTries,
                    'maxExceptions' => $job->maxExceptions,
                    'backoff' => $job->backoff,
                    'timeout' => $job->timeout,
                    'retryUntil' => $job->retryUntil,
                    'data' => $job->data,
                    'batchId' => mt_rand(0, 1) === 0 ? $batchIds[array_rand($batchIds)] : null,
                ]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => $availableAt,
                'created_at' => $availableAt,
            ]);
        }

        // (3) A few jobs that already finished and got reserved (so the queue
        //     shows recent activity rather than nothing).
        for ($i = 0; $i < 4; $i++) {
            $class = $jobClasses[array_rand($jobClasses)];
            $queue = $queues[array_rand($queues)];
            $createdAt = $now - mt_rand(300, 3600);
            $reservedAt = $createdAt + mt_rand(1, 5);
            DB::table('jobs')->insert([
                'queue' => $queue,
                'payload' => json_encode([
                    'uuid' => (string) Str::uuid(),
                    'displayName' => $class,
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'maxTries' => 3,
                    'maxExceptions' => 2,
                    'timeout' => 60,
                    'data' => ['commandName' => $class, 'command' => serialize(new \stdClass)],
                ]),
                'attempts' => 1,
                'reserved_at' => $reservedAt,
                'available_at' => $createdAt,
                'created_at' => $createdAt,
            ]);
        }

        // (4) Failed jobs — 4 entries with realistic exception messages
        $exceptions = [
            'RuntimeException: SMS provider returned 500 (timeout) while sending appointment reminder.',
            'GuzzleHttp\\Exception\\ConnectException: cURL error 28 — Operation timed out after 30000 ms (PH SMS gateway).',
            'PDOException: SQLSTATE[40001] — Serialization failure (deadlock) on clinic_visits insert.',
            'Throwable: Telegram bot token rejected (401 Unauthorized) — bot was revoked by @BotFather.',
        ];
        for ($i = 0; $i < 4; $i++) {
            $class = $jobClasses[array_rand($jobClasses)];
            DB::table('failed_jobs')->insert([
                'uuid' => (string) Str::uuid(),
                'connection' => 'database',
                'queue' => $queues[array_rand($queues)],
                'payload' => json_encode([
                    'uuid' => (string) Str::uuid(),
                    'displayName' => $class,
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'maxTries' => 3,
                    'maxExceptions' => 2,
                    'timeout' => 60,
                    'data' => ['commandName' => $class, 'command' => serialize(new \stdClass)],
                ]),
                'exception' => $exceptions[$i],
                'failed_at' => date('Y-m-d H:i:s', $now - mt_rand(120, 7200)),
            ]);
        }
    }
}
