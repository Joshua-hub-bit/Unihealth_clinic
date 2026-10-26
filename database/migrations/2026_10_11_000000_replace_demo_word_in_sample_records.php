<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TEXT_COLUMNS = [
        'appointments' => ['service_type', 'reason', 'notes', 'cancellation_reason'],
        'clinic_visits' => ['reason', 'symptoms', 'treatment', 'disposition', 'notes'],
        'referrals' => [
            'referral_no',
            'referred_to_name',
            'specialty',
            'diagnosis',
            'history',
            'reason',
            'management_given',
            'feedback',
        ],
    ];

    public function up(): void
    {
        foreach (self::TEXT_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'id')) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                $wrappedColumn = DB::getQueryGrammar()->wrap($column);
                DB::table($table)
                    ->select(['id', $column])
                    ->whereRaw("LOWER({$wrappedColumn}) LIKE ?", ['%demo%'])
                    ->orderBy('id')
                    ->chunkById(500, function ($rows) use ($table, $column): void {
                        foreach ($rows as $row) {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->update([$column => $this->replaceDemoWord((string) $row->{$column})]);
                        }
                    });
            }
        }
    }

    private function replaceDemoWord(string $value): string
    {
        return preg_replace_callback('/demo/i', static function (array $match): string {
            return match ($match[0]) {
                'DEMO' => 'SAMPLE',
                'Demo' => 'Sample',
                default => 'sample',
            };
        }, $value) ?? $value;
    }

    public function down(): void
    {
        // Reverting text substitutions could overwrite wording entered after this migration.
    }
};
