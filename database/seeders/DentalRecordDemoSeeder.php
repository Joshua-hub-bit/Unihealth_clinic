<?php

namespace Database\Seeders;

use App\Models\DentalRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DentalRecordDemoSeeder extends Seeder
{
    private const RECORDS_PER_CAMPUS = 25;

    private const DEMO_NOTE_PREFIX = 'SYNTHETIC DEMO DATA - NOT FOR CLINICAL USE. Seed: dental-demo-campus-';

    public function run(): void
    {
        $campuses = DB::table('campuses')->orderBy('id')->get(['id', 'name']);
        if ($campuses->isEmpty()) {
            throw new RuntimeException('Cannot seed dental demos because no campuses exist.');
        }

        $studentsByCampus = DB::table('students')
            ->where('is_active', true)
            ->where('role', 'student')
            ->whereIn('campus_id', $campuses->pluck('id'))
            ->orderBy('id')
            ->get(['id', 'campus_id'])
            ->groupBy('campus_id');

        foreach ($campuses as $campus) {
            if (($studentsByCampus[$campus->id] ?? collect())->isEmpty()) {
                throw new RuntimeException("Cannot seed dental demos because {$campus->name} has no active students.");
            }
        }

        $dentistProviderId = DB::table('clinic_providers')
            ->where('provider_type', 'dentist')
            ->where('is_active', true)
            ->whereNull('campus_id')
            ->value('id');
        $sampleTeeth = [
            'permanent-upper-16-1',
            'permanent-upper-26-2',
            'permanent-lower-36-condition-1',
            'permanent-lower-46-condition-2',
        ];

        DB::transaction(function () use ($campuses, $studentsByCampus, $dentistProviderId, $sampleTeeth): void {
            foreach ($campuses as $campus) {
                $campusStudents = $studentsByCampus[$campus->id]->values();
                $existingDemoCount = DentalRecord::query()
                    ->where('campus_id', $campus->id)
                    ->where('notes', 'like', self::DEMO_NOTE_PREFIX.$campus->id.'-%')
                    ->count();
                $existingDemos = DentalRecord::query()
                    ->where('campus_id', $campus->id)
                    ->where('notes', 'like', self::DEMO_NOTE_PREFIX.$campus->id.'-%')
                    ->orderBy('id')
                    ->get();
                foreach ($existingDemos as $index => $record) {
                    $chart = $record->tooth_chart ?? [];
                    $chart['tooth_markings'] = [$sampleTeeth[$index % count($sampleTeeth)]];
                    $chart['oral_condition_markings'] = ['Plaque:1'];
                    $record->tooth_chart = $chart;
                    $record->save();
                }

                for ($index = $existingDemoCount; $index < self::RECORDS_PER_CAMPUS; $index++) {
                    $student = $campusStudents[$index % $campusStudents->count()];
                    $sequence = $index + 1;
                    $date = now()->subDays(($campus->id * 11 + $sequence * 7) % 365)->toDateString();

                    DentalRecord::query()->create([
                        'student_id' => $student->id,
                        'dentist_provider_id' => $dentistProviderId,
                        'campus_id' => $campus->id,
                        'record_date' => $date,
                        'chief_complaint' => 'Synthetic sample for testing the Dental Record viewer.',
                        'dental_history' => 'Synthetic demo record; not a real patient history.',
                        'oral_exam_findings' => 'SYNTHETIC DEMO DATA ONLY. Sample findings for testing form display.',
                        'tooth_chart' => [
                            'tooth_markings' => [$sampleTeeth[($sequence - 1) % count($sampleTeeth)]],
                            'oral_condition_markings' => ['Plaque:1'],
                        ],
                        'treatment_done' => 'Synthetic sample treatment entry for display testing.',
                        'treatment_plan' => 'Synthetic sample plan; not for clinical use.',
                        'next_appointment' => null,
                        'notes' => self::DEMO_NOTE_PREFIX.$campus->id.'-'.$sequence.'.',
                    ]);
                }
            }
        });

        $this->command?->info('DentalRecordDemoSeeder: ensured 25 clearly labeled demo records per campus.');
    }
}
