<?php

namespace App\Support;

class CampusCourseCatalog
{
    private const COURSES = [
        'OLD-SAGAY' => [
            ['college' => 'CFNS', 'acronym' => 'BSF', 'name' => 'Bachelor of Science in Fisheries (BSF)'],
            ['college' => 'CFNS', 'acronym' => 'BSBIO', 'name' => 'Bachelor of Science in Biology (BSBIO)'],
            ['college' => 'CCIS', 'acronym' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology (BSIT)'],
            ['college' => 'CCIS', 'acronym' => 'BSIS', 'name' => 'Bachelor of Science in Information Systems (BSIS)'],
            ['college' => 'CCIS', 'acronym' => 'BSEMC-A', 'name' => 'Bachelor of Science in Entertainment and Multimedia Computing major in Animation Technology (BSEMC-A)'],
            ['college' => 'CCIS', 'acronym' => 'BLIS', 'name' => 'Bachelor of Library and Information Science (BLIS)'],
            ['college' => 'CALSS', 'acronym' => 'ABELS', 'name' => 'Bachelor of Arts in English Language Studies (ABELS)'],
            ['college' => 'CBM', 'acronym' => 'BPA', 'name' => 'Bachelor of Public Administration (BPA)'],
        ],
        'SAGAY-MAIN' => [
            ['college' => 'CBM', 'acronym' => 'BSHM', 'name' => 'Bachelor of Science in Hospitality Management (BSHM)'],
            ['college' => 'CBM', 'acronym' => 'BSCM', 'name' => 'Bachelor of Science in Cooperative Management (BSCM)'],
            ['college' => 'CBM', 'acronym' => 'BSBA-FM', 'name' => 'Bachelor of Science in Business Administration major in Financial Management (BSBA-FM)'],
            ['college' => 'CBM', 'acronym' => 'BSBA-MM', 'name' => 'Bachelor of Science in Business Administration major in Marketing Management (BSBA-MM)'],
            ['college' => 'CBM', 'acronym' => 'BSTM', 'name' => 'Bachelor of Science in Tourism Management (BSTM)'],
            ['college' => 'CBM', 'acronym' => 'BSAIS', 'name' => 'Bachelor of Science in Accounting Information Systems (BSAIS)'],
            ['college' => 'CHAS', 'acronym' => 'BSN', 'name' => 'Bachelor of Science in Nursing (BSN)'],
            ['college' => 'COED', 'acronym' => 'BPEd', 'name' => 'Bachelor in Physical Education (BPEd)'],
            ['college' => 'COED', 'acronym' => 'BSED', 'name' => 'Bachelor of Secondary Education (BSED)'],
            ['college' => 'COED', 'acronym' => 'BSEd-English', 'name' => 'Bachelor of Secondary Education major in English (BSEd-English)'],
            ['college' => 'COED', 'acronym' => 'BSEd-Mathematics', 'name' => 'Bachelor of Secondary Education major in Mathematics (BSEd-Mathematics)'],
            ['college' => 'COED', 'acronym' => 'BSEd-Science', 'name' => 'Bachelor of Secondary Education major in Science (BSEd-Science)'],
            ['college' => 'COED', 'acronym' => 'BTLEd', 'name' => 'Bachelor of Technology and Livelihood Education (BTLEd)'],
            ['college' => 'COED', 'acronym' => 'BTLEd-HE', 'name' => 'Bachelor of Technology and Livelihood Education major in Home Economics (BTLEd-HE)'],
            ['college' => 'COED', 'acronym' => 'BTLEd-IA', 'name' => 'Bachelor of Technology and Livelihood Education major in Industrial Arts (BTLEd-IA)'],
            ['college' => 'COED', 'acronym' => 'BTLEd-ICT', 'name' => 'Bachelor of Technology and Livelihood Education major in Information and Communications Technology (BTLEd-ICT)'],
            ['college' => 'COED', 'acronym' => 'BTLEd-AFA', 'name' => 'Bachelor of Technology and Livelihood Education major in Agri-Fishery Arts (BTLEd-AFA)'],
        ],
        'ESCALANTE' => [
            ['college' => 'CCJE', 'acronym' => 'BSCrim', 'name' => 'Bachelor of Science in Criminology (BSCrim)'],
            ['college' => 'CAAS', 'acronym' => 'BSA', 'name' => 'Bachelor of Science in Agriculture (BSA)'],
            ['college' => 'CAAS', 'acronym' => 'BSAB', 'name' => 'Bachelor of Science in Agribusiness (BSAB)'],
            ['college' => 'CAAS', 'acronym' => 'DVM', 'name' => 'Doctor of Veterinary Medicine (DVM)'],
        ],
        'LAGA-AN' => [
            ['college' => 'CAAS', 'acronym' => 'BSA', 'name' => 'Bachelor of Science in Agriculture (BSA)'],
            ['college' => 'CAAS', 'acronym' => 'BSAB', 'name' => 'Bachelor of Science in Agribusiness (BSAB)'],
        ],
    ];

    public static function courses(string $campusCode): array
    {
        return self::COURSES[$campusCode] ?? [];
    }

    public static function courseNames(string $campusCode): array
    {
        return array_column(self::courses($campusCode), 'name');
    }

    public static function offersCourse(string $campusCode, string $courseName): bool
    {
        return in_array($courseName, self::courseNames($campusCode), true);
    }
}
