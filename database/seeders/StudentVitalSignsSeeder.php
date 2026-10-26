<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use LogicException;

class StudentVitalSignsSeeder extends Seeder
{
    private const SAMPLE_NOTES = 'SYNTHETIC SAMPLE DATA: generated for dashboard testing; not measured, reviewed, or for clinical use.';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new LogicException('Synthetic vital-signs sample data cannot be seeded in production.');
        }

        DB::transaction(function (): void {
            $students = DB::table('students')
                ->where('role', 'student')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'campus_id']);

            if ($students->isEmpty()) {
                $this->command?->warn('No students found; no sample visits were created.');

                return;
            }

            DB::table('clinic_visits')
                ->where('notes', self::SAMPLE_NOTES)
                ->delete();

            $usedSlots = [];
            $studentsWithCompleteVitals = [];
            foreach (DB::table('clinic_visits')->get([
                'student_id',
                'visit_date',
                'visit_time',
                'bp',
                'temp',
                'pulse',
                'rr',
                'o2_sat',
            ]) as $visit) {
                $date = (string) $visit->visit_date;
                $usedSlots[$date.'|'.$visit->visit_time] = true;

                if (
                    $visit->bp !== null
                    && $visit->temp !== null
                    && $visit->pulse !== null
                    && $visit->rr !== null
                    && $visit->o2_sat !== null
                ) {
                    $studentsWithCompleteVitals[$visit->student_id] = true;
                }
            }

            $visitBatch = [];
            $studentsByCampus = $students
                ->filter(fn (object $student): bool => $student->campus_id !== null)
                ->groupBy('campus_id');

            foreach ($students->whereNull('campus_id')->values() as $index => $student) {
                if (isset($studentsWithCompleteVitals[$student->id])) {
                    continue;
                }

                $daysBack = $index + 1;
                do {
                    $visitDate = today()->subDays($daysBack)->toDateString();
                    $visitTime = $this->nextAvailableTime(
                        $visitDate,
                        (8 * 60) + ($index % 540),
                        $usedSlots,
                    );
                    if ($visitTime === null) {
                        $daysBack++;
                    }
                } while ($visitTime === null);

                $usedSlots[$visitDate.'|'.$visitTime] = true;
                $visitBatch[] = $this->demoVisit($student->id, null, $visitDate, $visitTime);
            }

            $addedVisitCount = count($visitBatch);
            $this->insertVisits($visitBatch);
            $visitBatch = [];

            $studentsByCampus = $studentsByCampus->sortKeys();
            $campusIds = $studentsByCampus->keys()->all();
            $month = today()->startOfYear();
            $lastMonth = today()->startOfMonth();

            while ($month->lte($lastMonth)) {
                $monthEnd = $month->copy()->endOfMonth()->min(today());
                $currentCounts = DB::table('clinic_visits')
                    ->select('campus_id')
                    ->selectRaw('COUNT(*) as visit_count')
                    ->whereBetween('visit_date', [$month->toDateString(), $monthEnd->toDateString()])
                    ->whereIn('campus_id', $campusIds)
                    ->groupBy('campus_id')
                    ->pluck('visit_count', 'campus_id')
                    ->map(fn ($count): int => (int) $count)
                    ->all();

                $targetCounts = [];
                foreach ($campusIds as $campusId) {
                    $targetCounts[$campusId] = $studentsByCampus->get($campusId)->count();
                    if (($currentCounts[$campusId] ?? 0) > $targetCounts[$campusId]) {
                        throw new LogicException(
                            "Existing visits exceed the student-count target for campus {$campusId} in {$month->format('Y-m')}.",
                        );
                    }
                }

                $visitDates = [];
                for ($date = $month->copy(); $date->lte($monthEnd); $date->addDay()) {
                    $visitDates[] = $date->toDateString();
                }

                $nextMinuteByDate = [];
                foreach ($campusIds as $campusId) {
                    $currentCampusVisitCount = $currentCounts[$campusId] ?? 0;
                    if ($currentCampusVisitCount >= $targetCounts[$campusId]) {
                        continue;
                    }

                    $campusStudents = $studentsByCampus->get($campusId)->values();
                    $visitedStudentIds = DB::table('clinic_visits')
                        ->where('campus_id', $campusId)
                        ->whereBetween('visit_date', [$month->toDateString(), $monthEnd->toDateString()])
                        ->pluck('student_id')
                        ->all();
                    $visitedStudentIds = array_fill_keys($visitedStudentIds, true);
                    $studentsToVisit = $campusStudents
                        ->reject(fn (object $student): bool => isset($visitedStudentIds[$student->id]))
                        ->values();
                    $visitsNeeded = $targetCounts[$campusId] - $currentCampusVisitCount;

                    if ($studentsToVisit->count() < $visitsNeeded) {
                        throw new LogicException(
                            "Not enough unvisited students to meet the campus {$campusId} target for {$month->format('Y-m')}.",
                        );
                    }

                    foreach ($studentsToVisit->take($visitsNeeded)->values() as $index => $student) {
                        $visitDate = $visitDates[$index % count($visitDates)];
                        $minuteOfDay = $nextMinuteByDate[$visitDate] ?? (8 * 60);
                        $visitTime = $this->nextAvailableTime($visitDate, $minuteOfDay, $usedSlots);
                        if ($visitTime === null) {
                            throw new LogicException(
                                "Unable to assign a unique visit date and time for campus {$campusId}.",
                            );
                        }

                        $usedSlots[$visitDate.'|'.$visitTime] = true;
                        $nextMinuteByDate[$visitDate] = $this->timeToMinutes($visitTime) + 1;
                        $visitBatch[] = $this->demoVisit($student->id, $campusId, $visitDate, $visitTime);
                    }

                    $addedVisitCount += $visitsNeeded;
                }

                $this->insertVisits($visitBatch);
                $visitBatch = [];
                $month->addMonth();
            }

            $this->command?->info(
                "Created {$addedVisitCount} clearly labeled sample visits, targeting each campus's student count per month.",
            );
        });
    }

    private function demoVisit(int $studentId, ?int $campusId, string $visitDate, string $visitTime): array
    {
        $visitAt = $visitDate.' '.$visitTime;

        return [
            'student_id' => $studentId,
            'visit_date' => $visitDate,
            'visit_time' => $visitTime,
            'reason' => 'SYNTHETIC SAMPLE VITALS - NOT A REAL VISIT',
            'symptoms' => 'Sample placeholder; symptoms were not assessed.',
            'treatment' => 'Sample placeholder; no treatment information recorded.',
            'bp' => '110/70',
            'temp' => 36.5,
            'pulse' => 72,
            'rr' => 16,
            'o2_sat' => 98,
            'weight_kg' => null,
            'nurse_id' => null,
            'physician_id' => null,
            'disposition' => 'Sample data only - not a clinical encounter',
            'notes' => self::SAMPLE_NOTES,
            'campus_id' => $campusId,
            'created_at' => $visitAt,
            'updated_at' => $visitAt,
        ];
    }

    private function insertVisits(array $visits): void
    {
        foreach (array_chunk($visits, 200) as $chunk) {
            DB::table('clinic_visits')->insert($chunk);
        }
    }

    private function nextAvailableTime(string $visitDate, int $minuteOfDay, array $usedSlots): ?string
    {
        for ($minute = max(8 * 60, $minuteOfDay); $minute < 18 * 60; $minute++) {
            $visitTime = sprintf('%02d:%02d:00', intdiv($minute, 60), $minute % 60);
            if (! isset($usedSlots[$visitDate.'|'.$visitTime])) {
                return $visitTime;
            }
        }

        return null;
    }

    private function timeToMinutes(string $visitTime): int
    {
        [$hour, $minute] = array_map('intval', explode(':', $visitTime));

        return ($hour * 60) + $minute;
    }
}
