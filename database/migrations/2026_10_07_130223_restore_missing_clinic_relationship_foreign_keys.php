<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RELATIONSHIPS = [
        ['students', 'campus_id', 'campuses', 'set null'],
        ['staff', 'campus_id', 'campuses', 'set null'],
        ['health_records', 'student_id', 'students', 'cascade'],
        ['health_records', 'campus_id', 'campuses', 'set null'],
        ['appointments', 'student_id', 'students', 'cascade'],
        ['appointments', 'created_by', 'staff', 'set null'],
        ['appointments', 'campus_id', 'campuses', 'set null'],
        ['clinic_visits', 'student_id', 'students', 'cascade'],
        ['clinic_visits', 'nurse_id', 'staff', 'set null'],
        ['clinic_visits', 'physician_id', 'staff', 'set null'],
        ['clinic_visits', 'campus_id', 'campuses', 'set null'],
        ['medications', 'student_id', 'students', 'cascade'],
        ['medications', 'prescribed_by', 'staff', 'set null'],
        ['medications', 'campus_id', 'campuses', 'set null'],
        ['forms_certificates', 'student_id', 'students', 'cascade'],
        ['forms_certificates', 'staff_id', 'staff', 'set null'],
        ['forms_certificates', 'campus_id', 'campuses', 'set null'],
        ['referrals', 'student_id', 'students', 'cascade'],
        ['referrals', 'from_campus_id', 'campuses', 'set null'],
        ['referrals', 'to_campus_id', 'campuses', 'set null'],
        ['referrals', 'referred_by', 'staff', 'set null'],
        ['medical_transcriptions', 'student_id', 'students', 'cascade'],
        ['medical_transcriptions', 'nurse_id', 'staff', 'set null'],
        ['medical_transcriptions', 'physician_id', 'staff', 'set null'],
        ['medical_transcriptions', 'clinic_visit_id', 'clinic_visits', 'set null'],
        ['medical_transcriptions', 'campus_id', 'campuses', 'set null'],
        ['messages', 'channel_id', 'message_channels', 'cascade'],
        ['message_channels', 'campus_id', 'campuses', 'set null'],
        ['dental_records', 'student_id', 'students', 'cascade'],
        ['dental_records', 'dentist_id', 'staff', 'set null'],
        ['dental_records', 'campus_id', 'campuses', 'set null'],
        ['ml_triage_logs', 'student_id', 'students', 'set null'],
        ['ml_triage_logs', 'clinic_visit_id', 'clinic_visits', 'set null'],
        ['ml_triage_logs', 'campus_id', 'campuses', 'set null'],
    ];

    public function up(): void
    {
        foreach (self::RELATIONSHIPS as [$table, $column, $parentTable, $onDelete]) {
            if (! Schema::hasTable($table)
                || ! Schema::hasTable($parentTable)
                || ! Schema::hasColumn($table, $column)
                || ! Schema::hasColumn($parentTable, 'id')
            ) {
                throw new RuntimeException("Cannot restore {$table}.{$column} relationship: required table or column is missing.");
            }

            $orphanCount = DB::table($table)
                ->whereNotNull($column)
                ->whereNotExists(function ($query) use ($table, $column, $parentTable): void {
                    $query->selectRaw('1')
                        ->from($parentTable)
                        ->whereColumn("{$parentTable}.id", "{$table}.{$column}");
                })
                ->count();

            if ($orphanCount > 0) {
                throw new RuntimeException("Cannot restore {$table}.{$column} foreign key: {$orphanCount} orphaned reference(s) remain. Resolve them before retrying.");
            }
        }

        foreach (self::RELATIONSHIPS as [$table, $column, $parentTable, $onDelete]) {
            $existingForeignKey = collect(Schema::getForeignKeys($table))->first(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                    && $foreignKey['foreign_table'] === $parentTable
                    && $foreignKey['foreign_columns'] === ['id']
            );

            if ($existingForeignKey !== null) {
                if ($existingForeignKey['on_delete'] !== $onDelete) {
                    throw new RuntimeException("The existing {$table}.{$column} foreign key has an unexpected delete rule.");
                }

                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $column, $parentTable, $onDelete): void {
                $blueprint->foreign($column, $this->constraintName($table, $column))
                    ->references('id')
                    ->on($parentTable)
                    ->onDelete($onDelete);
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::RELATIONSHIPS) as [$table, $column]) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $constraintName = $this->constraintName($table, $column);
            $hasRepairConstraint = collect(Schema::getForeignKeys($table))
                ->contains(fn (array $foreignKey): bool => $foreignKey['name'] === $constraintName);

            if ($hasRepairConstraint) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($constraintName));
            }
        }
    }

    private function constraintName(string $table, string $column): string
    {
        return "restore_{$table}_{$column}_fk";
    }
};
