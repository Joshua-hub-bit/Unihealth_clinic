<?php

namespace App\Services;

use App\Models\Student;
use Illuminate\Support\Carbon;

/**
 * Stateless helpers used across multiple API controllers.
 *
 * Replaces the legacy App\Core\Helpers static class.
 * Inject via constructor DI; no static calls needed.
 */
class ClinicHelperService
{
    // -----------------------------------------------------------------------
    // Input sanitisation
    // -----------------------------------------------------------------------

    /**
     * Strip HTML tags, trim whitespace, and truncate to $maxLen characters.
     */
    public function sanitize(mixed $value, int $maxLen = 255): string
    {
        if ($value === null) {
            return '';
        }

        $cleaned = preg_replace('/<[^>]+>/', '', trim((string) $value)) ?? '';

        return substr($cleaned, 0, $maxLen);
    }

    /**
     * Return a valid Y-m-d string or null.
     */
    public function sqlDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', trim((string) $value));

            return $date && $date->format('Y-m-d') === trim((string) $value)
                ? $date->format('Y-m-d')
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Return a valid H:i:s string or null.
     */
    public function sqlTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = trim((string) $value);

        foreach (['H:i:s', 'H:i'] as $format) {
            try {
                $time = Carbon::createFromFormat($format, $raw);
                if ($time) {
                    return $time->format('H:i:s');
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Return a float or null for numeric inputs.
     */
    public function sqlNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    // -----------------------------------------------------------------------
    // Student lookup
    // -----------------------------------------------------------------------

    /**
     * Resolve a student_no string to the students.id (primary key).
     * Returns null when the student does not exist or is inactive.
     */
    public function resolveStudentId(string $studentNo): ?int
    {
        $student = Student::where('student_id', strtoupper(trim($studentNo)))
            ->where('is_active', true)
            ->value('id');

        return $student ? (int) $student : null;
    }

    // -----------------------------------------------------------------------
    // Bool field normalisation (legacy 0/1 storage for immunisations, etc.)
    // -----------------------------------------------------------------------

    /**
     * Coerce a value to a boolean appropriate for storage.
     * Treats '0', 'false', 'False', '' as false; everything else as true.
     */
    public function normaliseBool(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }

        return ! in_array((string) $value, ['0', 'false', 'False', ''], true);
    }
}
