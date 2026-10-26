<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $students = DB::table('students')->get(['id', 'student_id', 'last_name', 'email']);
        $demoStudents = $students->filter(fn (object $student): bool => $this->isDemoEmail($student->email))->values();

        if ($demoStudents->isEmpty()) {
            return;
        }

        $targetIds = array_fill_keys($demoStudents->pluck('id')->all(), true);
        $surnameCounts = $students->countBy(
            fn (object $student): string => Str::slug($student->last_name, '') ?: 'student',
        );
        $reservedEmails = $students
            ->reject(fn (object $student): bool => isset($targetIds[$student->id]))
            ->mapWithKeys(fn (object $student): array => [strtolower($student->email) => true])
            ->all();
        $updatedEmails = [];

        foreach ($demoStudents as $student) {
            $lastName = Str::slug($student->last_name, '') ?: 'student';
            $studentId = Str::slug($student->student_id, '') ?: 'student'.$student->id;
            $emailLocalPart = ($surnameCounts->get($lastName, 0) > 1)
                ? $lastName.'+'.$studentId
                : $lastName;
            $email = $emailLocalPart.'@gmail.com';

            if (isset($reservedEmails[$email])) {
                $email = $lastName.'+'.$studentId.'@gmail.com';
            }

            if (isset($reservedEmails[$email])) {
                $email = $lastName.'+'.$studentId.'-'.$student->id.'@gmail.com';
            }

            if (isset($reservedEmails[$email])) {
                throw new RuntimeException('Could not generate a unique demo patient email address.');
            }

            $reservedEmails[$email] = true;
            $updatedEmails[$student->id] = $email;
        }

        DB::transaction(function () use ($demoStudents, $updatedEmails): void {
            foreach ($demoStudents as $student) {
                DB::table('students')
                    ->where('id', $student->id)
                    ->update(['email' => 'migration-student-'.$student->id.'@invalid.invalid']);
            }

            foreach ($updatedEmails as $id => $email) {
                DB::table('students')->where('id', $id)->update(['email' => $email]);
            }
        });
    }

    public function down(): void
    {
        // The original demo email values are not retained, so this data backfill cannot be reversed safely.
    }

    private function isDemoEmail(string $email): bool
    {
        return preg_match('/(?:^|[+._@-])(?:demo|test|example)(?:[+._@-]|$)/i', $email) === 1
            || preg_match('/^student\d*@uni\.edu$/i', $email) === 1;
    }
};
